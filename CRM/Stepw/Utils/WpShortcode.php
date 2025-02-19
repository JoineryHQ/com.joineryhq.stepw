<?php

/**
 * General-purpose utilities for stepw extension.
 *
 */
class CRM_Stepw_Utils_WpShortcode {
  public static function getStepwiseButtonHtml() {
    // fixme3 note: here we will:
    //  - Build and return html for one or more buttons, for replacement of WP [stepwise-button] shortcode.
    // 
    // fixme3val: validate getStepwiseButtonHtml.
    //  - Given WI exists in state
    //  - Given Step public id exists in WI
    //  - Given step is NOT the last step in workflow
    //  -- VALIDATION FAILURE: return empty string
    //
    
    // fixme3: this  shortcode could create mutiple buttons, depending on workflow config (e.g. video pages in 3 languages).
    // Therefore, we must process the template multiple times, per workflow config.
    //
    
    // If is not stepwise workflow (and not shortcode styles debugging: return empty string.
    $stepwiseShortcodeDebug = $_GET['stepwise-button-debug'] ?? 0;
    if (!$stepwiseShortcodeDebug && !CRM_Stepw_Utils_Userparams::isStepwiseWorkflow('request')) {    
      return '';
    }
        
        
    // support designer's debugging of percentage indicator outside of stepwise workflow.
    if ($stepwiseShortcodeDebug) {
      $buttonDisabled = ($_GET['stepwise-button-disabled'] ?? NULL);
      $buttonText = $_GET['stepwise-button-text'] ?? 'Next';
      $buttonHref = '#';
    }
    else {

      $workflowInstancePublicId = CRM_Stepw_Utils_Userparams::getUserParams('request', CRM_Stepw_Utils_Userparams::QP_WORKFLOW_INSTANCE_ID);
      $stepPublicId = CRM_Stepw_Utils_Userparams::getUserParams('request', CRM_Stepw_Utils_Userparams::QP_STEP_ID);
      $state = CRM_Stepw_State::singleton();
      $workflowInstance = $state->getWorkflowInstance($workflowInstancePublicId);
      $buttonText = $workflowInstance->getButtonLabel($stepPublicId);
      $buttonDisabled = $workflowInstance->getButtonDisabled($stepPublicId);

      $hrefQueryParams = [
        CRM_Stepw_Utils_Userparams::QP_WORKFLOW_INSTANCE_ID=> $workflowInstancePublicId,
        CRM_Stepw_Utils_Userparams::QP_DONE_STEP_ID => CRM_Stepw_Utils_Userparams::getUserParams('request', CRM_Stepw_Utils_Userparams::QP_STEP_ID)
      ];
      $buttonHref = CRM_Stepw_Utils_General::buildStepUrl($hrefQueryParams);
    }
    
    // if button is disabled, use '#' for the buttonHref, and pass $buttonHref to
    // the template where JS can get at it. The video enforcer js will then:
    // 1. do enforcement;
    // 2. set the href
    // 3. enable the button    
    if ($buttonDisabled) {
      $buttonHref64 = base64_encode($buttonHref);
      $buttonHref = '#';
    }
    
    
    $buttonHtml = '';
    $tpl = CRM_Core_Smarty::singleton();
    $tpl->assign('buttonHref64', $buttonHref64);
    $tpl->assign('buttonHref', $buttonHref);
    $tpl->assign('buttonText', $buttonText);
    $tpl->assign('buttonDisabled', $buttonDisabled);
    $buttonHtml = $tpl->fetch('CRM/Stepw/snippet/StepwiseButton.tpl');
    return $buttonHtml;
    
  }
  
  public static function getProgressBarHtml() {
    // fixme3 note: here we will:
    //  - Build and return html for a progress bar to appear at the top of the page.
    // 
    // fixme3val: validate getStepwiseButtonHtml.
    //  - Given WI exists in state
    //  - Given Step public id exists in WI
    //  -- VALIDATION FAILURE: return empty string
    //
       
    // If is not stepwise workflow (and not shortcode styles debugging: return empty string.
    $stepwiseShortcodeDebug = $_GET['stepwise-button-debug'] ?? 0;
    if (!$stepwiseShortcodeDebug && !CRM_Stepw_Utils_Userparams::isStepwiseWorkflow('request')) {    
      return '';
    }
        
    // support designer's debugging of percentage indicator outside of stepwise workflow.
    if ($stepwiseShortcodeDebug) {
      $stepOrdinal = $_GET['stepwise-step'] ?? 1;
      $stepTotalCount = $_GET['stepwise-step-count'] ?? 10;
    }
    else {
      $workflowInstancePublicId = CRM_Stepw_Utils_Userparams::getUserParams('request', CRM_Stepw_Utils_Userparams::QP_WORKFLOW_INSTANCE_ID);
      $stepPublicId = CRM_Stepw_Utils_Userparams::getUserParams('request', CRM_Stepw_Utils_Userparams::QP_STEP_ID);
      $workflowInstance = CRM_Stepw_State::singleton()->getWorkflowInstance($workflowInstancePublicId);
      $progressProperties = $workflowInstance->getProgress($stepPublicId);
      $stepOrdinal = $progressProperties['stepOrdinal'];
      $stepTotalCount = $progressProperties['stepTotalCount'];
    }
    $percentage = round(($stepOrdinal / $stepTotalCount * 100));
    
    $ret = '';
    $tpl = CRM_Core_Smarty::singleton();
    $tpl->assign('percentage', $percentage);
    $tpl->assign('stepOrdinal', $stepOrdinal);
    $tpl->assign('stepTotalCount', $stepTotalCount);
    $ret = $tpl->fetch('CRM/Stepw/snippet/StepwiseProgressBar.tpl');
    return $ret;
    
  
    
  }

}
