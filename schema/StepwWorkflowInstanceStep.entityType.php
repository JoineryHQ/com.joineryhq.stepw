<?php

use CRM_Stepw_ExtensionUtil as E;

return [
  'name' => 'StepwWorkflowInstanceStep',
  'table' => 'civicrm_stepw_workflow_instance_step',
  'class' => 'CRM_Stepw_DAO_StepwWorkflowInstanceStep',
  'getInfo' => fn() => [
    'title' => E::ts('Stepwise Workflow Instance Step'),
    'title_plural' => E::ts('Stepwise Workflow Instance Steps'),
    'description' => E::ts('A single step within a workflow instance.'),
    'icon' => 'fa-person-walking-arrow-right',
    'log' => TRUE,
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => E::ts('ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => E::ts('Unique StepwWorkflowInstanceStep ID'),
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'workflow_instance_id' => [
      'title' => E::ts('Workflow Instance ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'description' => E::ts('FK to StepwWorkflowInstance'),
      'required' => TRUE,
      'entity_reference' => [
        'entity' => 'StepwWorkflowInstance',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
    'step_number' => [
      'title' => E::ts('Step Number'),
      'sql_type' => 'tinyint(1) unsigned',
      'input_type' => 'Text',
      'description' => E::ts('Number of this step in the workflow (e.g. 1, 2, 3)'),
      'required' => TRUE,
    ],
    'activity_id' => [
      'title' => E::ts('Activity ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'description' => E::ts('FK to Activity'),
      'entity_reference' => [
        'entity' => 'Activity',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
    'url' => [
      'title' => ts('URL'),
      'sql_type' => 'varchar(2048)',
      'input_type' => 'Text',
      'description' => ts('URL'),
      'required' => TRUE,
    ],
  ],
  'getIndices' => fn() => [],
  'getPaths' => fn() => [],
];
