<?php
use CRM_Stepw_ExtensionUtil as E;

class CRM_Stepw_Page_Start extends CRM_Core_Page {
  
  public function run() {
    
    parent::run();
    
    $workflowId = CRM_Stepw_Utils_Userparams::getStartWorkflowid();
    if (!$workflowId) {
      CRM_Stepw_Utils_General::redirectToInvalid('Missing required parameter: '. CRM_Stepw_Utils_Userparams::QP_START_WORKFLOW_ID);
    }
    
    // Initialize this workflow instance.
    $workflowInstance = new CRM_Stepw_WorkflowInstance($workflowId);
    
    // Get the data for this workflow.
    $workflowConfig = CRM_Stepw_Utils_WorkflowData::getWorkflowConfigById($workflowId);
    if (empty($workflowConfig)) {
      CRM_Stepw_Utils_General::redirectToInvalid('Unidentified workflow requested.');
    }
    
    // At start, all steps will of course be nonexistent (thus un-closed), so
    // getting the "next step" is sufficient.
    $workflowInstanceNextStep = $workflowInstance->getNextStep();

    // Open step in workflowInstance.
    $stepPublicId = $workflowInstance->openStep($workflowInstanceNextStep['stepId']);

    // Append parameters to step url and redirect thence.
    $params = [
      'i' => $workflowInstance->getVar('publicId'),
      's' => $stepPublicId,
    ];
    $redirect = CRM_Stepw_Utils_Userparams::appendParamsToUrl($workflowInstanceNextStep['url'], $params);
    
    CRM_Utils_System::redirect($redirect);

  }

}
