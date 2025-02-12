<?php

// Angular module crmStepw.
// @see https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_angularModules
return [
  'js' => [
    'ang/crmStepw.js',
    'ang/crmStepw/*.js',
    'ang/crmStepw/*/*.js',
  ],
  'css' => [
    'ang/crmStepw.css',
  ],
  'partials' => ['ang/crmStepw'],
  'requires' => ['crmUi', 'crmUtil', 'ngRoute'],
  'settings' => [],
];
