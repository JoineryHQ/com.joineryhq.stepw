<?php

/**
 * General-purpose utilities for stepw extension.
 *
 */
class CRM_Stepw_Utils_General {
  public static function generatePublicId() {
    return bin2hex(random_bytes(18));
  }

  public static function redirectToInvalid() {
    $redirect = CRM_Utils_System::url('civicrm/stepwise/invalid', '', FALSE, NULL, TRUE, TRUE);
    CRM_Utils_System::redirect($redirect);
  }
}
