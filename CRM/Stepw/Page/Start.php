<?php
use CRM_Stepw_ExtensionUtil as E;

class CRM_Stepw_Page_Start extends CRM_Core_Page {

  public function run() {
    $workflowId = CRM_Stepw_Utils_Userparams::getStartWorkflowid();
    if (!$workflowId) {
      throw new CRM_Extension_Exception('Missing required parameter: '. CRM_Stepw_Utils_Userparams::QP_START_WORKFLOW_ID);
    }
    
    // Initialize this workflow instance.
    $workflowInstance = CRM_Stepw_WorkflowInstance::singleton($workflowId);
    $workflowInstance->initialize();
    
    // Get the data for this workflow.
    $workflow = CRM_Stepw_Utils_WorkflowData::getWorkflowConfigById($workflowId);
    if (empty($workflow)) {
      throw new CRM_Extension_Exception('Unidentified workflow requested.', 'stepw_unknown_workflow_id', ['requested id' => $workflowId]);
    }
    
    // At start, all steps will of course be nonexistent (thus un-closed), so
    // getting the "next step" is sufficient.
    $workflowInstanceNextStep = $workflowInstance->getNextStep();

    // Open step in workflowInstance.
    $stepPublicId = $workflowInstance->openStep($workflowInstanceNextStep['stepId']);

    // Append parameters to step url and redirect thence.
    $params = [
      's' => $stepPublicId,
      'i' => $workflowInstance->getVar('publicId'),
    ];
    $redirect = CRM_Stepw_Utils_Userparams::appendParamsToUrl($params, $workflowInstanceNextStep['url']);
    
    CRM_Utils_System::redirect($redirect);

  }

}
