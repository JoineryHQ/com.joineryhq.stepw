/**
 * Object to handle alerts display.
 */
CRM.stepwAlert = {

  /**
   * Wrapper around Swal.fire()
   * @param {array} parameters
   * @param {boolean} fallBackToAlert
   */
  fire: function (parameters, fallBackToAlert) {
    if (typeof fallBackToAlert === 'undefined') {
      fallBackToAlert = true;
    }
    if (typeof Swal === 'function') {
      Swal.fire(parameters);
    } else if (fallBackToAlert) {
      window.alert(parameters.title + '\n\n' + parameters.text);
    }
  }
};