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
      // note: Ignore and return if any of:
      // - we're not in a stepwise workflow
      // - QP_AFFORM_RELOAD_SID is not given (i.e., this is a re-submission)
      // 
      //
      // note:val: validate afform.submit prepare (referer):
      //  - QP_AFFORM_RELOAD_SID matches the sid of the given Step 
      //  - Given Step has already been associated with the given submission id.
      //  - Given Step is for this afform
      //  -- ON VALIDATION FAILURE: throw an exception    
      
      if (!CRM_Stepw_Utils_Userparams::isStepwiseWorkflow('referer')) {
        // We're not in a workflowInstance (per referer), so there's nothing for us to do here.
        return;
      }
      
      $reloadSubmissionId = CRM_Stepw_Utils_Userparams::getUserParams('referer', CRM_Stepw_Utils_Userparams::QP_AFFORM_RELOAD_SID);
      if (empty($reloadSubmissionId)) {
        // afformsubmission.sid is not provided (in referer QP_AFFORM_RELOAD_SID);
        // there's nothing for us to do here.
        return;
      }
      
      // Validation: on failure we'll throw an exception. Clearly somebody is mucking with params.
      //
      // validate: fail if: afformsubmission.sid is not an sid already saved for this step.
      $workflowInstancePublicId = CRM_Stepw_Utils_Userparams::getUserParams('referer', CRM_Stepw_Utils_Userparams::QP_WORKFLOW_INSTANCE_ID);
      $stepPublicId = CRM_Stepw_Utils_Userparams::getUserParams('referer', CRM_Stepw_Utils_Userparams::QP_STEP_ID);
      if (!CRM_Stepw_Utils_Validation::stepHasAfformSubmissionId($workflowInstancePublicId, $stepPublicId, $reloadSubmissionId) ){
        throw new  CRM_Stepw_Exception("Provided afform submission sid does not match existing sid in step, in " . __METHOD__, 'CRM_Stepw_APIWrapper_PREPARE_4.afform.submit_mismatch-submission-id');
      }
      
      // validate: fail if: Given Step is not for this afform.
      $request = $event->getApiRequest();
      $afform = $request->getParams();
      $afformName = ($afform['name'] ?? NULL);
      if (!CRM_Stepw_Utils_Validation::stepIsForAfformName($workflowInstancePublicId, $stepPublicId, $afformName)) {
        throw new  CRM_Stepw_Exception("Referenced step is not for this affrom '$afformName', in " . __METHOD__, 'CRM_Stepw_APIWrapper_PREPARE_4.afform.submit_mismatch-afform');
      }
      
      // Allow saving of afforms loaded with the ?sid=n query parameter (i.e.,
      // afforms preloaded with a given afform.submission), by stripping the
      // submission id from request args, but only on certain conditions.
      // (Note that this will facilitate OVERWRITING of existing entities
      // that were created by the original submission.)
      $args = $request->getArgs();
      // We're modifying the afform entity just before submission processing.
      // That submission processing demands that args['sid'] must be empty,
      // because afform does not support re-saving of submissions that have
      // already been processed.
      // FLAG_STEPW_AFFORM_BRITTLE : afform could decide to use some other means
      //  to prevent re-saving of already-processed submissisons.
      //  Reference https://github.com/civicrm/civicrm-core/blob/5.81.0/ext/afform/core/Civi/Api4/Action/Afform/Submit.php#L41
      //
      unset($args['sid']);
      $event->getApiRequest()->setArgs($args);
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
      // note: here we will:
      // - modify the afform html as we would in alterAngular(), so that the afform
      //   supports auto-fill of Individual1, even if it is not so configured.
      // 
      // note: Ignore and return if any of:
      // - we're not in a stepwise workflow
      // - We're not on the prefil ajax call ($q == "civicrm/ajax/api4/Afform/prefill"; I've verified this is the way.)
      // 
      // note:val: validate afform.get respond (referer):
      //  - Given Step is for this afform
      //  - 
      //  -- ON VALIDATION FAILURE: throw an exception
      //

      if (!CRM_Stepw_Utils_Userparams::isStepwiseWorkflow('referer')) {
        // We're not in a workflowInstance (per referer), so there's nothing for us to do here.
        return;
      }
      
      $q = CRM_Utils_Request::retrieve('q', 'String', '');
      if ($q != 'civicrm/ajax/api4/Afform/prefill') {
        // This is not an afform prefill operation, so there's nothing for us to do here.
        // Only in prefill do we care about this. Otherwise, hook_civicrm_alterAngluar()
        // is modifying the form as we need; but in prefill, that hook has no effect.
        // 
        // FLAG_STEPW_AFFORM_BRITTLE : afform may decide to skip hook_civicrm_alterAngluar() in execution flows other than prefill,
        //   in which case this section will need to support those execution flows too.
        return;
      }

      $response = $event->getResponse();
      // In prefill for a form, there should be exactly one response, and it should be an array of properties for one afform.
      $afformProperties = $response[0];
      if (($afformProperties['type'] ?? NULL) != 'form') {
        // Prefill is sometimes called on afform blocks, but we only care if it's a form.
        // If it's not a form, we have nothing to do here.
        return;
      }      
      
      // Validation: on failure we'll throw an exception. Clearly somebody is mucking with params.
      // 
      // validate: fail if: Given Step is not for this afform.
      $workflowInstancePublicId = CRM_Stepw_Utils_Userparams::getUserParams('referer', CRM_Stepw_Utils_Userparams::QP_WORKFLOW_INSTANCE_ID);
      $stepPublicId = CRM_Stepw_Utils_Userparams::getUserParams('referer', CRM_Stepw_Utils_Userparams::QP_STEP_ID);
      $afformName = $afformProperties['name'];
      if (!CRM_Stepw_Utils_Validation::stepIsForAfformName($workflowInstancePublicId, $stepPublicId, $afformName)) {
        throw new  CRM_Stepw_Exception("Referenced step is not for this affrom '$afformName', in " . __METHOD__, 'CRM_Stepw_APIWrapper_RESPOND_4.afform.get_mismatch-afform');
      }
      
      // layout is now a deeply nested array, which is very hard to search and 
      // alter manually. So convert it to html and then to a phpQueryObject,
      // so we can easily search and modify elements therein.
      $converter = new \CRM_Afform_ArrayHtml(TRUE);
      $htmlLayout = $converter->convertArraysToHtml($afformProperties['layout']);
      $docLayout = \phpQuery::newDocument($htmlLayout, 'text/html');
      // Modify the layout via phpQueryObject as needed.
      CRM_Stepw_Utils_Afform::alterForm($docLayout);
      // Convert phpQueryObject layout back to html.
      $coder = new \Civi\Angular\Coder();
      $newHtmlLayout = $coder->encode($docLayout);
      // Convert html layout back to deeply nested array.
      $afformProperties['layout'] = $converter->convertHtmlToArray($newHtmlLayout);
      
      // Update the api response with our modified values.
      $event->setResponse($response);
    }    
    elseif ($requestSignature == "4.afformsubmission.create") {
      // note: here we will:
      // - Capture saved submission id in the step in workflowInstance.
      // 
      // note: ignore and return if any of:
      // - we're not in a stepwise workflow
      // 
      // note:val: validate afform.get respond (referer):
      //  - Given Step is for this afform
      //  -- ON VALIDATION FAILURE: do nothing and return (this is an api call, possibly by ajax)
      //

      if (!CRM_Stepw_Utils_Userparams::isStepwiseWorkflow('referer')) {
        // We're not in a workflowInstance (per referer), so there's nothing for us to do here.
        return;
      }
      
      // Validation: on failure we'll throw an exception. Clearly somebody is mucking with params.
      //
      // validate: fail if: Given Step is not for this afform.
      $workflowInstancePublicId = CRM_Stepw_Utils_Userparams::getUserParams('referer', CRM_Stepw_Utils_Userparams::QP_WORKFLOW_INSTANCE_ID);
      $stepPublicId = CRM_Stepw_Utils_Userparams::getUserParams('referer', CRM_Stepw_Utils_Userparams::QP_STEP_ID);
      $request = $event->getApiRequest();
      $requestParams = $request->getParams();
      $afformName = ($requestParams['values']['afform_name'] ?? NULL);
      if (!CRM_Stepw_Utils_Validation::stepIsForAfformName($workflowInstancePublicId, $stepPublicId, $afformName)) {
        throw new  CRM_Stepw_Exception("Referenced step is not for this affrom '$afformName', in " . __METHOD__, 'CRM_Stepw_APIWrapper_RESPOND_4.afformsubmission.create_mismatch-afform');
      }

      // Capture saved submission id in the step.
      $response = $event->getResponse();
      $afformSubmissionId = $response[0]['id'];
      $workflowInstance = CRM_Stepw_State::singleton()->getWorkflowInstance($workflowInstancePublicId);
      $workflowInstance->setStepAfformSubmissionId($afformSubmissionId, $stepPublicId);
      
    }
  }

}
