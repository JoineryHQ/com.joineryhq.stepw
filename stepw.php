<?php

require_once 'stepw.civix.php';

use CRM_Stepw_ExtensionUtil as E;

function stepw_civicrm_pageRun(CRM_Core_Page $page) {
  
  $pageName = $page->getVar('_name');
  if ($pageName == 'CRM_Afform_Page_AfformBase') {
    // fixme3 note: here we will:
    //  - If the given step has already been submitted, and we were NOT given an afform sid,
    //    redirect to $workflowInstance->getStepUrl(stepPublicId) (which should return
    //    a url with afform sid and some "reload" query parameter to indicate that fact (since afform
    //    only knows it from #fragment, which is not visible here.)
    //  - Set crm.vars for stepwAfform module.
    //

    // If is not stepwise workflow: return.
    if (!CRM_Stepw_Utils_Userparams::isStepwiseWorkflow('request')) {
      // We're not in a stepwise workflow. Nothing for us to do here.
      return;
    }

    // fixme3val: validate hook_pageRun.
    //  - Given WI exists in state
    //  - Given Step public id exists in WI
    //  - If given "reload" param:
    //      - "reload" param is for given step
    //      - step has an afform sid.
    //  - Given Step is for this afform ($q matches step['url'])
    //  -- VALIDATION FAILURE: redirect to invalid
    //
    
      $g = $_GET;
      $p = $_POST;
      $r = $_REQUEST;
      $q = CRM_Utils_Request::retrieve('q', 'String', '');
      $a = 1;
      $state = CRM_Stepw_State::singleton();

    $workflowInstancePublicId = CRM_Stepw_Utils_Userparams::getUserParams('request', CRM_Stepw_Utils_Userparams::QP_WORKFLOW_INSTANCE_ID);
    $workflowInstance = CRM_Stepw_State::singleton()->getWorkflowInstance($workflowInstancePublicId);
    $stepPublicId = CRM_Stepw_Utils_Userparams::getUserParams('request', CRM_Stepw_Utils_Userparams::QP_STEP_ID);
    

    // If the given step has already been submitted, and we were NOT given QP_AFFORM_RELOAD_SID (with the current sid of the step),
    // redirect to $workflowInstance->getStepUrl(stepPublicId). See notes on
    // $step->afformSid.
    // This redirection is part of our security/validation protocol, because it 
    // ensures that the value of QP_AFFORM_RELOAD_SID is corret, which is important
    // because that value is later checked by our validation process.
    //
    $reloadSubmissionId = CRM_Stepw_Utils_Userparams::getUserParams('request', CRM_Stepw_Utils_Userparams::QP_AFFORM_RELOAD_SID);
    $stepSubmissionId = $workflowInstance->getStepAfformSubmissionId($stepPublicId);
    if (
      $stepSubmissionId
      && ($reloadSubmissionId != $stepSubmissionId)
    ) {
      $stepReloadUrl = $workflowInstance->getStepUrl($stepPublicId);
      CRM_Utils_System::redirect($stepReloadUrl);
      
      $a = 1;
    }
      
    // Build redirect url to our step handler for this workflowInstance
    // Note: our step handler will, by the time it runs, know that this
    // form was the most recently submitted step, so it will redirect to the url
    // for the step subsequent to this one.
    //
    $redirectQueryParams = [
      CRM_Stepw_Utils_Userparams::QP_WORKFLOW_INSTANCE_ID => $workflowInstancePublicId,
    ];
    $redirectUrl = CRM_Stepw_Utils_General::buildStepUrl($redirectQueryParams);
    // Also send the button label to js.
    $buttonLabel = $workflowInstance->getStepButtonLabel($stepPublicId);
    $jsVars = [
      'submitButtonLabel' => $buttonLabel,
      'redirectUrl' => $redirectUrl,
      'stepAfformSid' => ($stepSubmissionId ?? NULL),
    ];
    CRM_Core_Resources::singleton()->addVars('stepw', $jsVars);

  }
}

function stepw_civicrm_alterContent(&$content, $context, $tplName, &$object) {
  // fixme3 note: here we will:
  // - display a progress bar above the afform
  //
  // fixme3: If is not stepwise workflow: return.
  // 
  // fixme3val: None. Validation was alrady done in hook_pageRun().
  //
  
  // fixme3: if this is an afform and we're in a workflowInstance, display a progress bar OUTSIDE OF THE FORM
  if (!CRM_Stepw_Utils_Userparams::isStepwiseWorkflow('request')) {
    // If we're not in a stepwise workflow, there's nothing for us to do here.
    return;
  }  
}

