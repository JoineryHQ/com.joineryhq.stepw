<?php
use CRM_Stepw_ExtensionUtil as E;

class CRM_Stepw_Page_Reload extends CRM_Core_Page {

  public function run() {
    
    // fixmeval: validate page reload (request):
    //  - Given WI exists in state
    //  - Given Step is closed in WI
    //  -- WHAT ACTION TO TAKE ON VALIDATION FAILURE?
    
    $workflowInstancePublicId = CRM_Stepw_Utils_Userparams::getUserParams('request', CRM_Stepw_Utils_Userparams::QP_WORKFLOW_INSTANCE_ID);
    $stepPublicId = CRM_Stepw_Utils_Userparams::getUserParams('request', CRM_Stepw_Utils_Userparams::QP_STEP_ID);    

    $workflowInstance = CRM_Stepw_State::singleton()->getWorkflowInstance($workflowInstancePublicId);
    $stepNumber = $workflowInstance->getStepNumberByPublicId($stepPublicId);
    
    // Open step in workflowInstance.
    $workflowInstance->openStepNumber($stepNumber);
 
    $redirectQueryParams = [
      CRM_Stepw_Utils_Userparams::QP_WORKFLOW_INSTANCE_ID => $workflowInstancePublicId,
    ];
    $redirect = CRM_Stepw_Utils_General::buildNextUrl($redirectQueryParams);
    
    CRM_Utils_System::redirect($redirect);
  }

}
