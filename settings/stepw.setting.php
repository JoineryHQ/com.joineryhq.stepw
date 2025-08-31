<?php

use CRM_Stepw_ExtensionUtil as E;

return array(
  'stepw_debug_log' => array(
    'group_name' => 'Stepw Settings',
    'group' => 'stepw',
    'name' => 'stepw_debug_log',
    'add' => '5.0',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => E::ts('If yes, log additional debug output to ConfigAndLog/*stepw*.log'),
    'title' => E::ts('Log debug messages to file?'),
    'type' => 'Boolean',
    'quick_form_type' => 'YesNo',
    'default' => 0,
    'html_type' => '',
  ),
);
