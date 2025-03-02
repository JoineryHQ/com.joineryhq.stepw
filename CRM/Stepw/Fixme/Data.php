<?php
use CRM_Stepw_ExtensionUtil as E;

// fixme: skip progressbar (and omit from step total count) on first step?
// In some configurations, step1 may be of the type that doesn't need a progress
// bar and shouldn't be counted in the step total count -- i.e., it's a page
// that says "welcome" and offers a "start" button.
//

class CRM_Stepw_Fixme_Data {
  public static function getSampleData() {
    $ufBaseUrl = rtrim(CIVICRM_UF_BASEURL, '/');
    return [
      '1' => [
        'steps' => [
          // TODO: when loading real data, ensure these are sorted in step order and keyed sequentially from 0.
          [
            'options' => [
              [
                'type' => 'url',
                'url' => $ufBaseUrl . '/example-intro-page/',
                // Option labels are the labels to display ON THIS OPTION/PAGE
                // for each of the options (in order) that are available in the 
                // next step.
                // if these are omitted, 
                'optionLabels' => [
                  'Video Option 1 (page)',
                  'Video Option 2 (page)',
                ],
              ],
            ],
          ],
          [
            'options' => [
              [
                'type' => 'url',
                'url' => $ufBaseUrl . '/option-step-option-1/',
                'requireOnpageEnforcer' => 1,
                'optionLabels' => [
                  'Go to afform 1',
                ],
              ],
              [
                'type' => 'url',
                'url' => $ufBaseUrl . '/option-step-option-2/',
                'requireOnpageEnforcer' => 1,
                'optionLabels' => [
                  'Go to afform 1',
                ],
              ],
            ],
          ],
          [
            'options' => [
              [
                'type' => 'afform',
                'url' => $ufBaseUrl . '/civicrm/test-form-start/',
                'afformName' => 'afformTESTFormStart',
                // Because this step has at least one afform option (this one),
                // the subsequent step MUST have only one option.
                'optionLabels' => [
                  'Submit and Go to afform 2',
                ],
              ],
            ],
          ],
          [
            'options' => [
              [
                'type' => 'afform',
                'url' => $ufBaseUrl . '/civicrm/test-form-2-activity/',
                'afformName' => 'afformTestForm2Activity',
                // Because this step has at least one afform option (this one),
                // the subsequent step MUST have only one option.
                'optionLabels' => [
                  'Submit and view final step.',
                ],
              ],
            ],
          ],
          [
            'options' => [
              [
                'type' => 'url',
                'url' => $ufBaseUrl . '/example-final-page/',
                'optionLabels' => [
                  // This page shouldn't have a button, and even if it does,
                  // the button shortcode should not display it, because it's last.
                  'THIS SHOULD NOT DISPLAY',
                ],
              ],
            ],
          ],
//          
//          
//          
//          
//          [],
//          [
//            'type' => 'afform',
//            'url' => $ufBaseUrl . '/civicrm/test-form-2-activity/',
//            'afformName' => 'afformTestForm2Activity1',
//            'buttonLabel' => 'Submit and Next',
//          ],
//          [
//            'type' => 'url',
//            'url' => $ufBaseUrl . '/example-intro-page/',
//            'buttonLabel' => 'fixmeSplits: THIS WONT MATTER BECAUSE NEXT STEP IS "SPLIT"',
//          ],
//          [
//            // Note: a step of type 'split' MUST follow a step of type 'url',
//            // because the preceding step can only show the splits 
//            // if it's a WP page with the [stepwise-button] shortcode.
//            //
//            'type' => 'split',
//            'splits' => [
//              [
//                'type' => 'url',
//                'url' => $ufBaseUrl . '/option-step-option-1/',
//                'option_label' => 'Option 1',
//                'buttonLabel' => 'Next (1)',
//              ],
//              [
//                'type' => 'url',
//                'url' => $ufBaseUrl . '/option-step-option-2/',
//                'option_label' => 'Option 2',
//                'buttonLabel' => 'Next (2)',
//              ],
//              [
//                'type' => 'afform',
//                'url' => $ufBaseUrl . '/civicrm/test-form-2-activity/',
//                'afformName' => 'afformTestForm2Activity1',
//                'buttonLabel' => 'Submit and Next (3)',
//                'option_label' => 'Option 3 (afform)',
//              ],
//            ],            
//          ],
//          [
//            'type' => 'afform',
//            'url' => $ufBaseUrl . '/civicrm/test-form-3-activity-2/',
//            'afformName' => 'afformTestForm3Activity2',
//            'buttonLabel' => 'Submit and Next',
//          ],
        ],
      ],
    ];
  }
}

  

