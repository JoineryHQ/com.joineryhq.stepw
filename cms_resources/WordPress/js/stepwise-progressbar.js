/**
 * on-page actions to handle stepwise progress bar.
 */

// This script may appear on WP posts OR on civicrm (afform) pages. Define a 
// consisten jQuery variable for both contexts.
if ((typeof CRM !== 'undefined') && '$' in CRM) {
  jQuery = CRM.$;
}
jQuery(function ($) {

  // Move progress bar to top of page.
  // If this is an afform, it will have a div#crm-main-content-wrapper; place 
  //  progressbar just before that element.
  // Otherwise, look for the first h1 tag and place progressbar just before it.
  // Note: If a WP page does not contain an h1 tag, the progress bar will not appear.
  var el;
  if ($('h1').length) {
    el = $('h1');
  }
  else {
    el = $('div#crm-main-content-wrapper');
  }
  $('.stepwise-progress-wrapper').insertBefore(el);
  $('.stepwise-progress-wrapper').show();


});

