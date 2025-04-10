/**
 * Object to handle alerts display.
 */
CRM.stepwAlert = {

  /**
   * Wrapper around sweetalert's Swal.fire()
   * @param object parameters Paramters as for Swal.fire():
   *  - parameters.text, if not given or empty, will be calculated by stripping
   *    html tags from parameters.html.
   * @param boolean fallBackToAlert Default true; if false, no alert will be shown
   *    when Swal.fire() is unavailable.
   */
  fire: function (parameters, fallBackToAlert) {
    if (typeof fallBackToAlert === 'undefined') {
      fallBackToAlert = true;
    }
    if (typeof Swal === 'function') {
      Swal.fire(parameters);
    } else if (fallBackToAlert) {
      if (typeof parameters.text === 'undefined' || parameters.text == '') {
        parameters.text = CRM.$('<div>' + parameters.html + '</div>').text();
      }
      window.alert(parameters.title + '\n\n' + parameters.text);
    }
  }
};