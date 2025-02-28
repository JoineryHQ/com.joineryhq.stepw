/**
 * on-page actions to handle stepwise progress bar.
 */

// This script may appear on WP posts OR on civicrm (afform) pages. Define a 
// consisten jQuery variable for both contexts.
if ((typeof CRM !== 'undefined') && '$' in CRM) {
  jQuery = CRM.$;
}
jQuery(function ($) {

  // Move progress bar to just before h1 tag.
  $('h1').before($('.stepwise-progress-wrapper'));
  $('.stepwise-progress-wrapper').show();


});

