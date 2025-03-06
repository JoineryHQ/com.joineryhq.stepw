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
    return $data;
  }
}

  

