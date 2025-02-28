/**
 * on-page actions to handle elements created by [stepwise-button] shortcode.
 */

jQuery(function ($) {

  /**
   * Onpage enforcement handler.
   */
  stepwEnforcer = {
    /**
     * Do whatever is appropriate on the page when onpage enforcmement has passed.
     */
    passEnforcement: function passEnforcement() {
      $('a.stepwise-button.stepwise-button-disabled').each(function (idx, el) {
        $(el).removeClass('stepwise-button-disabled');
        var href = atob($(el).data('data'));
        $(el).attr('href', href);
      });
    },
    /**
     * Click handler. If clicked element has certain atributes, preventDefault.
     */
    clickHandler: function clickHandler(e) {
      if ($(this).hasClass('stepwise-button-disabled')) {
        // Element has 'stepwise-button-disabled' class, so preventDefault().
        e.preventDefault();
      }
    }
  }

  // Move progress bar to just before h1 tag.
  $('h1').before($('.stepwise-progress-wrapper'));
  $('.stepwise-progress-wrapper').show();

  // Add a click handler for disabled step links.
  $('a.stepwise-button.stepwise-button-disabled').click(stepwEnforcer.clickHandler);


});