function stepw_civicrm_angularModules(&$angularModules) {
  // This hook only fires on cache rebuild.
  // All afforms need our angular module (because when this hook fires, we really
  // have no idea which afforms will later be created later or added to a workflow)
  $angularModules['afCore']['requires'][] = 'stepwAfform';
}

/**
 * Callback for afform modification, defined in stepw_civicrm_alterAngular().
 * 
 * @param phpQueryObject $doc
 * @param string $path
 */
function _stepw_alterAfformHtml(phpQueryObject $doc, $path) {
  // Our alterations are sometimes done from other scopes, so we've put them
  // into a utility method.
  CRM_Stepw_Utils_Afform::alterForm($doc);
}


function stepw_civicrm_alterAngular(\Civi\Angular\Manager $angular) {
  // This hook fires only when afform cache is rebuilt.
  // For any afform defined in any step of any workflow, add our alterAfformHtml callback.
  $hookedAfformNames = CRM_Stepw_Utils_WorkflowData::getAllAfformNames();

  foreach ($hookedAfformNames as $hookedAfformName) {
    /* If I know the $name of the saved form, eg. afformQuickAddIndividual, I can
     * get the path ("~/afformQuickAddIndividual/afformQuickAddIndividual.aff.html")
     * by calling \Civi\Angular\Manager::getRawPartials($name).
     */
    $partials = $angular->getRawPartials($hookedAfformName);
    $alterHtmlFile = array_keys($partials)[0];
    \Civi::$statics['STEPW_AFFORM_FORM_NAME_BY_PATH'][$alterHtmlFile] = $hookedAfformName;
    $angular->add(\Civi\Angular\ChangeSet::create('stepw_changeset_' . $hookedAfformName)
        ->alterHtml($alterHtmlFile, '_stepw_alterAfformHtml')
    );
  }
}

/**
 * Late (low-priority) listener on 'civi.afform.submit' event (bound in stepw_civicrm_config()).
 * 
 * @param \Civi\Afform\Event\AfformSubmitEvent $event
 * @return void
 */
function _stepw_afform_submit_late(\Civi\Afform\Event\AfformSubmitEvent $event) {
    // fixme3 note: here we will:
    // - Determine what step was submitted, and timestamp this step submission in the workflow intance.
    // - Determine any created contact ID, and set this as a workflowInstance property.
    //
    // fixme3: If is not stepwise workflow: return.
    // 
    // fixme3val: validate _stepw_afform_submit_late.
    //  - Given WI exists in state
    //  - Given Step public id exists in WI
    //  - Given Step is for this afform ($afformName matches step['afform_name'])
    //  -- VALIDATION FAILURE: take no action and return.
    //

  
  $afform = $event->getAfform();
  $afformName = ($afform['name'] ?? NULL);
  
  // Determine what step was submitted, and close this step in the workflow.
  $workflowInstancePublicId = CRM_Stepw_Utils_Userparams::getUserParams('referer', CRM_Stepw_Utils_Userparams::QP_WORKFLOW_INSTANCE_ID);
  $workflowInstance = CRM_Stepw_State::singleton()->getWorkflowInstance($workflowInstancePublicId);
  if (!empty($workflowInstance)) {
    $stepPublicId = CRM_Stepw_Utils_Userparams::getUserParams('referer', CRM_Stepw_Utils_Userparams::QP_STEP_ID);
    $workflowInstance->completeStep($stepPublicId);
  }
  // Determine any created contact ID, and set this as a workflowInstance property.
  $entityIds = $event->getEntityIds('Individual1');
  $individualContactId = ($entityIds[0] ?? NULL);
  if (!empty($individualContactId)) {
    $workflowInstance->setCreatedIndividualCid($individualContactId);
  }
}

/**
 * Early (high-priority) listener on 'civi.afform.submit' event (bound in stepw_civicrm_config()).
 * 
 * FLAG_STEPW_AFFORM_BRITTLE
 * 
 * @param \Civi\Afform\Event\AfformSubmitEvent $event
 * @return void
 */
