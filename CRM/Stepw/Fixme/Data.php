<?php
use CRM_Stepw_ExtensionUtil as E;

class CRM_Stepw_Fixme_Data {
  public static function getSampleData() {
    return [
      '1' => [
        'steps' => [
          // TODO: when loading real data, ensure these are sorted in step order.
          '1' =>  [
            'type' => 'post',
            'url' => 'http://plana.l/example-intro-page/',
          ],
          '2' =>  [
            'type' => 'afform',
            'url' => 'http://plana.l/civicrm/test-form-start/',
            'afform_name' => 'afformTESTFormStart',
          ],
          '3' => [
            'type' => 'post',
            'url' => 'http://plana.l/example-video-page/',
          ],
          '4' =>  [
            'type' => 'afform',
            'url' => 'http://plana.l/civicrm/test-form-2-activity/',
            'afform_name' => 'afformTestForm2Activity1',
          ],
          '5' => [
            'type' => 'post',
            'url' => 'http://plana.l/example-final-page/',
          ],
        ],
      ],
    ];
  }
}

  

