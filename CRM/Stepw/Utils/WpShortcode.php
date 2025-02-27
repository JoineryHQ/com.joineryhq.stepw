<?php

/**
 * General-purpose utilities for stepw extension.
 *
 */
class CRM_Stepw_Utils_WpShortcode {
  public static function getStepwiseButtonHtml() {
    // note: here we will:
    //  - Build and return html for one or more buttons, for replacement of WP [stepwise-button] shortcode.
    // 

    $buttons = [];
    
    // If we're debugging (e.g. for design), do some special handling.
    $stepwiseShortcodeDebug = ($_GET['stepwise-button-debug'] ?? 0);
    if ($stepwiseShortcodeDebug) {    
      $buttonDisabled = ($_GET['stepwise-button-disabled'] ?? NULL);
      $buttonText = $_GET['stepwise-button-text'] ?? 'Next';
      $buttonCount = $_GET['stepwise-button-count'] ?? 1;
      $buttonHref = '#';
      
      if ($buttonDisabled) {
        $buttonHref64 = base64_encode($buttonHref);
        $buttonHref = '#';
      }
      $button = [
        'href64' => ($buttonHref64 ?? NULL),
        'href' => $buttonHref,
        'text' => $buttonText,
        'disabled' => $buttonDisabled,        
      ];
      $buttons = array_pad($buttons, $buttonCount, $button);
    }
    else {
      if (!self::isValidParams()) {
        return '';
      }      
    
      // fixme3: this  shortcode could create mutiple buttons, depending on workflow config (e.g. video pages in 3 languages).
      // Therefore, we must process the template multiple times, per workflow config.
      //
      
      $workflowInstancePublicId = CRM_Stepw_Utils_Userparams::getUserParams('request', CRM_Stepw_Utils_Userparams::QP_WORKFLOW_INSTANCE_ID);
      $stepPublicId = CRM_Stepw_Utils_Userparams::getUserParams('request', CRM_Stepw_Utils_Userparams::QP_STEP_ID);

      $workflowInstance = CRM_Stepw_State::singleton()->getWorkflowInstance($workflowInstancePublicId);
      $subsequentStepOptions = $workflowInstance->getSubsequentStepOptionButtonProperties($stepPublicId);
      foreach ($subsequentStepOptions as $subsequentStepOption) {
        $hrefQueryParams = [
          CRM_Stepw_Utils_Userparams::QP_WORKFLOW_INSTANCE_ID=> $workflowInstancePublicId,
          CRM_Stepw_Utils_Userparams::QP_DONE_STEP_ID => CRM_Stepw_Utils_Userparams::getUserParams('request', CRM_Stepw_Utils_Userparams::QP_STEP_ID),
        ];
        if (count($subsequentStepOptions) > 1) {
          $hrefQueryParams[CRM_Stepw_Utils_Userparams::QP_SUBSEQUENT_STEP_SELECTED_OPTION_ID] = $subsequentStepOption['publicId'];
        }
        $buttonHref = CRM_Stepw_Utils_General::buildStepUrl($hrefQueryParams);


        $buttonDisabled = $subsequentStepOption['buttonDisabled'];
        
        if ($buttonDisabled) {
          $buttonHref64 = base64_encode($buttonHref);
          $buttonHref = '#';
        }
        $button = [
          'href64' => ($buttonHref64 ?? NULL),
          'href' => $buttonHref,
          'text' => $subsequentStepOption['buttonLabel'],
          'disabled' => $buttonDisabled,        
        ];

        $buttons[] = $button;
      }

    }
    
    // if button is disabled, use '#' for the buttonHref, and pass $buttonHref to
    // the template where JS can get at it. The video enforcer js will then:
    // 1. do enforcement;
    // 2. set the href
    // 3. enable the button    
    
    $tpl = CRM_Core_Smarty::singleton();
    $tpl->assign('buttons', $buttons);
    $buttonHtml = $tpl->fetch('CRM/Stepw/snippet/StepwiseButton.tpl');
    return $buttonHtml;
    
  }
  
  public static function getProgressBarHtml() {
    // note: here we will:
    //  - Build and return html for a progress bar to appear at the top of the page.
    // 

    // If we're debugging (e.g. for design), do some special handling.
    $stepwiseShortcodeDebug = ($_GET['stepwise-button-debug'] ?? 0);
    if ($stepwiseShortcodeDebug) {    
      $stepOrdinal = $_GET['stepwise-step'] ?? 1;
      $stepTotalCount = $_GET['stepwise-step-count'] ?? 10;
    }
    else {
      if (!self::isValidParams()) {
        return '';
      }
    
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

  private static function isValidParams() {
    static $ret;
    if (!isset($ret)) {
      // return FALSE (params are invalid) if any of:
      //  - we're not in a stepwise workflow.
      //  - Given WI NOT exists in state
      //  - Given Step public id NOT exists in WI
      //
      // return false if: We're not in a workflowInstance.
      if (!CRM_Stepw_Utils_Userparams::isStepwiseWorkflow('request')) {
        $ret = FALSE;
        return $ret;
      }
      // return false if: Given WI or step don't exist in state.
      $workflowInstancePublicId = CRM_Stepw_Utils_Userparams::getUserParams('request', CRM_Stepw_Utils_Userparams::QP_WORKFLOW_INSTANCE_ID);
      $stepPublicId = CRM_Stepw_Utils_Userparams::getUserParams('request', CRM_Stepw_Utils_Userparams::QP_STEP_ID);
      if (!CRM_Stepw_Utils_Validation::isWorkflowInstanceAndStepValid($workflowInstancePublicId, $stepPublicId)) {
        $ret = FALSE;
        return $ret;
      }
      // if we're still here, return true (params are valid)
      $ret = TRUE;
      return $ret;      
    }
  }

}
