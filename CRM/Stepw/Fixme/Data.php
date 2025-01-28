<?php
use CRM_Stepw_ExtensionUtil as E;

class CRM_Stepw_Fixme_Data {
  public static function getSampleData() {
    return [
      // FIXME: afform_name and post_id are not used at all, can be removed (unless we start using it later? Better grep.)
      '1' => [
        '1' =>  [
          'type' => 'afform',
          'url' => 'http://plana.l/civicrm/example-form-1/',
        ],
        '2' => [
          'type' => 'post',
          'url' => 'http://plana.l/example-video-page/',
        ],
        '3' => [
          'type' => 'afform',
          'url' => 'http://plana.l/civicrm/form-test/',
        ],
      ],
    ];
  }
}

  

