<?php
use CRM_Stepw_ExtensionUtil as E;

class CRM_Stepw_Page_Step extends CRM_Core_Page {

  public function run() {
  // note: here we will:
  // - If we're given 'start_workflow_id',
  //   - ensure the relevant workflow is inabled, or exit with a message.
  //   - initialize a workflow instance and use that $wi
  // - else
  //   - get workflowintance $wi based on given 'stepw_wiid'
  // - if we're given step_done_step_id:
  //   - timestamp step completed in $wi
  // - get $wi first uncompleted step url, and redirect thence.
  //
  // note:val: none; WI and Step classes will throw exceptions on invalid publicIds
  //

    parent::run();

    // If we're given 'start_workflow_id', initialize a workflow instance and use this workflowInstance
    $startWorkflowId = CRM_Stepw_Utils_Userparams::getUserParams('request', CRM_Stepw_Utils_Userparams::QP_START_WORKFLOW_ID);
    if ($startWorkflowId) {
      $this->ensureEnabledOrError($startWorkflowId);
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

      // If we're also given a subsequentStepOptionid, update that step to use the given option.
      // (This is useless without $doneStepPublicId.)
      $subsequentStepOptionId = CRM_Stepw_Utils_Userparams::getUserParams('request', CRM_Stepw_Utils_Userparams::QP_SUBSEQUENT_STEP_SELECTED_OPTION_ID);
      if ($subsequentStepOptionId) {
        $workflowInstance->setSubsequentStepOptionId($doneStepPublicId, $subsequentStepOptionId);
      }
    }

    $errors = [];
    if(!$workflowInstance->validateLastCompletedStep($errors)) {
      // We've presumably just completed one step, so we should perform any input
      // validation of that step, here. On failure, redirect to ValidationError page.
      CRM_Stepw_Utils_General::redirectToValidationError($errors);
    }

    $firstUncompletedStepUrl = $workflowInstance->getFirstUncompletedStepUrl();
    CRM_Utils_System::redirect($firstUncompletedStepUrl);

  }

  /**
   * Ensure an enabled workflow with the given publicId exists; otherwise, display
   * and "invalid" message.
   *
   * @param String $workflowPublicId
   */
  private static function ensureEnabledOrError($workflowPublicId) {
    $workflowCount = \Civi\Api4\StepwWorkflow::get()
      ->setCheckPermissions(FALSE)
      ->addWhere('is_active', '=', TRUE)
      ->addWhere('public_id', '=', $workflowPublicId)
      ->execute()
      ->count();
    if (!$workflowCount) {
      // Todo: Manually calling storePublicErrorMessage() feels sloppy, but this works for now.
      CRM_Stepw_State::singleton()->storePublicErrorMessage(E::ts('Sorry, the content you have requested is not available'));
      $redirect = CRM_Utils_System::url('civicrm/stepwise/invalid', '', TRUE, NULL, FALSE);
      CRM_Utils_System::redirect($redirect);
    }
  }

}
