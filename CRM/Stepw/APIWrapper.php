<?php

/**
 * Description of CRM_Stepw_APIWrapper
 *
 * @author as
 */
class CRM_Stepw_APIWrapper {
  public static function PREPARE (Civi\API\Event\PrepareEvent $event) {
    $requestSignature = $event->getApiRequestSig();
    if ($requestSignature == "4.afform.submit") {
      if (!CRM_Stepw_Utils_General::isStepwiseWorkflow('referer')) {
        // We're not in a workflowInstance, so there's nothing for us to do here.
        return;
      }  
      // Allow saving of afforms loaded with the ?sid=n query parameter (i.e.,
      // afforms preloaded with a given afform.submission), by stripping the
      // submission id from request args, but only on certain conditions.
      // (Note that this will facilitate OVERWRITING of existing entities
      // that were created by the original submission.)
      if (CRM_Stepw_Utils_Userparams::getUserParams('referer', 'stepwisereload')) {
        $args = $event->getApiRequest()->getArgs();
        unset($args['sid']);
        $event->getApiRequest()->setArgs($args);
      }
    }
    elseif ($requestSignature == "4.afformsubmission.get") {
      if (!CRM_Stepw_Utils_General::isStepwiseWorkflow('referer')) {
        // We're not in a workflowInstance, so there's nothing for us to do here.
        return;
      }  
      // fixme: we can display prefilled form submission with a url like 'http://plana.l/civicrm/form-test/#?sid=7'
      // IF WE DO OUR OWN PERMISSION CHECKING AND DISABLE THIS REQUEST'S PERMISSIONS HERE:
      // AND IF WE GRANT (MOMENTARILY) 'AFFORM: EDIT AND DELETE FORMS' PERMISSION (FOR THIS, CONSIDER OUR PR TO SMS API EXTENSION)
//      $event->getApiRequest()->setCheckPermissions(false);
      
//      parse_str(parse_url($_SERVER['HTTP_REFERER'], PHP_URL_QUERY), $queryParams);
//      $r = \Civi\Api4\AfformSubmission::get(FALSE);
//      $apiParams = $event->getApiRequest()->getParams();
//      foreach($apiParams['where'] as $where) {
//        call_user_func([$r, 'addWhere'], $where);
//      }
//      $event->setApiRequest($r);
      
//      $a = 1;
    }
  }
  
  public static function RESPOND(Civi\API\Event\RespondEvent $event) {
    $requestSignature = $event->getApiRequestSig();

    if ($requestSignature == "4.afformsubmission.create") {
      if (!CRM_Stepw_Utils_General::isStepwiseWorkflow('referer')) {
        // We're not in a workflowInstance, so there's nothing for us to do here.
        return;
      }  
      // trying to capture saved submission id
      $response = $event->getResponse();
      $afformSubmissionId = $response[0]['id'];
      $stepPublicId = CRM_Stepw_Utils_Userparams::getUserParams('referer', CRM_Stepw_Utils_Userparams::QP_STEP_ID);
      $workflowInstancePublicId = CRM_Stepw_Utils_Userparams::getUserParams('referer', CRM_Stepw_Utils_Userparams::QP_WORKFLOW_INSTANCE_ID);
      $workflowInstance = CRM_Stepw_State::singleton()->getWorkflowInstance($workflowInstancePublicId);
      $workflowInstance->setStepSubmissionId($stepPublicId, $afformSubmissionId);
      
    }
  }

}
