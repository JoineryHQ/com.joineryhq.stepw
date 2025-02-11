<?php

/**
 * General-purpose utilities for stepw extension.
 *
 */
class CRM_Stepw_Utils_WpShortcode {
  public static function getStepwiseProperties() {
    // fixme: stub for shortcode properties.
    $stepOrdinal = $_GET['stepwise-step'] ?? 1;
    $buttonDisabled = ($_GET['stepwise-button-disabled'] ?? NULL);
    $workflowStepCount = $_GET['stepwise-step-count'] ?? 10;
    $buttonText = $_GET['stepwise-button-text'] ?? 'Next';

    if (!self::validate()) {
      // Request is invalid, i.e., somebody's mucking about with url parameters,
      // so we should shut the whole thing down, albeit with some explanation.
      // In the context of a shortcode, we're on a WP page; therefore, redirection 
      // is a decent way to shut things down and display an explanation.
      CRM_Stepw_Utils_General::redirectToInvalid();
    }
    
    $workflowPublicId = CRM_Stepw_Utils_Userparams::getUserParams('request', CRM_Stepw_Utils_Userparams::QP_WORKFLOW_INSTANCE_ID);

    $buttonHref = CRM_Utils_System::url('civicrm/stepwise/next', [
      CRM_Stepw_Utils_Userparams::QP_WORKFLOW_INSTANCE_ID=> $workflowPublicId,
      CRM_Stepw_Utils_Userparams::QP_DONE_STEP_ID => CRM_Stepw_Utils_Userparams::getUserParams('request', CRM_Stepw_Utils_Userparams::QP_STEP_ID)
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

  static private function validate() {
    $isValid = FALSE;
    
    $workflowInstancePublicId = CRM_Stepw_Utils_Userparams::getUserParams('request', CRM_Stepw_Utils_Userparams::QP_WORKFLOW_INSTANCE_ID);
    $stepPublicId = CRM_Stepw_Utils_Userparams::getUserParams('request', CRM_Stepw_Utils_Userparams::QP_STEP_ID);
    $workflowInstance = CRM_Stepw_State::singleton()->getWorkflowInstance($workflowInstancePublicId);
    if (
      !empty($stepPublicId)
      && !empty($workflowInstance)
      && ($workflowInstance->validateStep($stepPublicId))
    ) {
      $isValid = TRUE;
    }

    return $isValid;
  }
}
