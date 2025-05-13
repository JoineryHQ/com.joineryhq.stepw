<?php

use CRM_Stepw_ExtensionUtil as E;

return [
  'name' => 'StepwWorkflow',
  'table' => 'civicrm_stepw_workflow',
  'class' => 'CRM_Stepw_DAO_StepwWorkflow',
  'getInfo' => fn() => [
    'title' => E::ts('Stepwise Workflow'),
    'title_plural' => E::ts('Stepwise Workflows'),
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
      'required' => TRUE,
    ],
    'public_id' => [
      'title' => E::ts('Public ID'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'description' => E::ts('Long unguessable string identifier'),
      'required' => TRUE,
    ],
    'report_instance_id' => [
      'title' => E::ts('Report'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'description' => ts('FK to report_instance ID'),
      'input_attrs' => [
        'label' => E::ts('Report'),
      ],
      'entity_reference' => [
        'entity' => 'ReportInstance',
        'key' => 'id',
        'on_delete' => 'SET NULL',
      ],
    ],
    'is_active' => [
      'title' => ts('Workflow Is Active'),
      'sql_type' => 'boolean',
      'input_type' => 'CheckBox',
      'description' => ts('Is this workflow active?'),
      'default' => TRUE,
      'input_attrs' => [
        'label' => ts('Enabled'),
      ],
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
    'index_stepwWorkflow_is_active' => [
      'fields' => [
        'is_active' => TRUE,
      ],
    ],
  ],
  'getPaths' => fn() => [],
];
