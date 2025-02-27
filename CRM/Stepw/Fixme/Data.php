<?php
use CRM_Stepw_ExtensionUtil as E;

class CRM_Stepw_Fixme_Data {
  public static function getSampleData() {
    return [
      '1' => [
        'steps' => [
          // TODO: when loading real data, ensure these are sorted in step order and keyed sequentially from 0.
          [
            'options' => [
              [
                'type' => 'url',
                'url' => 'http://plana.l/example-intro-page/',
                // Option labels are the labels to display ON THIS OPTION/PAGE
                // for each of the options (in order) that are available in the 
                // next step.
                // if these are omitted, 
                'optionLabels' => [
                  'Option 1 (page)',
                  'Option 2 (page)',
                  'Option 3 (afform)',
                ],
              ],
            ],
          ],
          [
            'options' => [
              [
                'type' => 'url',
                'url' => 'http://plana.l/option-step-option-1/',
                'optionLabels' => [
                  'Go to afform 2',
                ],
              ],
              [
                'type' => 'url',
                'url' => 'http://plana.l/option-step-option-2/',
                'optionLabels' => [
                  'Go to afform 2',
                ],
              ],
              [
                'type' => 'afform',
                'url' => 'http://plana.l/civicrm/test-form-start/',
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
                'url' => 'http://plana.l/civicrm/test-form-2-activity/',
                'afformName' => 'afformTestForm2Activity1',
                // Because this step has at least one afform option (this one),
                // the subsequent step MUST have only one option.
                'optionLabels' => [
                  'Submit and the end.',
                ],
              ],
            ],
          ],
//          [
//            'options' => [
//              [
//              ],
//            ],
//          ],
//          [
//            'options' => [
//              [
//              ],
//            ],
//          ],
//          
//          
//          
//          
//          [],
//          [
//            'type' => 'afform',
//            'url' => 'http://plana.l/civicrm/test-form-2-activity/',
//            'afformName' => 'afformTestForm2Activity1',
//            'buttonLabel' => 'Submit and Next',
//          ],
//          [
//            'type' => 'url',
//            'url' => 'http://plana.l/example-intro-page/',
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
//                'url' => 'http://plana.l/option-step-option-1/',
//                'option_label' => 'Option 1',
//                'buttonLabel' => 'Next (1)',
//              ],
//              [
//                'type' => 'url',
//                'url' => 'http://plana.l/option-step-option-2/',
//                'option_label' => 'Option 2',
//                'buttonLabel' => 'Next (2)',
//              ],
//              [
//                'type' => 'afform',
//                'url' => 'http://plana.l/civicrm/test-form-2-activity/',
//                'afformName' => 'afformTestForm2Activity1',
//                'buttonLabel' => 'Submit and Next (3)',
//                'option_label' => 'Option 3 (afform)',
//              ],
//            ],            
//          ],
//          [
//            'type' => 'afform',
//            'url' => 'http://plana.l/civicrm/test-form-3-activity-2/',
//            'afformName' => 'afformTestForm3Activity2',
//            'buttonLabel' => 'Submit and Next',
//          ],
//          [
//            'type' => 'url',
//            'url' => 'http://plana.l/example-final-page/',
//            'buttonLabel' => '',
//          ],
        ],
      ],
    ];
  }
}

  

