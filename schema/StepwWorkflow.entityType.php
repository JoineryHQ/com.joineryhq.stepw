<?php
use CRM_Stepw_ExtensionUtil as E;

return [
  'name' => 'StepwWorkflow',
  'table' => 'civicrm_stepw_workflow',
  'class' => 'CRM_Stepw_DAO_StepwWorkflow',
  'getInfo' => fn() => [
    'title' => E::ts('StepwWorkflow'),
    'title_plural' => E::ts('StepwWorkflows'),
    'description' => E::ts('Stewise workflow'),
    'log' => TRUE,
    'label_field' => 'title',
    'icon' => 'fa-arrows-turn-to-dots fa-flip-vertical',
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => E::ts('ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => E::ts('Unique StepwWorkflow ID'),
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'title' => [
      'title' => E::ts('Title'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'description' => E::ts('Administrative title'),
    ],
    'public_id' => [
      'title' => E::ts('Public ID'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'description' => E::ts('Long unguessable string identifier'),
    ],
  ],
  'getIndices' => fn() => [
    'index_stepwWorkflow_title' => [
      'fields' => [
        'title' => TRUE,
      ],
      'unique' => TRUE,      
    ],
    'index_stepwWorkflow_public_id' => [
      'fields' => [
        'public_id' => TRUE,
      ],
      'unique' => TRUE,      
    ],
  ],
  'getPaths' => fn() => [],
];
