<?php
use CRM_Stepw_ExtensionUtil as E;

class CRM_Stepw_Fixme_LoadData {
  public static function getSampleData() {
    $localDataFile = __DIR__ . '/_localData.php';
    if (file_exists($localDataFile)) {
      $data = require $localDataFile;
    }
    else {
      $data = [];
    }
    return $data;
  }
}

  

