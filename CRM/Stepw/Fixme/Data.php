<?php
use CRM_Stepw_ExtensionUtil as E;

class CRM_Stepw_Fixme_Data {
  public static function getSampleData() {
    return [
      '1' => [
        'steps' => [
          // TODO: when loading real data, ensure these are sorted in step order and keyed sequentially from 0.
          // fixme3: we need a data structure that can handle sub-step options, e.g. vidoe pages in 3 languages.
          [
            'type' => 'url',
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
            'type' => 'afform',
            'url' => 'http://plana.l/civicrm/test-form-2-activity/',
            'afform_name' => 'afformTestForm2Activity1',
            'button_label' => 'Submit and Next',
          ],
          [
            'type' => 'url',
            'url' => 'http://plana.l/example-video-page/',
            'button_label' => 'Next',
            'is_video_page' => TRUE,
          ],
          [
            'type' => 'afform',
            'url' => 'http://plana.l/civicrm/test-form-3-activity-2/',
            'afform_name' => 'afformTestForm3Activity2',
            'button_label' => 'Submit and Next',
          ],
          [
            'type' => 'url',
            'url' => 'http://plana.l/example-final-page/',
            'button_label' => '',
          ],
        ],
      ],
    ];
  }
}

  

