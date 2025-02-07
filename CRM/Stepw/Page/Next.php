<?php
use CRM_Stepw_ExtensionUtil as E;

class CRM_Stepw_Page_Next extends CRM_Core_Page {

  public function run() {

    $workflowId = CRM_Utils_Request::retrieve('sw', 'Int');
    $stepId = CRM_Utils_Request::retrieve('ss', 'Int');
    $workflow = CRM_Stepw_Utils_WorkflowData::getWorkflowConfigById($workflowId);
    // fixme: if no stepid, we should start at step 1, but also need a mechanism to ensure previous steps have been completed -- i.e., no bookmarking 'step 2' and starting there.
    if (empty($stepId)) {
      $stepId = 1;
    }
      
    $step = $workflow[$stepId] ?? NULL;
    if (empty($workflow)) {
      die('fixme: workflow not found; should return 404');
    }
    if (empty($step)) {
      die('fixme: step not found in this workflow; should return 404');
    }
    
    $params = [
      'sw' => $workflowId,
      'ss' => $stepId,
    ];
    // fixme: this won't be the correct redirect
    $redirect = CRM_Stepw_Utils_Userparams::appendParamsToUrl($params, $step['url']);
    die('fixme: probably need to fix this redirect url: '. $redirect);
    CRM_Utils_System::redirect($redirect);
  }

}
