<?php
use CRM_Stepw_ExtensionUtil as E;

class CRM_Stepw_Fixme_Data {
  public static function getSampleData() {
    return [
      '1' => [
        'steps' => [
          // TODO: when loading real data, ensure these are sorted in step order.
          [
            'type' => 'post',
            'url' => 'http://plana.l/example-intro-page/',
          ],
          [
            'type' => 'afform',
            'url' => 'http://plana.l/civicrm/test-form-start/',
            'afform_name' => 'afformTESTFormStart',
          ],
          [
            'type' => 'post',
            'url' => 'http://plana.l/example-video-page/',
          ],
          [
            'type' => 'afform',
            'url' => 'http://plana.l/civicrm/test-form-2-activity/',
            'afform_name' => 'afformTestForm2Activity1',
          ],
          [
            'type' => 'post',
            'url' => 'http://plana.l/example-final-page/',
          ],
        ],
      ],
    ];
  }
}

  