function _stepw_afform_submit_early(\Civi\Afform\Event\AfformSubmitEvent $event) {
    // fixme3 note: here we will:
    // - Alter submission parameters to allow the afform submission to be re-saved.
    //
    // fixme3val: validate _stepw_afform_submit_early.
    //  - Given WI exists in state
    //  - Given Step public id exists in WI
    //  - Given Step is for this afform ($afformName matches step['afform_name'])
    //  - afformsubmission.sid is provided (in referer QP_AFFORM_RELOAD_SID ?)
    //  - $event is for an activity (we don't currently support any other entities here.)
    //  
    //  -- VALIDATION FAILURE: take no action and return.
    //

  if (!CRM_Stepw_Utils_Userparams::isStepwiseWorkflow('referer')) {
    // We're not in a workflowInstance, so there's nothing for us to do here.
    return;
  }

  $afform = $event->getAfform();
  $afformName = ($afform['name'] ?? NULL);
  $reloadSubmissionId = CRM_Stepw_Utils_Userparams::getUserParams('referer', CRM_Stepw_Utils_Userparams::QP_AFFORM_RELOAD_SID);
  

  // This code allows us to re-save afforms that are prefilled
  // with an existing submission Id, and to allow that re-submission to update
  // the related entity. This code:
  // - causes an existing activity (the one linked to the submission) to actually be overwritten. 
  // - causes the creation of a new submission on the afform.
  // We may want to improve this so it handles entities other than activities.
  if ($event->getEntityType() == 'Activity') {
    $records = $event->getRecords();
    foreach ($records as &$record) {
      if (!empty($record['id'])) {
        // FLAG_STEPW_AFFORM_BRITTLE : afform may decide to add more checks that
        //   would prevent re-saving of entities via already-processed submissions.
        // If the record has 'id', copy that into record['fields'] so that the
        // 'save' api will actually update the activity.
        $record['fields']['id'] = $record['id'];
        // Unset any null values in 'fields'. For activities, this might include,
        // e.g. 'source_contact_id', which could have been auto-populated by the
        // original afform, but which will be unknown in the submission.
        // In any case, we onlY want to update values given in the re-submitted
        // form data.
        foreach ($record['fields'] as $fieldName => $fieldValue) {
          if (is_null($fieldValue)) {
            unset($record['fields'][$fieldName]);
          }
        }
      }
    }
    $event->setRecords($records);
  }
}

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function stepw_civicrm_config(&$config): void {
  _stepw_civix_civicrm_config($config);

  // Bind our event listeners.
  Civi::dispatcher()->addListener('civi.api.prepare', ['CRM_Stepw_APIWrapper', 'PREPARE'], -100);
  Civi::dispatcher()->addListener('civi.api.respond', ['CRM_Stepw_APIWrapper', 'RESPOND'], -100);
  Civi::dispatcher()->addListener('civi.afform.submit', '_stepw_afform_submit_early', 1000);
  Civi::dispatcher()->addListener('civi.afform.submit', '_stepw_afform_submit_late', -1000);
}

/**
 * Implements hook_civicrm_permission-check().
 * 
 * FLAG_STEPW_AFFORM_BRITTLE
 * 
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_permission_check/
 * 
 */
function stepw_civicrm_permission_check($permission, &$granted) {
  
  // fixme3 note: here we will:
  // - Grant certain permissions so that afform submissions can be prefilled.
  //
  // fixme3: If is not stepwise workflow: return.
  // 
  // fixme3val: validate stepw_civicrm_permission_check.
  //  - Given WI(referer) exists in state
  //  - Given Step public id(referer) exists in WI
  //  - Given Step is for this afform ($afformName matches step['afform_name']), where afform name is in json_decode($_POST['params'])
  //  - afform sid is associated with this step (sid is in json_decode($_POST['params'])['args'], and in QP_AFFORM_RELOAD_SID)
  //  -- VALIDATION FAILURE: take no action and return.
  //


  // Only take action on afform.submission.prefill, which is actually a POST.
  static $uri;
  if (!isset($uri) && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $uri = $_SERVER['REQUEST_URI'];
    $a = 1;
  }
  if (
    $uri != "/civicrm/ajax/api4/Afform/prefill/"
//    fixme3: seems like we don't need to support this path: && $uri != "/civicrm/ajax/api4/Afform/submit/"
  ) {
    return;
  }

  if (!CRM_Stepw_Utils_Userparams::isStepwiseWorkflow('any')) {
    // We're not in a workflowInstance (neither per referer NOR per request), so there's nothing for us to do here.
    return;
  }
  
  // fixme: we must also verify that the submission id (available in QP_AFFORM_RELOAD_SID)
  // is valid for the given step.
  
  switch ($permission) {
    // If missing, anon will probably generate an IDS check failure.
    // fixme3: is 'skip ids check' actually required?
//    case 'skip IDS check':
    // If missing, anon will get API4 access denied on AfformSubmission::get,
    // and user-visible error on afform load
    // FLAG_STEPW_AFFORM_BRITTLE : Afform could change its required permissions for prefill operation.
    case 'administer afform':
    // if missing, afform prefill will be empty (depending on various permissions/ACLs)
    case 'view all contacts':
    // If missing, user (probably anon) won't be able to prefill contact data.
      $granted = true;
      break;
  }
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function stepw_civicrm_install(): void {
  _stepw_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function stepw_civicrm_enable(): void {
  _stepw_civix_civicrm_enable();
}
