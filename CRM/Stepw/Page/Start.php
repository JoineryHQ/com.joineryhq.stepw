<?php
use CRM_Stepw_ExtensionUtil as E;

class CRM_Stepw_Page_Start extends CRM_Core_Page {

  public function run() {
    $workflowId = CRM_Stepw_Utils_Userparams::getStartWorkflowid();
    if (!$workflowId) {
      throw new CRM_Extension_Exception('Missing required parameter: '. CRM_Stepw_Utils_Userparams::QP_START_WORKFLOW_ID);
    }
    
    // Initialize this workflow instance.
    $workflowinstance = CRM_Stepw_WorkflowInstance::singleton($workflowId);
    $workflowinstance->initialize();
    
    // Get the data for this workflow.
    $workflow = CRM_Stepw_Utils_WorkflowData::getWorkflowConfigById($workflowId);
    if (empty($workflow)) {
      throw new CRM_Extension_Exception('Unidentified workflow requested.', 'stepw_unknown_workflow_id', ['requested id' => $workflowId]);
    }
    // At start, always use the first step:
    $stepId = 1;
    $step = $workflow[$stepId];
    
    // Open step 1 in workflowInstance.
    $stepPublicId = $workflowinstance->openStep($stepId);

    // Append parameters to step url and redirect thence.
    $params = [
      's' => $stepPublicId,
      'i' => $workflowinstance->getVar('publicId'),
    ];
    $redirect = CRM_Stepw_Utils_Userparams::appendParamsToUrl($params, $step['url']);
    CRM_Utils_System::redirect($redirect);

  }

}
