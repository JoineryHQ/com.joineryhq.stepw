<?php

/**
 * Utilities for retrieving workflow configuration data.
 */
class CRM_Stepw_Utils_WorkflowData {
  private static function getAllWorkflowConfig() {
    // fixme3: somewhere (perhaps not here?) we need a data structure validator (e.g.
    // to make sure multi-option steps don't follow steps with any 'afform' options)
    //
    
    // fixme3: if config references afformName for any afforms that don't exist ...
    // then what to do? We'll get a fata Exception if we try to reference them.
    // But who should be warned about that, and when? Can we just remove them
    // from the config (that seems risky, as it will break the workflow)?
    // Maybe we just redirect to invalid/ or otherwise find a way to say "this workflow
    // is not configured properly; please try again".
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
