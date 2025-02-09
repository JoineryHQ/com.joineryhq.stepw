<?php


/**
 * State handler for stepw extension
 */

use CRM_Stepw_ExtensionUtil as E;


class CRM_Stepw_State {

  const maxWorkflowAgeSeconds = (60 * 60);
  const maxWorkflowCount = 5;

  static $_singleton;
  private $scopeKey;
  private $serializedVarName;
  private $storage;

  private function __construct() {
    $this->scopeKey = E::LONG_NAME . '_STATE';
    $this->serializedVarName = $this->scopeKey . '_serialized';
    $this->storage = CRM_Core_Session::singleton();
    $this->storage->createScope($this->scopeKey);

    // Load serialized state from session. See __destruct for rationale as to
    // why we can't just store our state in the session.
    // Here in the constructor, we'll check to see if any serialized state is
    // stored, and if so, unserialize it and store it as our state.
    $serializedState = $this->storage->get($this->serializedVarName);
    if (!empty($serializedState)) {
      $state = unserialize($serializedState);
      $this->storage->set($this->scopeKey, $state);
      $this->storage->set($this->serializedVarName, NULL);
    }

  }

  function __destruct() {
    // Serialize state and store it in session storage;
    // Without this, we'll have problems because our state includes objects of
    // classes which are defined (code files are loaded) AFTER session_start()
    // is called; because of this, any such objets loaded from the session will
    // be of the type __PHP_Incomplete_Class and won't work properly.
    // (brief explanation: https://lifemichael.com/en/the-__php_incomplete_class-object-in-php-pro/)
    // Therefore, we serialize the entire state here and store it as a string
    // in the civicrm session.
    // See __construct() for how we'll reload it on future page loads.
    $vars = [];
    $this->storage->getVars($vars);
    $state = $vars[$this->scopeKey];

    $state = $this->doStateCleanup($state);

    $serializedState = serialize($state);
    $this->storage->set($this->serializedVarName, $serializedState);
  }

  /**
   * Singleton pattern.
   *
   * @see __construct()
   *
   * @param Int $workflowId
   * @return object This
   */
  public static function &singleton() {
    if (self::$_singleton === NULL) {
      self::$_singleton = new CRM_Stepw_State();
    }
    return self::$_singleton;
  }

  private function set($name, $value) {
    $this->storage->set($name, $value, $this->scopeKey);
  }

  public function get($name) {
    $ret = $this->storage->get($name, $this->scopeKey);
    return $ret;
  }

  public function storeWorkflowInstance(CRM_Stepw_WorkflowInstance $workflowInstance) {
    $workflowInstances = $this->storage->get('workflowInstances', $this->scopeKey) ?? [];
    $publicId = $workflowInstance->getVar('publicId');
    $workflowInstances[$publicId] = $workflowInstance;
    $this->set('workflowInstances', $workflowInstances);
  }

  /**
   * 
   * @param type $workflowInstancePublicId
   * @return CRM_Stepw_WorkflowInstance
   */
  public function getWorkflowInstance($workflowInstancePublicId) {
    $stateWorkflows = $this->get('workflowInstances');
    return $stateWorkflows[$workflowInstancePublicId] ?? NULL;
  }

