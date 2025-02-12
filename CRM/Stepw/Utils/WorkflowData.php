<?php

/**
 * Utilities for retrieving workflow configuration data.
 */
class CRM_Stepw_Utils_WorkflowData {
  private static function getAllWorkflowConfig() {
    return CRM_Stepw_Fixme_Data::getSampleData();
  }

  public static function getWorkflowConfigById(Int $workflowId) {
    $data = self::getAllWorkflowConfig();
    return ($data[$workflowId] ?? NULL);
  }
  
  public static function getAllAfformNames() {
    $afformNames = [];
    $data = self::getAllWorkflowConfig();
    foreach ($data as $workflowId => $workflow) {
      foreach (($workflow['steps'] ?? []) as $step) {
        if (($step['type'] ?? '') == 'afform') {
          if ($afformName = ($step['afform_name'] ?? FALSE)) {
            $afformNames[] = $afformName;
          }
        }
      }
    }
    return $afformNames;
  }

}
