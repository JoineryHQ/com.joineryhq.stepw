<?php

/**
 * Description of CRM_Stepw_APIWrapper
 * 
 * @author as
 */
class CRM_Stepw_APIWrapper {
  /**
   * API wrapper for 'prepare' events.
   * 
   * FLAG_STEPW_AFFORM_BRITTLE
   * 
   * @param Civi\API\Event\PrepareEvent $event
   */
  public static function PREPARE (Civi\API\Event\PrepareEvent $event) {
    
    $requestSignature = $event->getApiRequestSig();
    
    if ($requestSignature == "4.afform.submit") {      
      // Note: here we will:
      //  - alter api request parameters to allow re-saving of an existing afform submission.
      //  
      // fixme3val: validate afform.submit prepare (referer):
      //  - Given WI exists in state
      //  - Given Step exists in WI
      //  - QP_AFFORM_RELOAD_SID is given (i.e., this is a re-submission)
      //  - QP_AFFORM_RELOAD_SID matches the sid of the given Step 
      //  - Given step has 'ever been closed'
      //  - Given Step has already been associated with the given submission id.
      //  - Given Step is for this afform
      //  -- ON VALIDATION FAILURE: take no action; form submission will fail, and we don't care.
      //
      
      $request = $event->getApiRequest();
      $afform = $request->getParams();
      $afformName = ($afform['name'] ?? NULL);
      
      // Allow saving of afforms loaded with the ?sid=n query parameter (i.e.,
      // afforms preloaded with a given afform.submission), by stripping the
      // submission id from request args, but only on certain conditions.
      // (Note that this will facilitate OVERWRITING of existing entities
      // that were created by the original submission.)
      if (CRM_Stepw_Utils_Userparams::getUserParams('referer', CRM_Stepw_Utils_Userparams::QP_AFFORM_RELOAD_SID)) {
        $args = $event->getApiRequest()->getArgs();
        // We're modifying the afform entity just before submission processing.
        // That submission processing demands that args['sid'] must be empty,
        // because afform does not support re-saving of submissions that have
        // already been processed.
        //   Reference https://github.com/civicrm/civicrm-core/blob/5.81.0/ext/afform/core/Civi/Api4/Action/Afform/Submit.php#L41
        // FLAG_STEPW_AFFORM_BRITTLE : afform could decide to use some other means
        //  to prevent re-saving of already-processed submissisons.
        //
        unset($args['sid']);
        $event->getApiRequest()->setArgs($args);
      }
    }
  }
  
  /**
   * API wrapper for 'respond' events.
   * 
   * FLAG_STEPW_AFFORM_BRITTLE
   * 
   * @param Civi\API\Event\RespondEvent $event
   */
  public static function RESPOND(Civi\API\Event\RespondEvent $event) {
    $requestSignature = $event->getApiRequestSig();
    if ($requestSignature == "4.afform.get") {
      // fixme3 note: here we will:
      // - modify the afform html as we would in alterAngular(), so that the afform
      //   supports auto-fill of Individual1, even if it is not so configured.
      // 
      // fixme3: If is not stepwise workflow: return.
      // 
      // fixme3val: validate afform.get respond (referer):
      //  - Given WI exists in state
      //  - Given Step exists in WI 
      //  - Given Step is for this afform
      //  - We're on the prefil ajax call ($q == "civicrm/ajax/api4/Afform/prefill"; I've verified this is the way.)
      //    FLAG_STEPW_AFFORM_BRITTLE : afform may decide to skip hook_civicrm_alterAngluar() in execution flows other than prefill,
      //      in which case this step will need to support those execution flows too.
      //    Only in prefill do we care about this. Otherwise, hook_civicrm_alterAngluar()
      //    is modifying the form as we need; but in prefill, that hook has no effect.
      //  -- ON VALIDATION FAILURE: do nothing and return (this is an api call, possibly by ajax)
      //
      
      $g = $_GET;
      $p = $_POST;
      $r = $_REQUEST;
      $uri = $_SERVER['REQUEST_URI'];
      $q = CRM_Utils_Request::retrieve('q', 'String', '');
      $qgqv = get_query_var('q');
      
      $response = $event->getResponse();
      $request = $event->getApiRequest();      
      $requestParams = $request->getParams();
      
      $setpwReferer = CRM_Stepw_Utils_Userparams::getUserParams('referer');
      $setpwRequest = CRM_Stepw_Utils_Userparams::getUserParams('request');
      
      foreach ($response as &$afform) {
        if (is_array($afform['layout'] ?? NULL)) {
          // layout is now a deeply nested array, which is very hard to search and 
          // alter manually. So convert it to html and then to a phpQueryObject,
          // so we can easily search and modify elements therein.
          $converter = new \CRM_Afform_ArrayHtml(TRUE);
          $htmlLayout = $converter->convertArraysToHtml($afform['layout']);
          $docLayout = \phpQuery::newDocument($htmlLayout, 'text/html');
          // Modify the layout via phpQueryObject as needed.
          CRM_Stepw_Utils_Afform::alterForm($docLayout);
          // Convert phpQueryObject layout back to html.
          $coder = new \Civi\Angular\Coder();
          $newHtmlLayout = $coder->encode($docLayout);
          // Convert html layout back to deeply nested array.
          $afform['layout'] = $converter->convertHtmlToArray($newHtmlLayout);
        }
      }
      // Update the api response with our modified values.
      $event->setResponse($response);
    }    
    elseif ($requestSignature == "4.afformsubmission.create") {
      // fixme3 note: here we will:
      // - Capture saved submission id in the step in workflowInstance.
      // 
      // fixme3: If is not stepwise workflow: return.
      // 
      // fixme3val: validate afform.get respond (referer):
      //  - Given WI exists in state
      //  - Given Step exists in WI 
      //  - Given Step is for this afform
      //  -- ON VALIDATION FAILURE: do nothing and return (this is an api call, possibly by ajax)
      //
      
      $request = $event->getApiRequest();
      $requestParams = $request->getParams();
      $afformName = ($requestParams['values']['afform_name'] ?? NULL);

      // Capture saved submission id in the step.
      $response = $event->getResponse();
      $afformSubmissionId = $response[0]['id'];
      $stepPublicId = CRM_Stepw_Utils_Userparams::getUserParams('referer', CRM_Stepw_Utils_Userparams::QP_STEP_ID);
      $workflowInstancePublicId = CRM_Stepw_Utils_Userparams::getUserParams('referer', CRM_Stepw_Utils_Userparams::QP_WORKFLOW_INSTANCE_ID);
      $workflowInstance = CRM_Stepw_State::singleton()->getWorkflowInstance($workflowInstancePublicId);
      $workflowInstance->setStepAfformSubmissionId($afformSubmissionId, $stepPublicId);
      
    }
  }

}
