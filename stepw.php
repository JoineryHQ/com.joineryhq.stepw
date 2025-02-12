<?php

require_once 'stepw.civix.php';

use CRM_Stepw_ExtensionUtil as E;

function _stepw_alterAfformHtml(phpQueryObject $doc, $path) {
  // This listener will only fire when the given afform's cache is being rebuilt,
  // i.e., the first time the afform is displayed after a cache clear.

  // $doc is a phpquery object:
  //  - built with code in: https://github.com/TobiaszCudnik/phpquery)
  //  - Best phpquery documentation I've found so far: https://github.com/electrolinux/phpquery/blob/master/wiki/README.md

  // fixme: we need an angular (module? directive? component?) that will do the following:
  // - only act if we're in a workflowInstance
  // - validate workflowinstance id (and stepid, if any), or else make a noisy error and prevent further user action.
  // - determine if this is a 'reload/back-button' form, and somehow redirect
  //   to the form with 'sid' in afform args.
  // - alter the button text per step configuration.
  // - display a progress bar OUTSIDE OF THE FORM (is this possible here? Should we use hook_civicrm_alterContent()?)
  // - if this is a 'reload/back-button' submission form (has afform 'sid'), 
  //   display a submit button (again, with the correct text per step config)
  //
  
  // Resolve the above fixmes above this line. We're quitting here.
  return;
  
  
  
  $isValid = _stepw_alterAfformHtml_validate('path', $path);
  if ($isValid) {
    // Find the submit button and change its text
    $button = $doc->find('button[ng-click="afform.submit()"]');
    // FIXME: get correct button name from stepwise config.
    $button->html('foobar');

    // fixme: must also add progress bar on these afform steps.

    // fixme: we need a way to determine if this is a back-button reload.
    if(CRM_Stepw_Utils_Userparams::getUserParams('referer', 'fixme-stepwisereload')) {
      // Only on stepwise 'reload submission' (i.e. "back-button") afforms, append a submit button.
      $doc->append('<button class="af-button btn btn-primary" crm-icon="fa-check" ng-click="afform.submit()">Submit</button>');
    }
  }
  else {
    CRM_Stepw_Utils_General::alterAfformInvalid($doc);
  }
}

function _stepw_alterAfformHtml_validate($keyType, $key) {
  $isValid = FALSE;
  
  if ($keyType == 'path') {
    // form names are keyed to $path in this static array variable:
    $afformName = \Civi::$statics['STEPW_AFFORM_FORM_NAME_BY_PATH'][$key];    
  }
  elseif ($keyType == 'name') {
    $afformName = $key;    
  }
  
  if (!CRM_Stepw_Utils_General::isStepwiseWorkflow('referer')) {
    // The afform is being included on a page that, for whatever reason,
    // isn't being viewed within a workflowInstance. This is fine. We have 
    // no validation problem to complain about.
    $isValid = TRUE;
  }
  else {
    $stepPublicId = CRM_Stepw_Utils_Userparams::getUserParams('referer', CRM_Stepw_Utils_Userparams::QP_STEP_ID);
    $workflowInstancePublicId = CRM_Stepw_Utils_Userparams::getUserParams('referer', CRM_Stepw_Utils_Userparams::QP_WORKFLOW_INSTANCE_ID);
    $workflowInstance = CRM_Stepw_State::singleton()->getWorkflowInstance($workflowInstancePublicId);
    if (
      // workflow instance exists? 
      !empty($workflowInstance)
      // step exists?
      && $workflowInstance->validateStep($stepPublicId)  
    ) {
      // step is for this afform?
      $workflowInstanceStep = $workflowInstance->getStepByPublicId($stepPublicId);
      $workflowConfig = CRM_Stepw_Utils_WorkflowData::getWorkflowConfigById($workflowInstance->getVar('workflowId'));
      $workflowConfigStep = $workflowConfig['steps'][$workflowInstanceStep['stepId']];
      if (($workflowConfigStep['afform_name'] ?? NULL) == $afformName) {
        // fixme: must also validate :step_state_key (if given) is valid?
        $isValid = TRUE;
      }
    }
  }
  return $isValid;
}

function stepw_civicrm_alterAngular(\Civi\Angular\Manager $angular) {
  // This hook fires only when afform cache is rebuilt.
  
  // For any afform defined in any step of any workflow, add our listener.
  $hookedAfformNames = CRM_Stepw_Utils_WorkflowData::getAllAfformNames();
  
  foreach ($hookedAfformNames as $hookedAfformName) {
    /* If I know the $name of the saved form, eg. afformQuickAddIndividual, I can
     * get the path ("~/afformQuickAddIndividual/afformQuickAddIndividual.aff.html")
     * by calling _afform_get_partials($name), or perhaps better, \Civi\Angular\Manager::getRawPartials($name).
     */
    $partials = $angular->getRawPartials($hookedAfformName);
    $alterHtmlFile = array_keys($partials)[0];
    \Civi::$statics['STEPW_AFFORM_FORM_NAME_BY_PATH'][$alterHtmlFile] = $hookedAfformName;
    $angular->add(\Civi\Angular\ChangeSet::create('stepw_changeset_' . $hookedAfformName)
      ->alterHtml($alterHtmlFile, '_stepw_alterAfformHtml')
    );
    $a = 1;
  }
}


