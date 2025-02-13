<?php
use CRM_Stepw_ExtensionUtil as E;

class CRM_Stepw_Fixme_Data {
  public static function getSampleData() {
    return [
      '1' => [
        'steps' => [
          // TODO: when loading real data, ensure these are sorted in step order.
          // fixme: we need a data structure that can handle sub-step options, e.g. vidoe pages in 3 languages.
          [
            'type' => 'post',
            'url' => 'http://plana.l/example-intro-page/',
            'button_label' => 'Continue',
          ],
          [
            'type' => 'afform',
            'url' => 'http://plana.l/civicrm/test-form-start/',
            'afform_name' => 'afformTESTFormStart',
            'button_label' => 'Submit and continue',
          ],
          [
            'type' => 'post',
            'url' => 'http://plana.l/example-video-page/',
            'button_label' => 'Next',
          ],
          [
            'type' => 'afform',
            'url' => 'http://plana.l/civicrm/test-form-2-activity/',
            'afform_name' => 'afformTestForm2Activity1',
            'button_label' => 'Submit and Next',
          ],
          [
            'type' => 'post',
            'url' => 'http://plana.l/example-final-page/',
            'button_label' => '',
          ],
        ],
      ],
    ];
  }
}

  

