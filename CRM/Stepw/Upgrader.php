<?php

use CRM_Stepw_ExtensionUtil as E;

/**
 * Collection of upgrade steps.
 */
class CRM_Stepw_Upgrader extends \CRM_Extension_Upgrader_Base {
  // By convention, functions that look like "function upgrade_NNNN()" are
  // upgrade tasks. They are executed in order (like Drupal's hook_update_N).

  /**
   * Create activity types and related custom fields for tracking Workflow Instances
   * and Steps. (Would have used managed entities for this, but the dependencies
   * between these entities makes that unworkable at present.
   *
   */
  public function install(): void {
    // Create activity type: Workflow Instance
    $results = \Civi\Api4\OptionValue::create()
      // If the install hook can execute, this api call should be allowed also.
      ->setCheckPermissions(FALSE)
      ->addValue('option_group_id.name', 'activity_type')
      ->addValue('label', E::ts('Stepwise Workflow Instance'))
      ->addValue('name', 'Stepwise_Workflow_Instance')
      ->addValue('description', E::ts('Contact has begun and/or completed a workflow.'))
      ->addValue('icon', 'fa-arrows-turn-to-dots fa-flip-vertical')
      ->addValue('is_reserved', TRUE)
      ->execute();
    $workflowInstanceOptionValueValue = $results[0]['value'];

    // Create activity type: Workflow Instance Step
    $results = \Civi\Api4\OptionValue::create()
      // If the install hook can execute, this api call should be allowed also.
      ->setCheckPermissions(FALSE)
      ->addValue('option_group_id.name', 'activity_type')
      ->addValue('label', E::ts('Stepwise Workflow Instance Step'))
      ->addValue('name', 'Stepwise_Workflow_Instance_Step')
      ->addValue('description', E::ts('Contact has completed a specific step within a workflow.'))
      ->addValue('icon', 'fa-person-walking-arrow-right')
      ->addValue('is_reserved', TRUE)
      ->execute();
    $workflowInstanceStepOptionValueValue = $results[0]['value'];

    // Create custom fields for activity type: Workflow Instance    
    $results = \Civi\Api4\CustomGroup::create()
      // If the install hook can execute, this api call should be allowed also.
      ->setCheckPermissions(FALSE)
      ->addValue('name', 'Stepwise_Workflow_Instance_Details')
      ->addValue('title', E::ts('Stepwise Workflow Instance Details'))
      ->addValue('extends', 'Activity')
      ->addValue('extends_entity_column_value', [$workflowInstanceOptionValueValue])
      ->addValue('style', 'Inline')
      ->addChain('name_me_0', \Civi\Api4\CustomField::create()
        ->addValue('custom_group_id', '$id')
        ->addValue('name', 'Workflow')
        ->addValue('label', 'Workflow')
        ->addValue('data_type', 'EntityReference')
        ->addValue('html_type', 'Autocomplete-Select')
        ->addValue('is_view', TRUE)
        ->addValue('fk_entity', 'StepwWorkflow')
      )
      ->execute();

    // Create custom fields for activity type: Workflow Instance Step
    $i = 0;
    $results = \Civi\Api4\CustomGroup::create()
      // If the install hook can execute, this api call should be allowed also.
      ->setCheckPermissions(FALSE)
      ->addValue('name', 'Stepwise_Workflow_Instance_Step_Details')
      ->addValue('title', E::ts('Stepwise Workflow Instance Step Details'))
      ->addValue('extends', 'Activity')
      ->addValue('extends_entity_column_value', [$workflowInstanceStepOptionValueValue])
      ->addValue('style', 'Inline')
      ->addChain('name_me_' . $i++, \Civi\Api4\CustomField::create()
        ->addValue('custom_group_id', '$id')
        ->addValue('name', 'Stepwise_Workflow_Instance_Activity')
        ->addValue('label', E::ts('Stepwise Workflow Instance Activity'))
        ->addValue('data_type', 'EntityReference')
        ->addValue('html_type', 'Autocomplete-Select')
        ->addValue('is_view', TRUE)
        ->addValue('fk_entity', 'Activity')
      )
      ->addChain('name_me_' . $i++, \Civi\Api4\CustomField::create()
        ->addValue('custom_group_id', '$id')
        ->addValue('name', 'Step_URL')
        ->addValue('label', E::ts('Step URL'))
        ->addValue('data_type', 'Link')
        ->addValue('html_type', 'Link')
        ->addValue('is_view', TRUE)
      )
      ->addChain('name_me_' . $i++, \Civi\Api4\CustomField::create()
        ->addValue('custom_group_id', '$id')
        ->addValue('name', 'Activity_created_by_this_step')
        ->addValue('label', E::ts('Activity created by this step'))
        ->addValue('data_type', 'EntityReference')
        ->addValue('html_type', 'Autocomplete-Select')
        ->addValue('is_view', TRUE)
        ->addValue('fk_entity', 'Activity')
      )
      ->execute();
  }

  /**
   * Example: Work with entities usually not available during the install step.
   *
   * This method can be used for any post-install tasks. For example, if a step
   * of your installation depends on accessing an entity that is itself
   * created during the installation (e.g., a setting or a managed entity), do
   * so here to avoid order of operation problems.
   */
  // public function postInstall(): void {
  //  $customFieldId = civicrm_api3('CustomField', 'getvalue', array(
  //    'return' => array("id"),
  //    'name' => "customFieldCreatedViaManagedHook",
  //  ));
  //  civicrm_api3('Setting', 'create', array(
  //    'myWeirdFieldSetting' => array('id' => $customFieldId, 'weirdness' => 1),
  //  ));
  // }

