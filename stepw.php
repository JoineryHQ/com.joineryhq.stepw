<?php

require_once 'stepw.civix.php';

use CRM_Stepw_ExtensionUtil as E;

function stepw_civicrm_pageRun(CRM_Core_Page $page) {
  
  $pageName = $page->getVar('_name');
  if ($pageName == 'CRM_Afform_Page_AfformBase') {
    // fixme3 note: here we will:
    //  - If the given step has already been submitted, and this is NOT an afform with sid,
    //    redirect to $workflowInstance->getStepUrl(stepPublicId) (which should return
    //    a url with afform sid and some "reload" query parameter to indicate that fact (since afform
    //    only knows it from #fragment, which is not visible here.)
    //  - Set crm.vars for stepwAfform module.
    //
    //
    // fixme3: If is not stepwise workflow: return.
    // 
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

    if (CRM_Stepw_Utils_Userparams::isStepwiseWorkflow('request')) {
      // This script is only needed for 'back-button reloaded' forms; the button
      // itself is content defined in hook_civicrm_alterContent().
      CRM_Core_Resources::singleton()->addScriptFile(E::LONG_NAME, '/js/reload-button-hijack.js');
    }
    
    $isStepwiseWorkflow = CRM_Stepw_Utils_Userparams::isStepwiseWorkflow('request');
    if ($isStepwiseWorkflow) {

      // Build redirect url to our step handler for this workflowInstance
      // fixme3 note: our step handler will, by the time it runs, know that this
      // form was the most recently submitted step, so it will redirect to the url
      // for the step subsequent to this one.
      //
      $workflowInstancePublicId = CRM_Stepw_Utils_Userparams::getUserParams('request', CRM_Stepw_Utils_Userparams::QP_WORKFLOW_INSTANCE_ID);
      $stepPublicId = CRM_Stepw_Utils_Userparams::getUserParams('request', CRM_Stepw_Utils_Userparams::QP_STEP_ID);
      $redirectQueryParams = [
        CRM_Stepw_Utils_Userparams::QP_WORKFLOW_INSTANCE_ID => $workflowInstancePublicId,
      ];
      $redirect = CRM_Stepw_Utils_General::buildStepUrl($redirectQueryParams);

      // Get the config for this step so we can know the button label.
      $workflowInstance = CRM_Stepw_State::singleton()->getWorkflowInstance($workflowInstancePublicId);
      $buttonLabel = $workflowInstance->getButtonLabel($stepPublicId);

      $vars = [
        // fixme3: is isStepwiseWorkflow actually used in the JS (check stepwAfform module)
        'isStepwiseWorkflow' => $isStepwiseWorkflow,
        'submitButtonLabel' => $buttonLabel,
        'redirect' => $redirect,
      ];
      CRM_Core_Resources::singleton()->addVars('stepw', $vars);
      
    }
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
  
  // fixme: if this is an afform and we're in a workflowInstance, display a progress bar OUTSIDE OF THE FORM
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
 * @param \Civi\Afform\Event\AfformSubmitEvent $event
 * @return void
 */
function _stepw_afform_submit_early(\Civi\Afform\Event\AfformSubmitEvent $event) {
    // fixme3 note: here we will:
    // - Alter submission parameters to allow the afform submission to be re-saved.
    //
    // fixme3: If is not stepwise workflow: return.
    // 
    // fixme3val: validate _stepw_afform_submit_early.
    //  - Given WI exists in state
    //  - Given Step public id exists in WI
    //  - Given Step is for this afform ($afformName matches step['afform_name'])
    //  -- VALIDATION FAILURE: take no action and return.
    //

  if (!CRM_Stepw_Utils_Userparams::isStepwiseWorkflow('referer')) {
    // We're not in a workflowInstance, so there's nothing for us to do here.
    return;
  }

  $afform = $event->getAfform();
  $afformName = ($afform['name'] ?? NULL);

  // FIXME: this is POC code that allows us to re-save afform submissions and update
  // the related entity. This code causes an existing activity (the one linked to the submission)
  // to actually be overwritten. We need to improve this so it handles entities
  // other than activities.
  // FIXME: this should only be done on 'submission view' forms in the midst of
  // a stepwise workflow (i.e., "back-button" handling for form resubmission)
  if ($event->getEntityType() == 'Activity') {
    $records = $event->getRecords();
    foreach ($records as &$record) {
      if (!empty($record['id'])) {
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
  //  - afform sid is associated with this step (sid is in json_decode($_POST['params'])['args']
  //  -- VALIDATION FAILURE: take no action and return.
  //


  // Only take action on afform.submission.prefill, which is actually a POST.
  static $uri;
  if (!isset($uri) && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $uri = $_SERVER['REQUEST_URI'];
  }
  if ($uri != "/civicrm/ajax/api4/Afform/prefill/") {
    return;
  }

  if (!CRM_Stepw_Utils_Userparams::isStepwiseWorkflow('any')) {
    // We're not in a workflowInstance (neither per referer NOR per request), so there's nothing for us to do here.
    return;
  }
  
  // fixme: we must also verify that the submission id (available in $param['args']['sid'])
  // is valid for the current user's workflow instance and current step.
  
  // FIXME: Additional permissions are required for loading afform submission data (e.g.
  // stepwise form re-submission during 'back-button' handling), but of course
  // we should only grant it momentarily and only after confirming the (typically anonymous)
  // user is actual allowed to edit this submission as part of his current
  // workflow instance.
  switch ($permission) {
    // If missing, anon will probably generate an IDS check failure.
    // fixme3: is 'skip ids check' actually required?
//    case 'skip IDS check':
    // If missing, anon will get API4 access denied on AfformSubmission::get,
    // and user-visible error on afform load
    case 'administer afform':
    // if missing, afform prefill will be empty (depending on various permissions/ACLs)
    case 'view all contacts':
      $g = $_GET;
      $p = $_POST;
      $r = $_REQUEST;
      $afformParams = json_decode($_POST['params'], TRUE);
      $request = CRM_Stepw_Utils_Userparams::getUserParams('request');
      $referer = CRM_Stepw_Utils_Userparams::getUserParams('referer');
      $state = CRM_Stepw_State::singleton();      
      
      // FIXME: which perms are really required here?
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
