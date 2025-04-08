(function (angular, $, _) {
  /* Define an empty module. (This module only exists as a way to jam in some jQuery,
   * as described in https://civicrm.stackexchange.com/a/49339/907
   */
  angular.module('stepwAfsearchStepwiseWorkflows', CRM.angRequires('stepwAfsearchStepwiseWorkflows'));
})(angular, CRM.$, CRM._);

CRM.$(function ($) {
  var copyFailMessage = ts('Sometimes this happens. Could be because of some setting in your browser? You\'ll need to copy the URL manually.');

  /**
   * Fallback copy-to-clipboard function when browser doesn't provide navigator.clipboard.
   * Copied, with modificatinos for alerting,
   * from https://deanmarktaylor.github.io/clipboard-test/, referenced in https://stackoverflow.com/a/30810322/6476602
   *
   * @param String text Text to be written to clipboard.
   * @return True on success; False on failure.
   */
  function fallbackCopyTextToClipboard(text) {
    var ret = false;
    var textArea = document.createElement("textarea");
    textArea.value = text;

    // Avoid scrolling to bottom
    textArea.style.top = "0";
    textArea.style.left = "0";
    textArea.style.position = "fixed";

    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();

    try {
      document.execCommand('copy');
      ret = true;
    } catch (err) {
      CRM.stepwAlert.fire({
        title: 'Could not copy URL',
        html: copyFailMessage,
        text: copyFailMessage,
        icon: 'error',
        confirmButtonText: 'Close'
      });
    }

    document.body.removeChild(textArea);

    return ret;
  }

  /**
   * Copy given text to clipboard.
   * Copied, with modificatinos for alerting,
   * from https://deanmarktaylor.github.io/clipboard-test/, referenced in https://stackoverflow.com/a/30810322/6476602
   *
   * @param String text Text to be written to clipboard.
   * @return True on success; False on failure.
   */
  function copyTextToClipboard(text) {
    var ret = false;
    if (!navigator.clipboard) {
      ret = fallbackCopyTextToClipboard(text);
    } else {
      navigator.clipboard.writeText(text).then(function () {
        ret = true;
      }, function (err) {
        CRM.stepwAlert.fire({
          title: 'Could not copy URL',
          html: copyFailMessage,
          text: copyFailMessage,
          icon: 'error',
          confirmButtonText: 'Close'
        });
      });
    }
    return ret;
  }

  /**
   * For the clicked element, identify the relevant URL data and copy it to the clipboard.
   *
   * @param Event e
   */
  var copyUrlToClipboard = function copyUrlToClipboard(e) {
    e.preventDefault();

    // Get the text field
    var url = $(this).closest('td').find('span.stepw-workflow-url').text();
    if (copyTextToClipboard(url)) {
      // Alert the copied text
      CRM.stepwAlert.fire({
        title: 'Copied URL',
        html: ts('This URL is now in your clipboard:') + "\n<br>\n<br>" + url,
        text: ts('This URL is now in your clipboard:') + "\n\n" + url,
        icon: 'success',
        confirmButtonText: 'OK'
      });
    }
  }

  $('body').on('click', 'afsearch-stepwise-workflows tr a.stepw-workflow-url-copy', copyUrlToClipboard);

});

