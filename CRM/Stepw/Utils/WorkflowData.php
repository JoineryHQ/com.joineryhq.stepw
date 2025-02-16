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

  public static function getCurrentWorkflowConfigStep($source) {
    $ret = [];

    $userParams = CRM_Stepw_Utils_Userparams::getUserParams($source);
    $workflowInstancePublicId = $userParams[CRM_Stepw_Utils_Userparams::QP_WORKFLOW_INSTANCE_ID];
    $workflowStepPublicId = $userParams[CRM_Stepw_Utils_Userparams::QP_STEP_ID];

    $workflowInstance = CRM_Stepw_State::singleton()->getWorkflowInstance($workflowInstancePublicId);
    $workflowConfig = $workflowInstance->getVar('workflowConfig');
    $stepNumber = $workflowInstance->getStepNumberByPublicId($workflowStepPublicId);
    $workflowConfigStep = $workflowConfig['steps'][$stepNumber];
    $ret = $workflowConfigStep;
    
    return $ret;
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