  /**
   * Remove activity types and related custom fields that were created in self::install().
   *
   */
  public function uninstall(): void {
    // remove custom fields
    $customFields = \Civi\Api4\CustomField::get()
      // If the uninstall hook can execute, this api call should be allowed also.
      ->setCheckPermissions(FALSE)
      ->addWhere('custom_group_id:name', 'IN', ['Stepwise_Workflow_Instance_Details', 'Stepwise_Workflow_Instance_Step_Details'])
      ->addChain('name_me_0', \Civi\Api4\CustomField::delete()
        ->addWhere('id', '=', '$id')
      )
      ->execute();

    // remove custom groups
    $customGroups = \Civi\Api4\CustomGroup::get()
      // If the uninstall hook can execute, this api call should be allowed also.
      ->setCheckPermissions(FALSE)
      ->addWhere('name', 'IN', ['Stepwise_Workflow_Instance_Details', 'Stepwise_Workflow_Instance_Step_Details'])
      ->addChain('name_me_0', \Civi\Api4\CustomGroup::delete()
        ->addWhere('id', '=', '$id')
      )
      ->execute();

    // remove activity types
    $optionValues = \Civi\Api4\OptionValue::get()
      // If the uninstall hook can execute, this api call should be allowed also.
      ->setCheckPermissions(FALSE)
      ->addWhere('option_group_id:name', '=', 'activity_type')
      ->addWhere('name', 'IN', ['Stepwise_Workflow_Instance', "Stepwise_Workflow_Instance_Step'"])
      ->addChain('name_me_0', \Civi\Api4\OptionValue::delete()
        ->addWhere('id', '=', '$id')
      )
    ->execute();    
  }

  /**
   * Example: Run a simple query when a module is enabled.
   */
  // public function enable(): void {
  //  CRM_Core_DAO::executeQuery('UPDATE foo SET is_active = 1 WHERE bar = "whiz"');
  // }

  /**
   * Example: Run a simple query when a module is disabled.
   */
  // public function disable(): void {
  //   CRM_Core_DAO::executeQuery('UPDATE foo SET is_active = 0 WHERE bar = "whiz"');
  // }

  /**
   * Example: Run a couple simple queries.
   *
   * @return TRUE on success
   * @throws CRM_Core_Exception
   */
  // public function upgrade_4200(): bool {
  //   $this->ctx->log->info('Applying update 4200');
  //   CRM_Core_DAO::executeQuery('UPDATE foo SET bar = "whiz"');
  //   CRM_Core_DAO::executeQuery('DELETE FROM bang WHERE willy = wonka(2)');
  //   return TRUE;
  // }

  /**
   * Example: Run an external SQL script.
   *
   * @return TRUE on success
   * @throws CRM_Core_Exception
   */
  // public function upgrade_4201(): bool {
  //   $this->ctx->log->info('Applying update 4201');
  //   // this path is relative to the extension base dir
  //   $this->executeSqlFile('sql/upgrade_4201.sql');
  //   return TRUE;
  // }

  /**
   * Example: Run a slow upgrade process by breaking it up into smaller chunk.
   *
   * @return TRUE on success
   * @throws CRM_Core_Exception
   */
  // public function upgrade_4202(): bool {
  //   $this->ctx->log->info('Planning update 4202'); // PEAR Log interface
  //   $this->addTask(E::ts('Process first step'), 'processPart1', $arg1, $arg2);
  //   $this->addTask(E::ts('Process second step'), 'processPart2', $arg3, $arg4);
  //   $this->addTask(E::ts('Process second step'), 'processPart3', $arg5);
  //   return TRUE;
  // }
  // public function processPart1($arg1, $arg2) { sleep(10); return TRUE; }
  // public function processPart2($arg3, $arg4) { sleep(10); return TRUE; }
  // public function processPart3($arg5) { sleep(10); return TRUE; }

  /**
   * Example: Run an upgrade with a query that touches many (potentially
   * millions) of records by breaking it up into smaller chunks.
   *
   * @return TRUE on success
   * @throws CRM_Core_Exception
   */
  // public function upgrade_4203(): bool {
  //   $this->ctx->log->info('Planning update 4203'); // PEAR Log interface
  //   $minId = CRM_Core_DAO::singleValueQuery('SELECT coalesce(min(id),0) FROM civicrm_contribution');
  //   $maxId = CRM_Core_DAO::singleValueQuery('SELECT coalesce(max(id),0) FROM civicrm_contribution');
  //   for ($startId = $minId; $startId <= $maxId; $startId += self::BATCH_SIZE) {
  //     $endId = $startId + self::BATCH_SIZE - 1;
  //     $title = E::ts('Upgrade Batch (%1 => %2)', array(
  //       1 => $startId,
  //       2 => $endId,
  //     ));
  //     $sql = '
  //       UPDATE civicrm_contribution SET foobar = apple(banana()+durian)
  //       WHERE id BETWEEN %1 and %2
  //     ';
  //     $params = array(
  //       1 => array($startId, 'Integer'),
  //       2 => array($endId, 'Integer'),
  //     );
  //     $this->addTask($title, 'executeSql', $sql, $params);
  //   }
  //   return TRUE;
  // }
}
