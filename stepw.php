<?php

require_once 'stepw.civix.php';

use CRM_Stepw_ExtensionUtil as E;

function stepw_civicrm_alterContent(&$content, $context, $tplName, &$object) {
  // fixme: if this is an afform and we're in a workflowInstance, display a progress bar OUTSIDE OF THE FORM
}

function stepw_civicrm_pageRun(CRM_Core_Page $page) {
  
  $pageName = $page->getVar('_name');
  if ($pageName == 'CRM_Afform_Page_AfformBase') {
    // Validate workflow uwer params, or exit.
    CRM_Stepw_Utils_Userparams::validateWorkflowInstanceStep('request', TRUE);
    
    $isStepwiseWorkflow = CRM_Stepw_Utils_Userparams::isStepwiseWorkflow('request');
    if ($isStepwiseWorkflow) {

      // Build redirect url to next step.
      $workflowInstancePublicId = CRM_Stepw_Utils_Userparams::getUserParams('request', CRM_Stepw_Utils_Userparams::QP_WORKFLOW_INSTANCE_ID);
      $redirectQueryParams = [
        CRM_Stepw_Utils_Userparams::QP_WORKFLOW_INSTANCE_ID => $workflowInstancePublicId,
      ];
      $redirect = CRM_Stepw_Utils_General::buildNextUrl($redirectQueryParams);

      // Get the config for this step so we can know the button label.
      $workflowConfigStep = CRM_Stepw_Utils_WorkflowData::getCurrentWorkflowConfigStep('request');

      $vars = [
        // fixme: is isStepwiseWorkflow actually used in the JS (check stepwAfform module)
        'isStepwiseWorkflow' => $isStepwiseWorkflow,
        'submitButtonLabel' => $workflowConfigStep['button_label'],
        'redirect' => $redirect,
      ];
      CRM_Core_Resources::singleton()->addVars('stepw', $vars);
      
      // fixme: this js file is not needed, because apparently back-button causes
      // a full page reload on afform pages, which is what we want.
      // CRM_Core_Resources::singleton()->addScriptFile('com.joineryhq.stepw', 'js/CRM_Afform_Page_AfformBase.js');

      // fixme: determine if this is a 'reload/back-button' form, and redirect to the form with 'sid' in afform args.
      // (see for example how we add Individual1 in apiwrappers.)
    }
  }
}

function stepw_civicrm_angularModules(&$angularModules) {
  // This hook only fires on cache rebuild.
  // All afforms need our angular module.
  $angularModules['afCore']['requires'][] = 'stepwAfform';
}

function _stepw_alterAfformHtml(phpQueryObject $doc, $path) {
  // $doc is a phpquery object:
  //  - built with code in: https://github.com/TobiaszCudnik/phpquery)
  //  - Best phpquery documentation I've found so far: https://github.com/electrolinux/phpquery/blob/master/wiki/README.md

  // Change the button properties and relocate it into a new <div> that uses our controller.
  $button = $doc->find('button[ng-click="afform.submit()"]');
  $button->attr('ng-if', 'stepwiseShowSubmitButton');
  $button->html('{{ submitButtonLabel }}');
  $buttonHtml = $button->htmlOuter();
  $button->remove();
  $appendToDoc = <<< "END"
    <div ng-controller="stepwAfform">
    $buttonHtml
    </div>
  END;
  $doc->append($appendToDoc);
}


function stepw_civicrm_alterAngular(\Civi\Angular\Manager $angular) {
  // This hook fires only when afform cache is rebuilt.
  // For any afform defined in any step of any workflow, add our listener.
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

function _stepw_afform_submit_late(\Civi\Afform\Event\AfformSubmitEvent $event) {
  $afform = $event->getAfform();
  $afformName = ($afform['name'] ?? NULL);
  if (
    !empty($afformName)
    // If this afform is not for the current workflow step, we'll take no action here.
    && CRM_Stepw_Utils_Userparams::currentWorkflowStepIsForAfform('referer', $afformName)
  ) {
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

function _stepw_afform_submit_early(\Civi\Afform\Event\AfformSubmitEvent $event) {
  if (!CRM_Stepw_Utils_Userparams::isStepwiseWorkflow('referer')) {
    // We're not in a workflowInstance, so there's nothing for us to do here.
    return;
  }

  $afform = $event->getAfform();
  $afformName = ($afform['name'] ?? NULL);
  if (
    !empty($afformName)
    // If this afform is not for the current workflow step, we'll take no action here.
    && CRM_Stepw_Utils_Userparams::currentWorkflowStepIsForAfform('referer', $afformName)
  ) {

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
}

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function stepw_civicrm_config(&$config): void {
  _stepw_civix_civicrm_config($config);

  // Bind our wrapper for API Events
  Civi::dispatcher()->addListener('civi.api.prepare', ['CRM_Stepw_APIWrapper', 'PREPARE'], -100);
  Civi::dispatcher()->addListener('civi.api.respond', ['CRM_Stepw_APIWrapper', 'RESPOND'], -100);
  Civi::dispatcher()->addListener('civi.afform.submit', '_stepw_afform_submit_early', 1000);
  Civi::dispatcher()->addListener('civi.afform.submit', '_stepw_afform_submit_late', -1000);
}

function stepw_civicrm_permission_check($permission, &$granted) {
  if (!CRM_Stepw_Utils_Userparams::isStepwiseWorkflow('any')) {
    // We're not in a workflowInstance (neither per referer NOR per request), so there's nothing for us to do here.
    return;
  }

  // Only take action on afform.submission.prefill.
  static $q;
  if (!isset($q)) {
    $q = CRM_Utils_Request::retrieve('q', 'String', '');
  }
  if ($q != "civicrm/ajax/api4/Afform/prefill") {
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
//    case 'skip IDS check':
//    // If missing, anon will get API4 access denied on AfformSubmission::get,
//    // and user-visible error on afform load
//    case 'administer afform':
//    // if missing, afform prefill will be empty (depending on various permissions/ACLs)
//    case 'view all contacts':
    case 'this does nothing':
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
