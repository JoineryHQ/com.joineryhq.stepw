<?php

// Angular module stepwAfform.
// @see https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_angularModules
return [
  'js' => [
    'ang/stepwAfform.js',
    'ang/stepwAfform/*.js',
    'ang/stepwAfform/*/*.js',
  ],
  'css' => [
    'ang/stepwAfform.css',
  ],
  'partials' => [
    'ang/stepwAfform',
  ],
  'requires' => ['crmUi', 'crmUtil', 'ngRoute'],
  'settings' => [],
];
