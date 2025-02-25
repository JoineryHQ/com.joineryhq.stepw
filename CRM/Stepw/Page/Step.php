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
  // note:val: validate page:step if done_step_id
  //  - given WI exists in state
  //  - given done_step_id exists in WI
  //  -- VALIDATION FAILURE: redirect to invalid
  //

    parent::run();

    // If we're given 'start_workflow_id', initialize a workflow instance and used this workflowInstance
    $startWorkflowId = CRM_Stepw_Utils_Userparams::getUserParams('request', CRM_Stepw_Utils_Userparams::QP_START_WORKFLOW_ID);
    if ($startWorkflowId) {
      try {
        $workflowInstance = new CRM_Stepw_WorkflowInstance($startWorkflowId);      
      }
      catch (CRM_Extension_Exception $e) {
        CRM_Stepw_Utils_General::redirectToInvalid($e->getMessage());
      }
    }
    // Otherwise, get workflowintance based on given 'stepw_wiid' param
    else {
      $workflowInstancePublicId = CRM_Stepw_Utils_Userparams::getUserParams('request', CRM_Stepw_Utils_Userparams::QP_WORKFLOW_INSTANCE_ID);
      $workflowInstance = CRM_Stepw_State::singleton()->getWorkflowInstance($workflowInstancePublicId);
    }
    
    // If we're given a done_step_id, mark that step as completed in workflowInstance.
    $doneStepPublicId = CRM_Stepw_Utils_Userparams::getUserParams('request', CRM_Stepw_Utils_Userparams::QP_DONE_STEP_ID);    
    if ($doneStepPublicId) {
      // Validation: on failure we'll redirect to /invalid.
      // 
      // validate: fail if: Given WI or step don't exist in state.      
      if (!CRM_Stepw_Utils_Validation::isWorkflowInstanceAndStepValid($workflowInstancePublicId, $doneStepPublicId)) {
        CRM_Stepw_Utils_General::redirectToInvalid(__METHOD__ . ': Invalid publicId for workflowInstance and/or "done" step.');
      }
      
      // Complete the step.
      $workflowInstance->completeStep($doneStepPublicId);
    }
    
    $nextStepUrl = $workflowInstance->getNextStepUrl();
    CRM_Utils_System::redirect($nextStepUrl);
    
  }

}
