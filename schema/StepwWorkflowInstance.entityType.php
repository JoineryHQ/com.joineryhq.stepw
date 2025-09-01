<?php

use CRM_Stepw_ExtensionUtil as E;

return [
  'name' => 'StepwWorkflowInstance',
  'table' => 'civicrm_stepw_workflow_instance',
  'class' => 'CRM_Stepw_DAO_StepwWorkflowInstance',
  'getInfo' => fn() => [
    'title' => E::ts('Stepwise Workflow Instance'),
    'title_plural' => E::ts('Stepwise Workflow Instances'),
    'description' => E::ts('Instance of a contact stepping through a workflow.'),
    'icon' => 'fa-arrows-turn-to-dots fa-flip-vertical',
    'log' => TRUE,
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => E::ts('ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => E::ts('Unique StepwWorkflowInstance ID'),
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'contact_id' => [
      'title' => E::ts('Contact ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'description' => E::ts('FK to Contact'),
      'required' => FALSE,
      'entity_reference' => [
        'entity' => 'Contact',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
    'workflow_id' => [
      'title' => E::ts('Workflow ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'description' => E::ts('FK to StepwWorkflow'),
      'required' => TRUE,
      'entity_reference' => [
        'entity' => 'StepwWorkflow',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
    'created' => [
      'title' => E::ts('Created'),
      'sql_type' => 'datetime',
      'input_type' => 'Select Date',
      'description' => E::ts('Date/time this WI was created (WI is created upon opening of first step'),
      'default' => 'CURRENT_TIMESTAMP',
    ],
    'closed' => [
      'title' => E::ts('Closed'),
      'sql_type' => 'datetime',
      'input_type' => 'Select Date',
      'description' => E::ts('Date/time this WI was closed (WI is closed when next-to-last step (before thank-you) is completed)'),
      'default' => NULL,
    ],
    'public_id' => [
      'title' => E::ts('Public ID'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'description' => E::ts('Long unguessable string identifier'),
      'required' => TRUE,
    ],
  ],
  'getIndices' => fn() => [
    'index_stepwWorkflowinstance_created' => [
      'fields' => [
        'created' => TRUE,
      ],
    ],
  ],
  'getPaths' => fn() => [],
];
