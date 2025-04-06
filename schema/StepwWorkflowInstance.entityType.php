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
      'required' => TRUE,
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
      'title' => E::ts('Workflow ID'),
      'sql_type' => 'datetime',
      'input_type' => 'Select Date',
      'description' => E::ts('Date/time this WI was created (WI entry is created upon WI closure/completion'),
      'default' => 'CURRENT_TIMESTAMP',
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
