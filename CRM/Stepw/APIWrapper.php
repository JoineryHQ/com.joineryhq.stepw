<?php

/**
 * Description of CRM_Stepw_APIWrapper
 *
 * @author as
 */
class CRM_Stepw_APIWrapper {
  public static function PREPARE (Civi\API\Event\PrepareEvent $event) {
    $requestSignature = $event->getApiRequestSig();
    
    // fixme: this if block is for testing/debugging. remove.
    if (
      0
//      || $requestSignature == "4.afform.checkaccess"
//      || $requestSignature == "4.afform.get"
      || $requestSignature == "4.afform.prefill"
    ) {
      $g = $_GET;
      $p = $_POST;
      $r = $_REQUEST;
      $q = CRM_Utils_Request::retrieve('q', 'String', '');
      $request = $event->getApiRequest();      
      $requestParams = $request->getParams();
      $a = 1;
      
    }
    if ($requestSignature == "4.afform.submit") {
      if (!CRM_Stepw_Utils_Userparams::validateWorkflowInstanceStep('referer', FALSE)) {
        // Workflow params are invalid. Just return.
        return;
      }
      
      $request = $event->getApiRequest();
      $afform = $request->getParams();
      $afformName = ($afform['name'] ?? NULL);
      if (
        empty($afformName)
        // If this afform is not for the current workflow step, we'll take no action here.
        || !CRM_Stepw_Utils_Userparams::currentWorkflowStepIsForAfform('referer', $afformName)
      ) {
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
      if (!CRM_Stepw_Utils_Userparams::validateWorkflowInstanceStep('referer', FALSE)) {
        // Workflow params are invalid. Just return.
        return;
      }
      
      // fixme: what validation can we do here to check this submission is for an afform that matches the current workflow step?

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
      $g = $_GET;
      $p = $_POST;
      $r = $_REQUEST;
      $q = CRM_Utils_Request::retrieve('q', 'String', '');
      $response = $event->getResponse();
      $request = $event->getApiRequest();      
      $requestParams = $request->getParams();
      
      $setpwReferer = CRM_Stepw_Utils_Userparams::getUserParams('referer');
      $setpwRequest = CRM_Stepw_Utils_Userparams::getUserParams('request');
      
      // fixme: validation:
      //  - only operate on the afform that is for the current workflow step 
      //  - workflow instance and step public ids must be valid in state.
      //  - this afform must have 'afform_prefill_individual' = TRUE in workflow step config.
      $workflowStepAfformName = 'afformTestForm3Activity2';
      foreach ($response as &$afform) {
        if ($afform['name'] == $workflowStepAfformName) {
          if (is_array($afform['layout'])) {
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
      }
      // Update the api response with our modified values.
      $event->setResponse($response);
    }

    // fixme: is this necessary, e.g. for prefill?
//    if ($requestSignature == "4.afform.checkaccess") {
//      $response = $event->getResponse();
//      if ($response->rowCount == 1) {
//        $response[0]['access'] = TRUE;
//        $event->setResponse($response);
//      }
//    }
    
    if ($requestSignature == "4.afformsubmission.create") {
      if (!CRM_Stepw_Utils_Userparams::validateWorkflowInstanceStep('referer', FALSE)) {
        // Workflow params are invalid. Just return.
        return;
      }
      
      $request = $event->getApiRequest();
      $requestParams = $request->getParams();
      $afformName = ($requestParams['values']['afform_name'] ?? NULL);
      if (
        empty($afformName)
        // If this afform is not for the current workflow step, we'll take no action here.
        || !CRM_Stepw_Utils_Userparams::currentWorkflowStepIsForAfform('referer', $afformName)
      ) {
        return;
      }

      // Capture saved submission id in the step.
      $response = $event->getResponse();
      $afformSubmissionId = $response[0]['id'];
      $stepPublicId = CRM_Stepw_Utils_Userparams::getUserParams('referer', CRM_Stepw_Utils_Userparams::QP_STEP_ID);
      $workflowInstancePublicId = CRM_Stepw_Utils_Userparams::getUserParams('referer', CRM_Stepw_Utils_Userparams::QP_WORKFLOW_INSTANCE_ID);
      $workflowInstance = CRM_Stepw_State::singleton()->getWorkflowInstance($workflowInstancePublicId);
      $workflowInstance->setStepSubmissionId($stepPublicId, $afformSubmissionId);
      
    }
  }

}
