<?php

/**
 * Stepw Exception class
 */
class CRM_Stepw_Exception extends CRM_Extension_Exception {

  public function __construct($message, $error_code = 0, $errorData = [], $previous = NULL) {
    parent::__construct($message, $error_code, $errorData, $previous);

    // Logging in the midst of an exception is not ideal, since we want to get
    // out as quickly as possible and avoid further errors.
    // However, exceptions in a CiviCRM AJAX context are (AFAICT) always simply
    // echoed back to the user, so that hook_civicrm_unhandled_exception never
    // fires; therefore, this is our only chance to log.
    // Therefore, if this is civicrm/ajax, immediately call our exception handler,
    // which shows a reasonable error message and also logs the error.
    if (strpos($_REQUEST['q'], 'civicrm/ajax') === 0) {
      CRM_Stepw_Utils_General::handleException($this, TRUE);
    }
  }

  /**
   * Alter the exception message to the given string.
   *
   * @param string $message
   */
  public function alterMessage($message) {
    $this->message = (string) $message;
  }

}
