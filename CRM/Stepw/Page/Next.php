<?php
use CRM_Stepw_ExtensionUtil as E;

class CRM_Stepw_Page_Next extends CRM_Core_Page {

  private $workflowInstancePublicId;
  private $doneStepPublicId;
  private $workflowInstance;
  
  public function run() {

    $this->workflowInstancePublicId = CRM_Stepw_Utils_Userparams::getUrlQueryParams(CRM_Stepw_Utils_Userparams::QP_WORKFLOW_INSTANCE_ID);
    $this->workflowInstance = CRM_Stepw_State::singleton()->getWorkflowInstance($this->workflowInstancePublicId);
    $this->doneStepPublicId = CRM_Stepw_Utils_Userparams::getUrlQueryParams(CRM_Stepw_Utils_Userparams::QP_DONE_STEP_ID);    
        
    if (!$this->validate()) {
      CRM_Stepw_Utils_General::redirectToInvalid();
    }

    // fixme: if done step is video page, denote 'video watched for this step' in the workflow instance (this property won't be archived even if step is archived);

    if (!empty($this->doneStepPublicId)) {
      // ensure closure of done step (if given);
      $this->workflowInstance->closeStep($this->doneStepPublicId);
    }
    
    // fixme: determine last completed step (based only on closed steps); 
    // fixme: if last step was afform, apply any configured post-save validation for step;
    // fixme: if any post-save validation fails: redirect to post-save validation failure message;
    
    // determine next step (based only on closed steps);
    $workflowInstanceNextStep = $this->workflowInstance->getNextStep();

    // Open next step in workflowInstance.
    $stepPublicId = $this->workflowInstance->openStep($workflowInstanceNextStep['stepId']);

    // Append parameters to step url and redirect thence.
    $params = [
      'i' => $this->workflowInstance->getVar('publicId'),
      's' => $stepPublicId,
      // fixme: we must append afform params for existing contact_id if one was created
      // earlier in the workflow.
    ];
    $redirect = CRM_Stepw_Utils_Userparams::appendParamsToUrl($params, $workflowInstanceNextStep['url']);

    CRM_Utils_System::redirect($redirect);


  }
  
  private function validate() {
    $isValid = FALSE;
    
    if (!empty($this->workflowInstance)) {
      if (
        empty($this->doneStepPublicId)
        || CRM_Stepw_State::singleton()->validateWorkflowInstanceStep($this->doneStepPublicId)
      ) {
        $isValid = TRUE;
      }
    }
    return $isValid;
  }
}
