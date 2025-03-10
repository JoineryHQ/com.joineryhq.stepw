<?php

use CRM_Stepw_ExtensionUtil as E;

/**
 * General-purpose utilities for stepw extension.
 *
 */
class CRM_Stepw_Utils_WpShortcode {
  public static function getStepwiseButtonHtml() {
    // note: here we will:
    //  - Build and return html for one or more buttons, for replacement of WP [stepwise-button] shortcode.
    // 

    // Use a static cache here, because the WP plugin may call this method multiple
    // times in a single page load.
    static $ret;
    if (!isset($ret)) {
      $buttons = [];

      // If we're debugging (e.g. for design), do some special handling.
      $debugParams = self::getDebugParams();
      if (!empty($debugParams)) {
        $buttonHref = '#';      
        if ($debugParams['buttonDisabled']) {
          $buttonHref64 = base64_encode($buttonHref);
        }
        $button = [
          'href64' => ($buttonHref64 ?? NULL),
          'href' => $buttonHref,
          'text' => $debugParams['buttonText'],
          'disabled' => $debugParams['buttonDisabled'],
        ];
        $buttons = array_pad($buttons, $debugParams['buttonCount'], $button);
      }
      else {
        if (!self::isValidParams()) {
          return '';
        }      

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
            // if button is disabled, use '#' for the buttonHref, and pass $buttonHref to
            // the template where JS can get at it. The onpage enforcer js will then:
            // 1. do enforcement;
            // 2. set the href
            // 3. enable the button    
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

      $tpl = CRM_Core_Smarty::singleton();
      $tpl->assign('buttons', $buttons);
      $buttonHtml = $tpl->fetch('CRM/Stepw/snippet/StepwiseButton.tpl');
      $ret = $buttonHtml;
    }
    return $ret;
    
  }
  
  public static function getProgressBarHtml() {
    // note: here we will:
    //  - Build and return html for a progress bar to appear at the top of the page.
    // 

    // If we're debugging (e.g. for design), do some special handling.
    $debugParams = self::getDebugParams();
    if (!empty($debugParams)) {
      $stepOrdinal = $debugParams['stepOrdinal'];
      $stepTotalCount = $debugParams['stepTotalCount'];
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
    $tpl->assign('omitProgressbar', ($progressProperties['omitProgressbar'] ?? FALSE));
    $ret = $tpl->fetch('CRM/Stepw/snippet/StepwiseProgressBar.tpl');
    return $ret;
  }
  
  public static function getPageAssets($type = 'all') {
    $ret = [];
    if (!self::isValidParams() && empty(self::getDebugParams())) {
      return $ret;
    }
    
    switch($type) {
      case 'button':
        $isButton = TRUE;
        break;
      case 'progressbar':
        $isProgress = TRUE;
        break;
      case 'all':
        $isButton = TRUE;        
        $isProgress = TRUE;
        break;
    }
    
    if ($isButton) {
      $ret[] = [
        'type'   => 'style',
        'handle' => 'stepwise-button-css', 
        'src'    => E::url('cms_resources/WordPress/css/stepwise-button.css'),
      ];
      $ret[] = [
        'type'   => 'script',
        'handle' => 'jquery',
        'src'    => '',
      ];
      $ret[] = [
        'type'   => 'script',
        'handle' => 'stepwise-button-js',
        'src'    => E::url('cms_resources/WordPress/js/stepwise-button.js'),
      ];
    }
    if ($isProgress) {
      $ret[] = [
        'type'   => 'style',
        'handle' => 'stepwise-progressbar-css', 
        'src'    => E::url('cms_resources/WordPress/css/stepwise-progressbar.css'),
      ];
      $ret[] = [
        'type'   => 'script',
        'handle' => 'jquery',
        'src'    => '',
      ];
      $ret[] = [
        'type'   => 'script',
        'handle' => 'stepwise-progressbar-js',
        'src'    => E::url('cms_resources/WordPress/js/stepwise-progressbar.js'),
      ];      
    }
    
    // if this step/option requires onpage enforcement, also add onpage enforcer JS.
    $workflowInstancePublicId = CRM_Stepw_Utils_Userparams::getUserParams('request', CRM_Stepw_Utils_Userparams::QP_WORKFLOW_INSTANCE_ID);
    $stepPublicId = CRM_Stepw_Utils_Userparams::getUserParams('request', CRM_Stepw_Utils_Userparams::QP_STEP_ID);
    $workflowInstance = CRM_Stepw_State::singleton()->getWorkflowInstance($workflowInstancePublicId);
    if($workflowInstance->stepRequiresOnpageEnforcer($stepPublicId)) {
      $ret[] = [
        'type'   => 'script',
        'handle' => 'stepwise-onpage-enforcer-sdk',
        'src'    => 'https://player.vimeo.com/api/player.js',
      ];
      $ret[] = [
        'type'   => 'script',
        'handle' => 'stepwise-onpage-enforcer-js',
        'src'    => E::url('cms_resources/WordPress/js/stepwise-video-enforcer-sdk.js'),
      ];
    }
    
    return $ret;
  }

  private static function getDebugParams() {
    static $ret;
    if (!isset($ret)) {
      $ret = [];
      $stepwiseShortcodeDebug = ($_GET['stepwise-button-debug'] ?? 0);
      if ($stepwiseShortcodeDebug) {
        $ret['buttonDisabled'] = ($_GET['stepwise-button-disabled'] ?? NULL);
        $ret['buttonText'] = $_GET['stepwise-button-text'] ?? 'Continue';
        $ret['buttonCount'] = $_GET['stepwise-button-count'] ?? 1;
        $ret['stepOrdinal'] = $_GET['stepwise-step'] ?? 1;
        $ret['stepTotalCount'] = $_GET['stepwise-step-count'] ?? 10;        
      }      
    }
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
        throw new CRM_Stepw_Exception('WorkflowInstanceId and StepId were both given, but together are not valid in state, in ' . __METHOD__, 'CRM_Stepw_Utils_WpShortcode_isValidParams-invalid-public-ids');
      }
      // if we're still here, return true (params are valid)
      $ret = TRUE;
      return $ret;      
    }
    return $ret;    
  }

}
