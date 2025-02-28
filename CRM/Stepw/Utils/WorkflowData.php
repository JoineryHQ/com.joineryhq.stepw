<?php

/**
 * Utilities for retrieving workflow configuration data.
 */
class CRM_Stepw_Utils_WorkflowData {
  private static function getAllWorkflowConfig() {
    // fixme3: somewhere (perhaps not here?) we need a data structure validator (e.g.
    // to make sure multi-option steps don't follow steps with any 'afform' options)
    //
    return CRM_Stepw_Fixme_Data::getSampleData();
  }

  public static function getWorkflowConfigById(String $workflowId) {
    $data = self::getAllWorkflowConfig();
    return ($data[$workflowId] ?? NULL);
  }
  
  public static function getAllAfformNames() {
    $afformNames = [];
    $data = self::getAllWorkflowConfig();
    foreach ($data as $workflowId => $workflow) {
      foreach (($workflow['steps'] ?? []) as $step) {
        foreach ($step['options'] as $option) {
          if (($option['type'] ?? '') == 'afform') {
            if ($afformName = ($option['afformName'] ?? FALSE)) {
              $afformNames[] = $afformName;
            }
          }
        }
      }
    }
    return $afformNames;
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
}
