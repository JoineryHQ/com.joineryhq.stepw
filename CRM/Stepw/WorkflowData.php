<?php

/**
 * Class to handle workflow configuration data.
 */

use CRM_Stepw_ExtensionUtil as E;

class CRM_Stepw_WorkflowData {
  static $_singleton;
  
  /**
   * Array of all workflow data
   * @var Array
   */
  private $data;
  
  /**
   * List of afformNames used in workflow data.
   * @var Array
   */
  private $allAfformNames = [];
  
  private $workflowIdsWithBadConfig = [];
  
  private function __construct() {

    // fixme: somewhere (perhaps not here?) we need a data structure validator (e.g.
    // to make sure multi-option steps don't follow steps with any 'afform' options)
    //
    
    // fixme: if config references afformName for any afforms that don't exist ...
    // then what to do? We'll get a fata Exception if we try to reference them.
    // But who should be warned about that, and when? Can we just remove them
    // from the config (that seems risky, as it will break the workflow)?
    // Maybe we just redirect to invalid/ or otherwise find a way to say "this workflow
    // is not configured properly; please try again".
    $data = CRM_Stepw_Fixme_LoadData::getSampleData();
    foreach ($data as $workflowId => $workflow) {
      foreach (($workflow['steps'] ?? []) as $stepId => $step) {
        foreach ($step['options'] as $optionId => $option) {
          if (($option['type'] ?? '') == 'afform' && ($afformName = ($option['afformName'] ?? FALSE))) {
            // If this afform doesn't exist, throw an exception.
            if (self::afformExists($afformName)) {
              // Add afform url to config data for this afform step/option.
              $afform = \Civi\Api4\Afform::get(TRUE)
                ->setCheckPermissions(FALSE)
                ->addWhere('name', '=', $afformName)
                ->setLimit(1)
                ->execute()
                ->first();
              $data[$workflowId]['steps'][$stepId]['options'][$optionId]['url'] = CRM_Utils_System::url($afform['server_route']);

              // Add this afformName to allAfformNamtes, for future reference.
              $this->allAfformNames[] = $afformName;
            }
            else {
              // This function is sometimes called (e.g. when debug is on, or when otherwise
              // afform cache is being rebuilt) from executino paths that will
              // end with WSOD if we throw an exception. Therefore, just flag
              // this workflow config as invalid, and we'll throw an exception
              // elsewhere -- see, e.g. this->getWorkflowConfigById().
              $errorContext = [
                'error_id' => $this->getsetWorkflowConfigErrorId($workflowId),
                'workflowId' => $workflowId,
                'stepId' => $stepId,
                'optionId' => $optionId,
                'afformName' => $afformName,
              ];
              \Civi::log()->error("Workflow step/option configured with non-existent afformName.", $errorContext);
            }
          }
        }
      }
    }
    $this->data = $data;
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
      self::$_singleton = new CRM_Stepw_WorkflowData();
    }
    return self::$_singleton;
  }
  
  public function getWorkflowConfigById(String $workflowId) {
    if (!empty($this->workflowIdsWithBadConfig[$workflowId])) {      
      throw new CRM_Stepw_Exception('Workflow has bad configuration. For more info, see error log entries for error_id '. $this->workflowIdsWithBadConfig[$workflowId], 'CRM_Stepw_WorkflowData_getWorkflowConfigById_bad-workflow-config');
    }
    return ($this->data[$workflowId] ?? NULL);
  }
  
  public function getAllAfformNames() {
    return $this->allAfformNames;
  }

  public static function getPseudoFinalStepConfig() {
    return [
      'options' => [
        [
          'type' => 'url',
          'url' => CRM_Utils_System::url('civicrm/stepwise/final', '', TRUE, NULL, FALSE, TRUE),
        ]
      ],
    ];
  }
  
  private static function afformExists(string $afformName) {
    $afformCount = \Civi\Api4\Afform::get(TRUE)
      ->setCheckPermissions(FALSE)
      ->addWhere('name', '=', $afformName)
      ->setLimit(1)
      ->execute()
      ->count();
    return ($afformCount ? TRUE : FALSE);
  }
  
  private function getsetWorkflowConfigErrorId(string $workflowId) {
    if (empty($this->workflowIdsWithBadConfig[$workflowId])) {
      $this->workflowIdsWithBadConfig[$workflowId] = CRM_Stepw_Utils_General::generateErrorId();    
    }
    return $this->workflowIdsWithBadConfig[$workflowId];
  }

}
