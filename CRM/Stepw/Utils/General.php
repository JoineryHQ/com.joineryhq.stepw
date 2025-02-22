<?php

use CRM_Stepw_ExtensionUtil as E;

/**
 * General-purpose utilities for stepw extension.
 *
 */
class CRM_Stepw_Utils_General {
  public static function generatePublicId() {
    $ret = bin2hex(random_bytes(18));
    
    // append a pseudo-random letter to the string so it's guaranteed to never be numeric.
    $chars = 'abcdefghijklmnopqrstuvwxyz';
    $alpha = substr(str_shuffle($chars), 0, 1);
    
    $ret .= $alpha;
    
    return $ret;
  }

  public static function redirectToInvalid(string $logMessage = '', string $publicMessage = '') {
    if ($logMessage) {
      \Civi::log()->debug(E::LONG_NAME .': '. $logMessage, $_REQUEST);
    }
    if ($publicMessage) {
      CRM_Stepw_State::singleton()->storeInvalidMessage($publicMessage);
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
