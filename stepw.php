<?php

require_once 'stepw.civix.php';

use CRM_Stepw_ExtensionUtil as E;

function stepw_civicrm_angularModules(&$angularModules) {
  //  $angularModules['afGuiEditor']['requires'][] = 'crmDynamicT_hemeSelector';
}

function stepw_foo(phpQueryObject $doc, $path) {
  // $doc is a phpquery object:
  //  - built with code in: https://github.com/TobiaszCudnik/phpquery)
  //  - Best phpquery documentation I've found so far: https://github.com/electrolinux/phpquery/blob/master/wiki/README.md

  // Find the submit button and change its text
  $button = $doc->find('button[ng-click="afform.submit()"]');
  // FIXME: get correct button name from stepwise config.
  $button->html('foobar');
  
  if(CRM_Stepw_Utils::getRefererQueryParams('stepwisereload')) {
    // Only on stepwise 'reload submission' (i.e. "back-button") afforms, append a submit button.
    // FIXME: only do this if referer params indicate we're in a stepwise workflow (e.g.., not for core 'submission view' forms)
    $doc->append('<button class="af-button btn btn-primary" crm-icon="fa-check" ng-click="afform.submit()">Submit</button>');
  }
}

function stepw_civicrm_alterAngular(\Civi\Angular\Manager $angular) {
  /* If I know the $name of the saved form, eg. afformQuickAddIndividual, I can
   * get the path ("~/afformQuickAddIndividual/afformQuickAddIndividual.aff.html")
   * by calling _afform_get_partials($name), or perhaps better, \Civi\Angular\Manager::getRawPartials($name).
   */

  $hookedAfformNames = [
    // FIXME: Get these names from stepwise config, and only do this if we know we're in the midst of a stepwise workflow.
//    'afformQuickAddIndividual',
    'afformTestForm2Activity1',
  ];
  $i = 0;
  foreach ($hookedAfformNames as $hookedAfformName) {
    $partials = $angular->getRawPartials($hookedAfformName);
    $alterHtmlFile = array_keys($partials)[0];
    $angular->add(\Civi\Angular\ChangeSet::create('stepw_test_' . $i++)
      ->alterHtml($alterHtmlFile, 'stepw_foo')
    );
  }
}


function stepw_afform_submit(\Civi\Afform\Event\AfformSubmitEvent $event) {
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

  // Bind our wrapper for API Events
  Civi::dispatcher()->addListener('civi.api.prepare', ['CRM_Stepw_APIWrapper', 'PREPARE'], -100);
  Civi::dispatcher()->addListener('civi.api.respond', ['CRM_Stepw_APIWrapper', 'RESPOND'], -100);
  Civi::dispatcher()->addListener('civi.afform.submit', 'stepw_afform_submit', 1000);
}

function stepw_civicrm_permission_check($permission, &$granted) {
  // FIXME: this permission is required for loading afform submission data (e.g.
  // stepwise form re-submission during 'back-button' handling), but of course
  // we should only grant it momentarily and only after confirming the (typically anonymous)
  // user is actual allowed to edit this submission as part of his current 
  // workflow instance.  
  if ($permission == 'administer afform') {
    $granted = true;
  }
  // FIXME: the 'administer afform' permission is STILL not enough for anonymous 
  // users (which our workflow users will be) to view prefilled afform submission
  // data (e.g., in a "back-button" form). But if we grant all permissions
  // with unconditional `$granted = true;`, then they do. So we need to figure
  // out which permission is needed and grant that (again, momentarily and only
  // with proper workflow-instance authentication.
  $granted = true;
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
