<?php

// This file declares a managed database record of type "ReportTemplate".
// The record will be automatically inserted, updated, or deleted from the
// database as appropriate. For more details, see "hook_civicrm_managed" at:
// https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_managed
return [
  [
    'name' => 'CRM_Stepw_Form_Report_WorkflowInstances',
    'entity' => 'ReportTemplate',
    'params' => [
      'version' => 3,
      'label' => 'Stepwise Workflow Submissions',
      'description' => 'Stepwise Workflow Submissions',
      'class_name' => 'CRM_Stepw_Form_Report_WorkflowInstances',
      'report_url' => 'com.joineryhq.stepw/workflowinstances',
      'component' => '',
    ],
  ],
];
