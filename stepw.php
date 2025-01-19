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
  $button->html('foobar');
}

function stepw_civicrm_alterAngular(\Civi\Angular\Manager $angular) {
  /* If I know the $name of the saved form, eg. afformQuickAddIndividual, I can
   * get the path ("~/afformQuickAddIndividual/afformQuickAddIndividual.aff.html")
   * by calling _afform_get_partials($name), or perhaps better, \Civi\Angular\Manager::getRawPartials($name).
   *
   */

  $hookedAfformNames = ['afformQuickAddIndividual'];
  $i = 0;
  foreach ($hookedAfformNames as $hookedAfformName) {
    $partials = $angular->getRawPartials($hookedAfformName);
    $alterHtmlFile = array_keys($partials)[0];
    $angular->add(\Civi\Angular\ChangeSet::create('stepw_test_' . $i++)
      ->alterHtml($alterHtmlFile, 'stepw_foo')
    );
  }
  $a = 1;
}

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function stepw_civicrm_config(&$config): void {
  _stepw_civix_civicrm_config($config);

  // Bind our wrapper for API Events
  //  Civi::dispatcher()->addListener('civi.api.prepare', ['CRM_Stepw_APIWrapper', 'PREPARE'], -100);
  Civi::dispatcher()->addListener('civi.api.respond', ['CRM_Stepw_APIWrapper', 'RESPOND'], -100);

  $get = $_GET;
  $a = 1;
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
