(function (angular, $, _) {
  /* Define an empty module. (This module only exists as a way to jam in some jQuery,
   * as described in https://civicrm.stackexchange.com/a/49339/907
   */
  angular.module('stepwAfsearchStepwiseWorkflows', CRM.angRequires('stepwAfsearchStepwiseWorkflows'));
})(angular, CRM.$, CRM._);

CRM.$(function ($) {

  function showCopyClipboardStatus(status, messageHtml) {
    if (status === true) {
      CRM.stepwAlert.fire({
        title: 'Copied',
        html: messageHtml,
        icon: 'success',
        confirmButtonText: 'OK'
      });      
    }
    else {
      messageHtml = ts("Sometimes this happens. Could be because of some setting in your browser? You'll need to copy the URL manually.");
      CRM.stepwAlert.fire({
        title: 'Could not copy',
        html: messageHtml,
        icon: 'error',
        confirmButtonText: 'Close'
      });
    }
  }

  /**
   * Fallback copy-to-clipboard function when browser doesn't provide navigator.clipboard.
   * Copied, with modificatinos for alerting,
   * from https://deanmarktaylor.github.io/clipboard-test/, referenced in https://stackoverflow.com/a/30810322/6476602
   *
   * @param String text Text to be written to clipboard.
   */
  function fallbackCopyTextToClipboard(text, successMessageHtml) {
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
      showCopyClipboardStatus(true, successMessageHtml);
    } catch (err) {
      showCopyClipboardStatus(false);
    }

    document.body.removeChild(textArea);

  }

  /**
   * Copy given text to clipboard.
   * Copied, with modificatinos for alerting,
   * from https://deanmarktaylor.github.io/clipboard-test/, referenced in https://stackoverflow.com/a/30810322/6476602
   *
   * @param String text Text to be written to clipboard.
   */
  function copyTextToClipboard(text, successMessageHtml) {
    if (!navigator.clipboard) {
      fallbackCopyTextToClipboard(text, successMessageHtml);
    } else {
      navigator.clipboard.writeText(text).then(function () {
        showCopyClipboardStatus(true, successMessageHtml);
      }, function (err) {
        showCopyClipboardStatus(false);
      });
    }
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
    copyTextToClipboard(url, ts('This URL is now in your clipboard:') + "\n<br>\n<br>" + url);
  }

  $('body').on('click', 'afsearch-stepwise-workflows tr a.stepw-workflow-url-copy', copyUrlToClipboard);

});

