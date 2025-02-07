<?php

/**
 * General-purpose utilities for stepw extension.
 *
 */
class CRM_Stepw_Utils_WpShortcode {
  public static function getStepwiseProperties() {
    // fixme: stub.
    $stepOrdinal = $_GET['stepwise-step'] ?? 1;
    $buttonDisabled = ($_GET['stepwise-button-disabled'] ?? NULL);
    $workflowStepCount = $_GET['stepwise-step-count'] ?? 10;
    $buttonText = $_GET['stepwise-button-text'] ?? 'Next';
            
    $isValid = CRM_Stepw_State::singleton()->validateWorkflowInstanceStep(CRM_Stepw_WorkflowInstance::STEPW_WI_STEP_STATUS_OPEN);
        
    if (!$isValid) {
      // Request is invalid, i.e., somebody's mucking about with url parameters,
      // so we should shut the whole thing down, albeit with some explanation.
      // In the context of a shortcode, we're on a WP page; therefore, redirection 
      // decent way to shut things down and display an explanation.
      $redirect = CRM_Utils_System::url('civicrm/stepwise/invalid', '', FALSE, NULL, TRUE, TRUE);
      CRM_Utils_System::redirect($redirect);
    }
    
    $workflowPublicId = CRM_Stepw_Utils_Userparams::getUrlQueryParams(CRM_Stepw_Utils_Userparams::QP_START_WORKFLOW_ID);
    $stepPublicId = CRM_Stepw_Utils_Userparams::getUrlQueryParams(CRM_Stepw_Utils_Userparams::QP_STEP_ID);

    $buttonHref = CRM_Utils_System::url('civicrm/stepwise/next', [
      CRM_Stepw_Utils_Userparams::QP_START_WORKFLOW_ID => $workflowPublicId,
      CRM_Stepw_Utils_Userparams::QP_DONE_STEP_ID => $stepPublicId
    ]);
    $ret = [
      'percentage' => round(($stepOrdinal / $workflowStepCount * 100)),
      'buttonDisabled' => $buttonDisabled,
      'stepOrdinal' => $stepOrdinal,
      'worfklowStepCount' => $workflowStepCount,
      'buttonText' => $buttonText,
      'buttonHref' => $buttonHref,
    ];
    
    return $ret;
  }

}
