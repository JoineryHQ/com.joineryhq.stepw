<?php
use CRM_Stepw_ExtensionUtil as E;

class CRM_Stepw_Fixme_LoadData {
  public static function getSampleData() {
    // Preferred location for server-local workflow data config file is adjacent to civicrm.settings.php
    $civicrmSettingsDir = dirname(CIVICRM_SETTINGS_PATH ?? '');
    if (file_exists($civicrmSettingsDir . '/_stepwLocalData.php')) {
      $localDataFile = $civicrmSettingsDir . '/_stepwLocalData.php';
    }
    else {
      // Alternate (previously the only) location is adjacent to this file.
      $localDataFile = __DIR__ . '/_localData.php';
    }
    if (file_exists($localDataFile)) {
      $data = require $localDataFile;
    }
    else {
      $data = [];
    }
    
    // If any configured workflow is not already mirrored in a StepWorkflow entity,
    // make it so. (Obviously this won't be needed after when we're storing configs
    // in the DB instead of a file.
    self::primeDbWorkflowEntities($data);
    
    return $data;
  }
  
  private static function primeDbWorkflowEntities($data) {
    foreach ($data as $workflowId => $workflow) {
      $stepwWorkflowCount = \Civi\Api4\StepwWorkflow::get()
        ->setCheckPermissions(FALSE)
        ->addWhere('id', '=', $workflowId)
        ->execute()
        ->count();
      if (!$stepwWorkflowCount) {
        $publicId = $workflow['settings']['public_id'];
        $title = $workflow['settings']['title'];
        $results = \Civi\Api4\StepwWorkflow::create()
          ->setCheckPermissions(FALSE)
          ->addValue('title', $title)
          ->addValue('public_id', $publicId)
          ->execute();        
      }
    }
  }
}

  

