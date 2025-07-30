<?php
// Configure this file for workflows on the current (local) server, to be stored
// adjacent to civicrm.settings.php
//
// FYI: top-level array keys are for developer information only; they are not
// used by the extension (civicrm_stepw_workflow.id is simple auto-increment).
//
$ufBaseUrl = rtrim(CIVICRM_UF_BASEURL, '/');
return [
  '1' => [
    'settings' => [
      'progressOmitFirstStep' => FALSE,
      'public_id' => 'd3778d9b27c29b52afe442ebce572bbcca23k',
    ],
    'steps' => [
      // TODO: when loading real data, ensure these are sorted in step order and keyed sequentially from 0.
      [
        'options' => [
          [
            'type' => 'url',
            'url' => $ufBaseUrl . '/prevention/welcome-workflow-1',
            // Option labels are the labels to display ON THIS OPTION/PAGE
            // for each of the options (in order) that are available in the
            // next step.
            // if these are omitted,
            'optionLabels' => [
              'Video Option A (page)',
              'Video Option B (page)',
            ],
          ],
        ],
      ],
      [
        'options' => [
          [
            'type' => 'url',
            'url' => $ufBaseUrl . '/prevention/workflow-1-video-a/',
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
            'afformName' => 'afformTESTFormStart',
            // Because this step has at least one afform option (this one),
            // the subsequent step MUST have only one option.
            'optionLabels' => [
              'Submit and Go to afform 2',
            ],
            'postSubmitValidation' => [
              // FIXME: remove 'where' and do this with Smart Groups, not where clauses (it's more flexible, and less development)
              // LIKE SO: 'smartGroupId' => 1,
              'where' => [
                'Individual1' => [
                  ['age_years', '>=', 18],
                  ['age_years', '<=', 29],
                ],
              ],
              'failureMessage' => '<p>You do not meet the requirements for this program.</p>',
            ],
          ],
        ],
      ],
      [
        'options' => [
          [
            'type' => 'afform',
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
    ],
  ],
];
