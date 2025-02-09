<?php
use CRM_Stepw_ExtensionUtil as E;

class CRM_Stepw_Page_Next extends CRM_Core_Page {

  public function run() {

    // fixme: this is very much WIP.
    $workflowInstancePublicId = CRM_Stepw_Utils_Userparams::getUrlQueryParams(CRM_Stepw_Utils_Userparams::QP_WORKFLOW_INSTANCE_ID);
    $doneStepPublicId = CRM_Stepw_Utils_Userparams::getUrlQueryParams(CRM_Stepw_Utils_Userparams::QP_DONE_STEP_ID);
    if (!empty($doneStepPublicId)) {
      $isValid = CRM_Stepw_State::singleton()->validateWorkflowInstanceStep($doneStepPublicId, CRM_Stepw_WorkflowInstance::STEPW_WI_STEP_STATUS_OPEN);
      if (!$isValid) {
//        CRM_Stepw_Utils_General::redirectToInvalid();
      }
      
      $workflow = CRM_Stepw_State::singleton()->getWorkflowInstance($workflowInstancePublicId);
      $workflow->closeStep($doneStepPublicId);
    }
    
    $workflowInstance = CRM_Stepw_State::singleton()->getWorkflowInstance($workflowInstancePublicId);
    $workflowId = $workflowInstance->getVar('workflowId');
    $workflow = CRM_Stepw_Utils_WorkflowData::getWorkflowConfigById($workflowId);

    $fixmeNextStepKey = 2;

    $step = $workflow[$fixmeNextStepKey];
    
    // Open next step in workflowInstance.
    $stepPublicId = $workflowInstance->openStep($fixmeNextStepKey);

    // Append parameters to step url and redirect thence.
    $params = [
      's' => $stepPublicId,
      'i' => $workflowInstance->getVar('publicId'),
    ];
    $redirect = CRM_Stepw_Utils_Userparams::appendParamsToUrl($params, $step['url']);
    CRM_Utils_System::redirect($redirect);

    
    die(__METHOD__);
    $workflowId = CRM_Utils_Request::retrieve('sw', 'Int');
    $fixmeNextStepKey = CRM_Utils_Request::retrieve('ss', 'Int');
    $workflow = CRM_Stepw_Utils_WorkflowData::getWorkflowConfigById($workflowId);
    // fixme: if no stepid, we should start at step 1, but also need a mechanism to ensure previous steps have been completed -- i.e., no bookmarking 'step 2' and starting there.
    if (empty($fixmeNextStepKey)) {
      $fixmeNextStepKey = 1;
    }
      
    $step = $workflow[$fixmeNextStepKey] ?? NULL;
    if (empty($workflow)) {
      die('fixme: workflow not found; should return 404');
    }
    if (empty($step)) {
      die('fixme: step not found in this workflow; should return 404');
    }
    
    $params = [
      'sw' => $workflowId,
      'ss' => $fixmeNextStepKey,
    ];
    // fixme: this won't be the correct redirect
    $redirect = CRM_Stepw_Utils_Userparams::appendParamsToUrl($params, $step['url']);
    die('fixme: probably need to fix this redirect url: '. $redirect);
    CRM_Utils_System::redirect($redirect);
  }

}
