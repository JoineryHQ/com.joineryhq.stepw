<?php

/**
 * General-purpose utilities for stepw extension.
 *
 */
class CRM_Stepw_Utils_WpShortcode {
  public static function getStepwiseProperties() {
    // fixme: this  shortcode could crete mutiple buttons, depending on workflow config (e.g. video pages in 3 languages).
    
    $ret = [];
    
    // fixme: stub for shortcode properties.
    $stepOrdinal = $_GET['stepwise-step'] ?? 1;
    $buttonDisabled = ($_GET['stepwise-button-disabled'] ?? NULL);
    $workflowStepCount = $_GET['stepwise-step-count'] ?? 10;
    $buttonText = $_GET['stepwise-button-text'] ?? 'Next';

    if (CRM_Stepw_Utils_Userparams::isStepwiseWorkflow('request')) {    
      CRM_Stepw_Utils_Userparams::validateWorkflowInstanceStep('request', TRUE);

      $workflowPublicId = CRM_Stepw_Utils_Userparams::getUserParams('request', CRM_Stepw_Utils_Userparams::QP_WORKFLOW_INSTANCE_ID);

      $hrefQueryParams = [
        CRM_Stepw_Utils_Userparams::QP_WORKFLOW_INSTANCE_ID=> $workflowPublicId,
        CRM_Stepw_Utils_Userparams::QP_DONE_STEP_ID => CRM_Stepw_Utils_Userparams::getUserParams('request', CRM_Stepw_Utils_Userparams::QP_STEP_ID)
      ];
      $buttonHref = CRM_Stepw_Utils_General::buildNextUrl($hrefQueryParams);
      $ret = [
        'percentage' => round(($stepOrdinal / $workflowStepCount * 100)),
        'buttonDisabled' => $buttonDisabled,
        'stepOrdinal' => $stepOrdinal,
        'worfklowStepCount' => $workflowStepCount,
        'buttonText' => $buttonText,
        'buttonHref' => $buttonHref,
      ];
    }
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
