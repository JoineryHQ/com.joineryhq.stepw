<?php

// Angular module stepwAfsearchStepwiseWorkflows.
// @see https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_angularModules
return [
  'js' => [
    'js/stepwAlert.js',
    'ang/stepwAfsearchStepwiseWorkflows.js',
    'ang/stepwAfsearchStepwiseWorkflows/*.js',
    'ang/stepwAfsearchStepwiseWorkflows/*/*.js',
  ],
  'css' => [
    'ang/stepwAfsearchStepwiseWorkflows.css',
  ],
  'partials' => [
    'ang/stepwAfsearchStepwiseWorkflows',
  ],
  'requires' => ['crmUi', 'crmUtil', 'ngRoute'],
  'settings' => [],
];
