<?php

/**
 * Validation utility methods for stepw
 */
class CRM_Stepw_Utils_Validation {

  /**
   * For given public Id for a workflowInstance and a step, determine whether 
   * both are valid in the current state. "Valid" means that workflowInstance
   * exists in state, step exists in workflowInstance.
   *
   * @param string $workflowInstancePublicId
   * @param string $stepPublicId
   */
  public static function isWorkflowInstanceAndStepValid (string $workflowInstancePublicId, string $stepPublicId) {
    $ret = FALSE;
    $workflowInstance = CRM_Stepw_State::singleton()->getWorkflowInstance($workflowInstancePublicId);
    if ($workflowInstance) {
      $ret = $workflowInstance->hasStep($stepPublicId);
    }
    return $ret;
  }
  
  public static function stepIsForAfformName(string $workflowInstancePublicId, string $stepPublicId, string $afformName) {
    $ret = FALSE;
    $workflowInstance = CRM_Stepw_State::singleton()->getWorkflowInstance($workflowInstancePublicId);
    $stepAfformName = $workflowInstance->getStepAfformName($stepPublicId);
    if ($afformName == $stepAfformName) {    
      $ret = TRUE;
    }
    return $ret;
  }
  
  public static function stepHasAfformSubmissionId(string $workflowInstancePublicId, string $stepPublicId, string $afformSubmissionId) {
    $ret = FALSE;
    $workflowInstance = CRM_Stepw_State::singleton()->getWorkflowInstance($workflowInstancePublicId);
    if ($workflowInstance->stepHasAfformSubmissionId($stepPublicId, $afformSubmissionId)) {    
      $ret = TRUE;
    }
    return $ret;
  }
}
