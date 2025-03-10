<?php

// fixme: add a tool for generating qr codes in-app?

require_once 'stepw.civix.php';

use CRM_Stepw_ExtensionUtil as E;

function stepw_civicrm_pageRun(CRM_Core_Page $page) {

  $pageName = $page->getVar('_name');
  if ($pageName == 'CRM_Afform_Page_AfformBase') {
    // note: here we will:
    //  - If the given step has already been submitted, and we were NOT given an afform sid,
    //    redirect to $workflowInstance->getStepUrl(stepPublicId) (which should return
    //    a url with afform sid and some "reload" query parameter to indicate that fact (since afform
    //    only knows it from #fragment, which is not visible here.)
    //  - Set crm.vars for stepwAfform module.
    //
    // note:val: validate hook_pageRun.                                                                                                                                                     
    //  - If given "reload" param:                                                                                                                                                           
    //      - "reload" param is for given step                                                                                                                                               
    //      - step has an afform sid.                                                                                                                                                        
    //  - Given Step is for this afform ($q matches step['url'])                                                                                                                             
    //  -- VALIDATION FAILURE: throw an exception
    //  

    // If is not stepwise workflow: return.
    if (!CRM_Stepw_Utils_Userparams::isStepwiseWorkflow('request')) {
      // We're not in a stepwise workflow. Nothing for us to do here.
      return;
    }
    
    // Validation: on failure we'll throw an exception
    // 
    // validate: fail if: Given Step is not for this afform.
    $workflowInstancePublicId = CRM_Stepw_Utils_Userparams::getUserParams('request', CRM_Stepw_Utils_Userparams::QP_WORKFLOW_INSTANCE_ID);
    $stepPublicId = CRM_Stepw_Utils_Userparams::getUserParams('request', CRM_Stepw_Utils_Userparams::QP_STEP_ID);
    $menuItem = CRM_Core_Invoke::getItem($page->urlPath);
    $pageArgs = CRM_Core_Menu::getArrayForPathArgs($menuItem['page_arguments']);
    $pageAfformName = $pageArgs['afform'];    
    if (!CRM_Stepw_Utils_Validation::stepIsForAfformName($workflowInstancePublicId, $stepPublicId, $pageAfformName)) {
      throw new CRM_Stepw_Exception(__METHOD__ . ": Step, per publicId, is not for this afform: $pageAfformName");
    }
    
    // validate: fail if: the 'reload' param is given and step has no sid. (i.e., 
    // reload is only valid on steps that have already been submitted at least once.)
    $reloadSubmissionId = CRM_Stepw_Utils_Userparams::getUserParams('request', CRM_Stepw_Utils_Userparams::QP_AFFORM_RELOAD_SID);
    $workflowInstance = CRM_Stepw_State::singleton()->getWorkflowInstance($workflowInstancePublicId);
    $stepSubmissionId = $workflowInstance->getStepLastAfformSubmissionId($stepPublicId);
    if (
      (!empty($reloadSubmissionId))
      && (empty($stepSubmissionId))
    ) {
      throw new CRM_Stepw_Exception(__METHOD__ . ": Reload parameter given, but step has no existing submissionId: $afformName");
    }
    
    // If workflowInstance is closed, redirect to last step.
    if ($workflowInstance->getVar('isClosed')) {
      $closedWorkflowInstanceStepUrl = $workflowInstance->getFirstUncompletedStepUrl();
      CRM_Utils_System::redirect($closedWorkflowInstanceStepUrl);
    }

    // If the given step has already been submitted, and we were NOT given QP_AFFORM_RELOAD_SID (with the current sid of the step),
    // redirect to $workflowInstance->getStepUrl(stepPublicId). See notes on
    // $step->options[]['afformSid'].
    // This redirection is part of our security/validation protocol, because it 
    // ensures that the value of QP_AFFORM_RELOAD_SID is corret, which is important
    // because that value is later checked by our validation process.
    if (
      $stepSubmissionId
      && ($reloadSubmissionId != $stepSubmissionId)
    ) {
      $stepReloadUrl = $workflowInstance->getStepUrl($stepPublicId);
      CRM_Utils_System::redirect($stepReloadUrl);
    }
      
    // If we'er still here, it means we'll display the afform page (fresh, not
    // for review/re-submission).
    // Build redirect url to our step handler for this workflowInstance
    // Note: our step handler will, by the time it runs, know that this
    // form was the most recently submitted step, so it will redirect to the url
    // for the step subsequent to this one.
    //
    $redirectQueryParams = [
      CRM_Stepw_Utils_Userparams::QP_WORKFLOW_INSTANCE_ID => $workflowInstancePublicId,
    ];
    $redirectUrl = CRM_Stepw_Utils_General::buildStepUrl($redirectQueryParams);
    // Determine the button label so we can send it to JS.
    // Since this is an afform, we can only support one button label (indeed,
    // there should be only one in the config for this step/option). So we'll
    // fetch the array of labels, and just use the first one (there should be 
    // only one.)
    $buttonLabels = $workflowInstance->getStepButtonLabels($stepPublicId);
    $buttonLabel = $buttonLabels[0];
    
    $jsVars = [
      'submitButtonLabel' => $buttonLabel,
      'redirectUrl' => $redirectUrl,
      'stepAfformSid' => ($stepSubmissionId ?? NULL),
    ];
    CRM_Core_Resources::singleton()->addVars('stepw', $jsVars);

    // We're injecting html for the progress bar in stepw_civicrm_alterContent(),
    // but that function cannot add resources. Do that here.
    if (CIVICRM_UF == 'WordPress') {
      $assets = CRM_Stepw_Utils_WpShortcode::getPageAssets('progressbar');
    }
    CRM_Stepw_Utils_General::addCivicrmResources($assets);
  }
}

