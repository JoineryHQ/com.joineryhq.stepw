<?php
use CRM_Stepw_ExtensionUtil as E;

class CRM_Stepw_Fixme_Data {
  public static function getSampleData() {
    return [
      '1' => [
        'steps' => [
          // TODO: when loading real data, ensure these are sorted in step order.
          // fixme: we need a data structure that can handle sub-step options, e.g. vidoe pages in 3 languages.
          // fixme: need to support afform_allow_resubmit attribute by prevent resubmission on afforms where this is false.
          [
            'type' => 'post',
            'url' => 'http://plana.l/example-intro-page/',
            'button_label' => 'Continue',
          ],
          [
            'type' => 'afform',
            'url' => 'http://plana.l/civicrm/test-form-start/',
            'afform_name' => 'afformTESTFormStart',
            'afform_prefill_individual' => FALSE,
            'afform_allow_resubmit' => TRUE,
            'button_label' => 'Submit and continue',
          ],
          [
            'type' => 'afform',
            'url' => 'http://plana.l/civicrm/test-form-2-activity/',
            'afform_name' => 'afformTestForm2Activity1',
            'afform_prefill_individual' => FALSE,
            'afform_allow_resubmit' => TRUE,
            'button_label' => 'Submit and Next',
          ],
          [
            'type' => 'post',
            'url' => 'http://plana.l/example-video-page/',
            'button_label' => 'Next',
          ],
          [
            'type' => 'afform',
            'url' => 'http://plana.l/civicrm/test-form-3-activity-2/',
            'afform_name' => 'afformTestForm3Activity2',
            'afform_prefill_individual' => FALSE,
            'afform_allow_resubmit' => TRUE,
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

  

