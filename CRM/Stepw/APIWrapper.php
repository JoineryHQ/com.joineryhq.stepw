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

    if ($requestSignature == "4.afform.get") {
      if (!CRM_Stepw_Utils_General::isStepwiseWorkflow('referer')) {
        // We're not in a workflowInstance, so there's nothing for us to do here.
        return;
      }  

      // Alter afform 'redirect' property so it goes to our stepwise page handler,
      // but only if we're in a stepwise workflowIntance and the current step
      // is for this afform.
      $response = $event->getResponse();
      $responseLength = count($response);
      if ($responseLength == 1) {
        $afform = &$response[0];
        $afformName = ($afform['name'] ?? NULL);
        $afformHasRedirectProperty = array_key_exists('redirect', $afform);
        if (
          // If we don't have a name, we can't do anything
          !empty($afformName)
          // Only if this api call would be fetching the 'redirect' property
          && $afformHasRedirectProperty
        ) {
          $isValid = _stepw_alterAfformHtml_validate('name', $afformName);
          if ($isValid) {
            $workflowInstancePublicId = CRM_Stepw_Utils_Userparams::getUserParams('referer', CRM_Stepw_Utils_Userparams::QP_WORKFLOW_INSTANCE_ID);
            $redirectParams = [
              CRM_Stepw_Utils_Userparams::QP_WORKFLOW_INSTANCE_ID => $workflowInstancePublicId,
            ];
            $redirect = CRM_Utils_System::url('civicrm/stepwise/next', $redirectParams, TRUE, NULL, FALSE);
            $afform['redirect'] = $redirect;
            $event->setResponse($response);        
            $a = 1;

            // fixme: need a solid way to handle the final page.

          }
        }
      }
    }
    elseif ($requestSignature == "4.afformsubmission.create") {
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
