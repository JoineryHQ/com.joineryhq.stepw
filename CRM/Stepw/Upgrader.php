<?php
use CRM_Stepw_ExtensionUtil as E;
/**
 * Collection of upgrade steps.
 */
class CRM_Stepw_Upgrader extends \CRM_Extension_Upgrader_Base {

  // By convention, functions that look like "function upgrade_NNNN()" are
  // upgrade tasks. They are executed in order (like Drupal's hook_update_N).

  /**
   * Example: Run an external SQL script when the module is installed.
   *
   * Note that if a file is present sql\auto_install that will run regardless of this hook.
   */
  // public function install(): void {
  //   $this->executeSqlFile('sql/my_install.sql');
  // }

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
   * Example: Run an external SQL script when the module is uninstalled.
   *
   * Note that if a file is present sql\auto_uninstall that will run regardless of this hook.
   */
  // public function uninstall(): void {
  //   $this->executeSqlFile('sql/my_uninstall.sql');
  // }

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
   * Alter civicrm_stepw_workflow for reporting.
   *
   * @return TRUE on success
   * @throws CRM_Core_Exception
   */
   public function upgrade_4200(): bool {
     $this->ctx->log->info('Applying update 4200');
     CRM_Core_DAO::executeQuery('ALTER TABLE civicrm_stepw_workflow ADD report_instance_id int(10) unsigned NULL COMMENT "FK to report_instance ID" AFTER public_id');
     CRM_Core_DAO::executeQuery('ALTER TABLE civicrm_stepw_workflow ADD CONSTRAINT FK_civicrm_stepw_workflow_report_instance_id FOREIGN KEY (report_instance_id) REFERENCES civicrm_report_instance (id) ON DELETE SET NULL');
     return TRUE;
   }

  /**
   * Alter civicrm_stepw_workflow for active step logging.
   *
   * @return TRUE on success
   * @throws CRM_Core_Exception
   */
   public function upgrade_4201(): bool {
     $this->ctx->log->info('Applying update 4201');
     // civicrm_stepw_workflow_instance: add `closed` column and change comment on `created`
     CRM_Core_DAO::executeQuery("
      ALTER TABLE civicrm_stepw_workflow_instance
      CHANGE contact_id contact_id int(10) unsigned NULL COMMENT 'FK to Contact' AFTER id,
      CHANGE  created created datetime DEFAULT current_timestamp() COMMENT 'Date/time this WI was created (WI is created upon opening of first step)',
      ADD closed datetime NULL comment 'Date/time this WI was closed (WI is closed when next-to-last step (before thank-you) is completed)'
    ");
     // civicrm_stepw_workflow_instance: for all existign rows, set `closed` = `created`, because they were previously
     // the same thing.
     CRM_Core_DAO::executeQuery("
      UPDATE civicrm_stepw_workflow_instance
      SET closed = created
      WHERE closed IS NULL
    ");

     // civicrm_stepw_workflow_instance_step:
     // add columns `afform_submission_id`, `dreated`
     CRM_Core_DAO::executeQuery("
      ALTER TABLE civicrm_stepw_workflow_instance_step
      ADD afform_submission_id int(10) unsigned COMMENT 'FK to Afform Submission',
      ADD created datetime DEFAULT current_timestamp() comment 'Date/time this step was initiated by user.',
      ADD completed datetime NULL comment 'Date/time this step was most recently completed.'
    ");
     CRM_Core_DAO::executeQuery("
      ALTER TABLE civicrm_stepw_workflow_instance_step
      ADD FOREIGN KEY (afform_submission_id) REFERENCES civicrm_afform_submission (id) ON DELETE CASCADE ON UPDATE RESTRICT
    ");
     CRM_Core_DAO::executeQuery("
      ALTER TABLE civicrm_stepw_workflow_instance_step
      ADD UNIQUE index_step_number_workflow_instance_id (step_number, workflow_instance_id);
    ");
     return TRUE;
   }

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