  /**
   * For a given array of state data, remove unneeded/outdated data, to prevent session storage abuse.
   *
   * @param Array $state State as fetched from $this->storage->getVars($vars)[$this->scopeKey]
   * @return Array Cleaned-up state.
   */
  private function doStateCleanup($state) {
    $stateWorkflowInstances = $state['workflowInstances'];
    $retainedWorkflowInstances = [];
    $multisortLastModified = [];

    // Ignore any workflowInstances that aren't the right class, and prepare to sort by age.
    foreach  ($stateWorkflowInstances as $key => $workflowInstance) {
      $retain = true;
      if (!is_a($workflowInstance, 'CRM_Stepw_WorkflowInstance')) {
        // not a valid instance.
        $retain = false;
      }
      elseif ((time() - ($workflowInstance->getVar('lastModified') ?? 0)) > self::maxWorkflowAgeSeconds) {
        // instance is too old.
        $retain = false;
      }

      if ($retain) {
        $multisortLastModified[$key] = $workflowInstance->getVar('lastModified');
        $retainedWorkflowInstances[$key] = $workflowInstance;
      }
    }

    // If we have more than N instances, remove all but the N newest.
    if (count($retainedWorkflowInstances) > self::maxWorkflowCount) {
      // Sort by age, newest first
      array_multisort($multisortLastModified, SORT_DESC, $retainedWorkflowInstances);
      $keys = array_keys($retainedWorkflowInstances);
      // Keep only the N newest.
      $retainedWorkflowInstances = array_slice($retainedWorkflowInstances, 0, self::maxWorkflowCount);
    }

    // Update state with trimmed data.
    $state['workflowInstances'] = $retainedWorkflowInstances;
    return $state;
  }
  
  /**
   * Validate whether a given step is valid, at a given status (open/closed) in the active workflow.
   * @param String $stepPublicId A step publicId, presumably passed in from the user (_GET in WP, or REFERER in afform)
   * @param Int $requireStatus Any constant CRM_Stepw_WorkflowInstance::STEPW_WI_STEP_STATUS_*
   * 
   * @return bool True on valid; false otherwise.
   */
  public function validateWorkflowInstance($workflowPublicId) {
    $urlParams = CRM_Stepw_Utils_Userparams::getUrlQueryParams();
    $workflowInstancePublicId = $urlParams[CRM_Stepw_Utils_Userparams::QP_WORKFLOW_INSTANCE_ID];

    $workflowInstance = $this->getWorkflowInstance($workflowInstancePublicId);
    
    $isValid = FALSE;
    
    if (
      !empty($workflowInstance) 
      && is_a($workflowInstance, 'CRM_Stepw_WorkflowInstance')
      && is_array($workflowInstanceSteps = $workflowInstance->getVar('steps'))
      && (($workflowInstanceSteps[$stepPublicId]['status'] ?? NULL) === $requireStatus)
    ) {
      $isValid = TRUE;
    }
    
    return $isValid;
    
  }

  /**
   * Validate whether a given step is valid, at a given status (open/closed) in the active workflow.
   * @param String $stepPublicId A step publicId, presumably passed in from the user (_GET in WP, or REFERER in afform)
   * @param String $requireStatusName One of:
   *  open
   *  closed
   * 
   * @return bool True on valid; false otherwise.
   */
  public function validateWorkflowInstanceStep(string $stepPublicId, string $requireStatusName) {
    switch ($requireStatusName) {
      case 'open':
        $requireStatusValue = CRM_Stepw_WorkflowInstance::STEPW_WI_STEP_STATUS_OPEN;
        break;
      case 'closed':
        $requireStatusValue = CRM_Stepw_WorkflowInstance::STEPW_WI_STEP_STATUS_CLOSED;
        break;
      default:
        $requireStatusValue = NULL;
        break;
    }
    $urlParams = CRM_Stepw_Utils_Userparams::getUrlQueryParams();
    $workflowInstancePublicId = $urlParams[CRM_Stepw_Utils_Userparams::QP_WORKFLOW_INSTANCE_ID];

    $workflowInstance = $this->getWorkflowInstance($workflowInstancePublicId);
    
    $isValid = FALSE;
    
    if (
      !empty($workflowInstance) 
      && is_a($workflowInstance, 'CRM_Stepw_WorkflowInstance')
      && is_array($workflowInstanceSteps = $workflowInstance->getVar('steps'))
      && (($workflowInstanceSteps[$stepPublicId]['status'] ?? NULL) === $requireStatusValue)
    ) {
      $isValid = TRUE;
    }
    
    return $isValid;
    
  }
}
