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
    $data = CRM_Stepw_Fixme_Data::getSampleData();
    foreach ($data as $workflowId => $workflow) {
      foreach (($workflow['steps'] ?? []) as $stepId => $step) {
        foreach ($step['options'] as $optionId => $option) {
          if (($option['type'] ?? '') == 'afform' && ($afformName = ($option['afformName'] ?? FALSE))) {
            // If this afform doesn't exist, throw an exception.
            self::afformExistsOrException($afformName);

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
  
  private static function afformExistsOrException($afformName) {
    $afformCount = \Civi\Api4\Afform::get(TRUE)
      ->setCheckPermissions(FALSE)
      ->addWhere('name', '=', $afformName)
      ->setLimit(1)
      ->execute()
      ->count();
    if (!$afformCount) {
      $extension = \Civi\Api4\Extension::get(TRUE)
        ->setCheckPermissions(FALSE)
        ->addWhere('key', '=', 'com.joineryhq.stepw')
        ->setLimit(1)
        ->addSelect('label')
        ->execute()
        ->first();
      $label = ($extension['label'] ?? E::LONG_NAME);
      $errData = [
        'afformName' => $afformName
      ];
      throw new CRM_Extension_Exception("Extension '$label' is configured to use a form that does not exist.", 'CRM_Stepw_WorkflowData_afformExistsOrException_bad-afform-name', $errData);
    }
    
  }
}
