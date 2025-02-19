<?php

use CRM_Stepw_ExtensionUtil as E;

/**
 * General-purpose utilities for stepw extension.
 *
 */
class CRM_Stepw_Utils_General {
  public static function generatePublicId() {
    // fixme3: append a (random?) letter to the string so it's guaranteed to never be numeric.
    return bin2hex(random_bytes(18));
  }

  public static function redirectToInvalid(string $message = '') {
    if ($message) {
      \Civi::log()->critical(E::LONG_NAME .': '. $message, $_REQUEST);      
      CRM_Stepw_State::singleton()->storeInvalidMessage($message);
    }
    $redirect = CRM_Utils_System::url('civicrm/stepwise/invalid', '', TRUE, NULL, FALSE);
    CRM_Utils_System::redirect($redirect);
  }

  public static function redirectToFinal() {
    $redirect = CRM_Utils_System::url('civicrm/stepwise/final', '', TRUE, NULL, FALSE);
    CRM_Utils_System::redirect($redirect);
  }
  
  public static function buildStepUrl($queryParams) {
    $url = CRM_Utils_System::url('civicrm/stepwise/step', $queryParams, TRUE, NULL, FALSE);    
    return $url;
  }
  
  public static function buildReloadUrl($queryParams) {
    $url = CRM_Utils_System::url('civicrm/stepwise/reload', $queryParams, TRUE, NULL, FALSE);    
    return $url;
  }
    
}
