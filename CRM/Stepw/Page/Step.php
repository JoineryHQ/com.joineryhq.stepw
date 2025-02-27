<?php
use CRM_Stepw_ExtensionUtil as E;

class CRM_Stepw_Page_Step extends CRM_Core_Page {

  public function run() {
  // note: here we will:
  // - If we're given 'start_workflow_id', 
  //   - initialize a workflow instance and use this $wi
  // - else
  //   - get workflowintance $wi based on given 'stepw_wiid'
  // - if we're given step_done_step_id:
  //   - timestamp step completed in $wi
  // - get $wi next step url, and redirect thence.
  // 
  // note:val: none; WI and Step classes will throw exceptions on invalid publicIds
  //

    parent::run();

    // If we're given 'start_workflow_id', initialize a workflow instance and use this workflowInstance
    $startWorkflowId = CRM_Stepw_Utils_Userparams::getUserParams('request', CRM_Stepw_Utils_Userparams::QP_START_WORKFLOW_ID);
    if ($startWorkflowId) {
      $workflowInstance = new CRM_Stepw_WorkflowInstance($startWorkflowId);      
    }
    // Otherwise, get workflowintance based on given 'stepw_wiid' param
    else {
      $workflowInstancePublicId = CRM_Stepw_Utils_Userparams::getUserParams('request', CRM_Stepw_Utils_Userparams::QP_WORKFLOW_INSTANCE_ID);
      $workflowInstance = CRM_Stepw_State::singleton()->getWorkflowInstance($workflowInstancePublicId);
    }
    
    // If we're given a done_step_id, mark that step as completed in workflowInstance.
    $doneStepPublicId = CRM_Stepw_Utils_Userparams::getUserParams('request', CRM_Stepw_Utils_Userparams::QP_DONE_STEP_ID);    
    if ($doneStepPublicId) {
      // Complete the step.
      $workflowInstance->completeStep($doneStepPublicId);
    }
    
    $nextStepUrl = $workflowInstance->getNextStepUrl();
    CRM_Utils_System::redirect($nextStepUrl);
    
  }

}