function stepw_civicrm_alterContent(&$content, $context, $tplName, &$object) {
  // Note: here we will:
  // - display a progress bar above the afform
  //

  if (!is_a($object, 'CRM_Afform_Page_AfformBase')) {
    // We'll only do this on afforms; otherwise, do nothing and return.
    return;
  }

  if (!CRM_Stepw_Utils_Userparams::isStepwiseWorkflow('request')) {
    // If we're not in a stepwise workflow, there's nothing for us to do here.
    return;
  }  
  
  if (CIVICRM_UF == 'WordPress') {
    // Currently this is only supported in WordPress.
    $progressBarHtml = CRM_Stepw_Utils_WpShortcode::getProgressBarHtml();
    $content .= $progressBarHtml;
    // NOTE: we've added the html here, but script/style resources must be (and are)
    // added in stepw_civicrm_pageRun();
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
  // fixme: test everything to verify that we're not breaking afform in its normal usage...
  //   This testing should cover evertying this extension does.

  // This hook fires only when afform cache is rebuilt.
  // For any afform defined in any step of any workflow, add our alterAfformHtml callback.
  $hookedAfformNames = CRM_Stepw_WorkflowData::singleton()->getAllAfformNames();

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
 * Early (high-priority) listener on 'civi.afform.submit' event (bound in stepw_civicrm_config()).
 * 
 * FLAG_STEPW_AFFORM_BRITTLE
 * 
 * @param \Civi\Afform\Event\AfformSubmitEvent $event
 * @return void
 */
function _stepw_afform_submit_early(\Civi\Afform\Event\AfformSubmitEvent $event) {
  // note: here we will:
  // - Alter submission parameters to allow the afform submission to be re-saved.
  //
  // note: ignore and return if any of:
  //  - we're not in a stepwise workflow.
  //  - $event is for an activity (we don't currently support any other entities here.)                                                                                                    
  //  - afformsubmission.sid is not provided (in referer QP_AFFORM_RELOAD_SID; i.e., this is not a re-submission)
  //
  // note:val: validate _stepw_afform_submit_early.                                                                                                                                       
  //  - Given Step is for this afform ($afformName matches step/option['afformName'])                                                                                                            
  //  - given afformsubmission.sid is not the sid already saved for this step.
  //
  //  -- VALIDATION FAILURE: throw an exception.
  //                                                                                                                                                                                       

  if (!CRM_Stepw_Utils_Userparams::isStepwiseWorkflow('referer')) {
    // We're not in a workflowInstance, so there's nothing for us to do here.
    return;
  }
  if ($event->getEntityType() != 'Activity') {
    // $event is not for an activity (we don't currently support any other entities here.)
    return;
  }
  // 
  $reloadSubmissionId = CRM_Stepw_Utils_Userparams::getUserParams('referer', CRM_Stepw_Utils_Userparams::QP_AFFORM_RELOAD_SID);
  if (empty($reloadSubmissionId)) {
    // afformsubmission.sid is not provided (in referer QP_AFFORM_RELOAD_SID); this is not a re-submission.
    return;
  }

  // Validation: on failure we will: throw an exception. clearly someone is mucking with params.
  // 
  // validate: fail if: Given Step is not for this afform.
  $workflowInstancePublicId = CRM_Stepw_Utils_Userparams::getUserParams('referer', CRM_Stepw_Utils_Userparams::QP_WORKFLOW_INSTANCE_ID);
  $stepPublicId = CRM_Stepw_Utils_Userparams::getUserParams('referer', CRM_Stepw_Utils_Userparams::QP_STEP_ID);
  $afform = $event->getAfform();
  $afformName = ($afform['name'] ?? NULL);
  if (!CRM_Stepw_Utils_Validation::stepIsForAfformName($workflowInstancePublicId, $stepPublicId, $afformName)) {
    throw new  CRM_Stepw_Exception("Referenced step is not for this affrom: '$afformName', in " . __METHOD__, 'stepw_afform_submit_early_mismatch-afform');
  }

  // validate: fail if: afformsubmission.sid is not an sid already saved for this step.
  if (!CRM_Stepw_Utils_Validation::stepHasAfformSubmissionId($workflowInstancePublicId, $stepPublicId, $reloadSubmissionId)) {
    throw new  CRM_Stepw_Exception("Provided afform submission sid does not match existing sid in step, in " . __METHOD__, 'stepw_afform_submit_early_mismatch-submission-id');
  }

  // This code allows us to re-save afforms that are prefilled
  // with an existing submission Id, and to allow that re-submission to update
  // the related entity. This code:
  // - causes an existing activity (the one linked to the submission) to actually be overwritten. 
  // - causes the creation of a new submission on the afform.
  $records = $event->getRecords();
  foreach ($records as &$record) {
    if (!empty($record['id'])) {
      // If the record has 'id', copy that into record['fields'] so that the
      // 'save' api will actually update the activity.
      // FLAG_STEPW_AFFORM_BRITTLE : afform may decide to add more checks that
      //   would prevent re-saving of entities via already-processed submissions.
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
  
  // note: here we will:
  //  - Grant certain permissions so that afform submissions can be prefilled.
  //
  // note: ignore and return if any of:
  //  - we're not being asked about one of a small set of permissions
  //  - we're not in an afform 'prefill' operation
  //  - we're not in a stepwise workflow
  //                                                                                                                                                                                         
  // note:val: validate stepw_civicrm_permission_check.                                                                                                                                     
  //  - Given Step is for this afform ($afformName matches step/option['afformName']), where afform name is in json_decode($_POST['params'])                                                       
  //  - afform sid is associated with this step (sid is in json_decode($_POST['params'])['args'], and in QP_AFFORM_RELOAD_SID)                                                               
  //  -- VALIDATION FAILURE: take no action and return.                                                                                                                                      
  //                

  // Only take action on afform.submission.prefill, which is actually a POST.
  static $uri;
  if (!isset($uri) && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $uri = $_SERVER['REQUEST_URI'];
  }
  if (
    $uri != "/civicrm/ajax/api4/Afform/prefill/"
  ) {
    return;
  }

  // Name the permissions we will grant, if we make it far enough through validation checks.
  $ourGrantedPermissions = [
    // If 'administer afform' is not granted, anon will get API4 access denied on AfformSubmission::get,
    // and user-visible error on afform load
    // FLAG_STEPW_AFFORM_BRITTLE : Afform could change its required permissions for the prefill operation.
    'administer afform',
    // if 'view all contacts' is not granted, afform prefill will be empty (depending on various permissions/ACLs)
    'view all contacts',
  ];
  if (!in_array($permission, $ourGrantedPermissions)) {
    return;
  }

  if (!CRM_Stepw_Utils_Userparams::isStepwiseWorkflow('referer')) {
    // We're not in a workflowInstance (per referer), so there's nothing for us to do here.
    return;
  }

  // Validation: on failure we will: do nothing and return.
  // 
  // validate: fail if: Given Step is not for this afform.
  $workflowInstancePublicId = CRM_Stepw_Utils_Userparams::getUserParams('referer', CRM_Stepw_Utils_Userparams::QP_WORKFLOW_INSTANCE_ID);
  $stepPublicId = CRM_Stepw_Utils_Userparams::getUserParams('referer', CRM_Stepw_Utils_Userparams::QP_STEP_ID);
  $postParams = json_decode($_POST['params'], TRUE);
  $afformName = $postParams['name'];
  if (!CRM_Stepw_Utils_Validation::stepIsForAfformName($workflowInstancePublicId, $stepPublicId, $afformName)) {
    return;
  }

  // validate: fail if: afformsubmission.sid is not given
  $afformSid = ($postParams['args']['sid'] ?? NULL);
  if (empty($afformSid)) {
    return;
  }
  
  // validate: fail if: afformsubmission.sid is not an sid already saved for this step.
  if (!CRM_Stepw_Utils_Validation::stepHasAfformSubmissionId($workflowInstancePublicId, $stepPublicId, $afformSid)) {
    return;
  }

  $granted = TRUE;
}

/**
 * Implements hook_civicrm_unhandled_exception().
 *
 */
function stepw_civicrm_unhandled_exception(Exception $exception, $request = NULL) {
  if (is_a($exception, 'CRM_Stepw_Exception')) {
    // If this is our exception, redirect to invalid/ (this will also log the error message and code.
    CRM_Stepw_Utils_General::redirectToInvalid($exception);
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
