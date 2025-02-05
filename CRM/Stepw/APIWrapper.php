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
      // Allow saving of afforms loaded with the ?sid=n query parameter (i.e.,
      // afforms preloaded with a given afform.submission), by stripping the
      // submission id from request args, but only on certain conditions.
      // (Note that this will facilitate OVERWRITING of existing entities
      // that were created by the original submission.)
      if (CRM_Stepw_Utils_Userparams::getRefererQueryParams('stepwisereload')) {
        $args = $event->getApiRequest()->getArgs();
        unset($args['sid']);
        $event->getApiRequest()->setArgs($args);
      }
    }
    elseif ($requestSignature == "4.afformsubmission.get") {
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
      // Alter afform 'redirect' property so it goes to our stepwise page handler.
      // fixme: only act if we're sure we're in a Stepwise workflow (examine $_GET, probably?)
      // fixme: if we're going to pass state via $_GET, we MUST have some way (a hash maybe) 
      // to ensure query params like 'sw' as 'ss' are really ours (not coliding with some other extension)

      // Get stepwise configuration.
      
      // Track worfklowid and stepid via referrer, because the xhr request that
      // gets us to this point is not easily overloaded in order to put sw and ss 
      // into the post/get values. 
      // fixme: should add some kind of hashing as well, to make this more trustworty.
      $queryParams = CRM_Stepw_Utils_Userparams::getRefererQueryParams();
      $workflowId = $queryParams['sw'];
      $stepId = $queryParams['ss'];
      
      if (!empty($workflowId)) {
        // determine redirect.
        // fixme: need a solid way to handle the final page.
        $workflow = CRM_Stepw_Utils_WorkflowData::getWorkflowConfigById($workflowId);
        $nextStepId = $stepId + 1;
        // fixme: refactor this into a util function, once we're decided on using query params to track state.
        $redirect = CRM_Utils_System::url('civicrm/stepwise', ['sw' => $workflowId, 'ss' => $nextStepId], TRUE, NULL, FALSE);

        $responseValues = $event->getResponse();
        foreach ($responseValues as &$responseValue) {
          $responseValue['redirect'] = urldecode($redirect);
        }
        $event->setResponse($responseValues);
      }
    }
  }

}
