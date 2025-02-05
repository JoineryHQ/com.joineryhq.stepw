<?php
use CRM_Stepw_ExtensionUtil as E;

class CRM_Stepw_Fixme_Data {
  public static function getSampleData() {
    return [
      // FIXME: afform_name and post_id are not used at all, can be removed (unless we start using it later? Better grep.)
      '1' => [
        '1' =>  [
          'type' => 'post',
          'url' => 'http://plana.l/example-intro-page/',
        ],
        '2' =>  [
          'type' => 'afform',
          'url' => 'http://plana.l/civicrm/test-form-start/',
        ],
        '3' => [
          'type' => 'post',
          'url' => 'http://plana.l/example-video-page/',
        ],
        '4' =>  [
          'type' => 'afform',
          'url' => 'http://plana.l/civicrm/test-form-2-activity/',
        ],
        '5' => [
          'type' => 'post',
          'url' => 'http://plana.l/example-final-page/',
        ],
      ],
    ];
  }
}

  

