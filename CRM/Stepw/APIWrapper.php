<?php

/**
 * Description of CRM_Stepw_APIWrapper
 *
 * @author as
 */
class CRM_Stepw_APIWrapper {

  public static function RESPOND($event) {
    $requestSignature = $event->getApiRequestSig();

    // fixme: only act if we're sure we're in a Stepwise workflow (examine $_GET, probably?)
    // fixme: if we're going to pass state via $_GET, we MUST have some way (a hash maybe) 
    // to ensure query params like 'sw' as 'ss' are really ours (not coliding with some other extension)
    
    if ($requestSignature == "4.afform.get") {
      $responseValues = $event->getResponse();

      $configData = CRM_Stepw_Utils::getWorkflowConfig();
      
      // Track worfklowid and stepid via referrer, because the xhr request that
      // gets us to this point is not easily overloaded in order to put sw and ss 
      // into the post/get values. 
      // fixme: should add some kind of hashing as well, to make this more trustworty.
      parse_str(parse_url($_SERVER['HTTP_REFERER'], PHP_URL_QUERY), $queryParams);
      $workflowId = $queryParams['sw'];
      $stepId = $queryParams['ss'];
      
      // determine redirect.
      // fixme: need a solid way to handle the final page.
      $workflow = $configData[$workflowId];
      $nextStepId = $stepId + 1;
      // fixme: refactor this into a util function, once we're decided on using query params to track state.
      $redirect = CRM_Utils_System::url('/civicrm/stepwise', ['sw' => $workflowId, 'ss' => $nextStepId], TRUE, NULL, FALSE);
      
      foreach ($responseValues as &$responseValue) {
        $responseValue['redirect'] = urldecode($redirect);
      }
      $event->setResponse($responseValues);
    }
  }

}