function _stepw_afform_submit_late(\Civi\Afform\Event\AfformSubmitEvent $event) {
  if (!CRM_Stepw_Utils_General::isStepwiseWorkflow('referer')) {
    // We're not in a workflowInstance, so there's nothing for us to do here.
    return;
  }  
    
  $afform = $event->getAfform();
  $afformName = ($afform['name'] ?? NULL);
  if (!empty($afformName)) {
    $isValid = _stepw_alterAfformHtml_validate('name', $afformName);
    if ($isValid) {
      // Determine what step was submitted, and close this step in the workflow.
      $workflowInstancePublicId = CRM_Stepw_Utils_Userparams::getUserParams('referer', CRM_Stepw_Utils_Userparams::QP_WORKFLOW_INSTANCE_ID);
      $workflowInstance = CRM_Stepw_State::singleton()->getWorkflowInstance($workflowInstancePublicId);
      if (!empty($workflowInstance)) {
        $stepPublicId = CRM_Stepw_Utils_Userparams::getUserParams('referer', CRM_Stepw_Utils_Userparams::QP_STEP_ID);
        $workflowInstance->closeStep($stepPublicId);
      }
      // Determine any created contact ID, and set this as a workflowInstance property.
      $entityIds = $event->getEntityIds('Individual1');
      $individualContactId = ($entityIds[0] ?? NULL);
      if (!empty($individualContactId)) {
        $workflowInstance->setCreatedEntityId('Individual1', $individualContactId);
      }
    }
  }
  $referer = CRM_Stepw_Utils_Userparams::getUserParams('referer');
  $request = CRM_Stepw_Utils_Userparams::getUserParams('request');
  $a = 1;
}

function _stepw_afform_submit_early(\Civi\Afform\Event\AfformSubmitEvent $event) {
  if (!CRM_Stepw_Utils_General::isStepwiseWorkflow('referer')) {
    // We're not in a workflowInstance, so there's nothing for us to do here.
    return;
  }  
  
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
  if (CRM_Stepw_Utils_General::isStepwiseWorkflow('referer')
   || CRM_Stepw_Utils_General::isStepwiseWorkflow('request')
  ){
    // In the current model, we have to make everything work even when
    // debug is OFF
    if (CRM_Core_Config::singleton()->debug) {
      throw new CRM_Extension_Exception('This branch demands debugging must be turned off!');
    }
  }
  _stepw_civix_civicrm_config($config);
  
  // Bind our wrapper for API Events
  Civi::dispatcher()->addListener('civi.api.prepare', ['CRM_Stepw_APIWrapper', 'PREPARE'], -100);
  Civi::dispatcher()->addListener('civi.api.respond', ['CRM_Stepw_APIWrapper', 'RESPOND'], -100);
  Civi::dispatcher()->addListener('civi.afform.submit', '_stepw_afform_submit_early', 1000);
  Civi::dispatcher()->addListener('civi.afform.submit', '_stepw_afform_submit_late', -1000);
}

function stepw_civicrm_permission_check($permission, &$granted) {
  if (
    !CRM_Stepw_Utils_General::isStepwiseWorkflow('referer')
    && !CRM_Stepw_Utils_General::isStepwiseWorkflow('request')
  ) {
    // We're not in a workflowInstance (neither per referer NOR per request), so there's nothing for us to do here.
    return;
  }  
  
  // FIXME: Additional permissions are required for loading afform submission data (e.g.
  // stepwise form re-submission during 'back-button' handling), but of course
  // we should only grant it momentarily and only after confirming the (typically anonymous)
  // user is actual allowed to edit this submission as part of his current
  // workflow instance.
  switch($permission) {
    // If missing, anon will probably generate an IDS check failure.
    case 'skip IDS check':
    // If missing, anon will get API4 access denied on AfformSubmission::get,
    // and user-visible error on afform load
    case 'administer afform':
    // if missing, afform prefill will be empty (depending on various permissions/ACLs)
    case 'view all contacts':
      // fixme: it's okay to log this, but use a better prefix.
      \Civi::log()->debug(__FUNCTION__, ['8oom05JEP34HAJOJ0geXoVMvLLgRn1Sq: granting:'. $permission]);
      
      $q = CRM_Utils_Request::retrieve('q', 'String', '');
      // Only take action on afform.submission.prefill.
      // fixme: we must also verify that the submission id (available in $param['args']['sid'])
      // is valid for the current user's workflow instance and current step.
      if ($q == "civicrm/ajax/api4/Afform/prefill") {
        $params = json_decode($r['params'], true);
        $granted = true;
      }
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
