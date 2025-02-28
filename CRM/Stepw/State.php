<?php


/**
 * State handler for stepw extension
 */

use CRM_Stepw_ExtensionUtil as E;


class CRM_Stepw_State {
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

  public function createWorkflowInstance(CRM_Stepw_WorkflowInstance $workflowInstance) {
    $workflowInstances = $this->storage->get('workflowInstances', $this->scopeKey) ?? [];
    $workflowInstances[$workflowInstance->getPublicId()] = $workflowInstance;
    $this->set('workflowInstances', $workflowInstances);
  }
  
  public function getWorkflowInstance($workflowPublicId) {
    $stateWorkflows = $this->get('workflowInstances');
    return $stateWorkflows[$workflowPublicId] ?? null;
  }
}
