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

  public static function redirectToInvalid(string $logMessage = '', string $errorCode) {
    if ($logMessage) {
      // If there's a logMessage, append a uniq log identifier both to the logMessage and to a publicMessage.
      // This will allow users to report something that will be meaningful in debugging/log-inspection.
      // This would be similar to what civicrm core does for ajax-context errors, as in
      // https://github.com/civicrm/civicrm-core/blob/5.81.0/CRM/Api4/Page/AJAX.php#L159
      $error_id = rtrim(chunk_split(CRM_Utils_String::createRandom(12, CRM_Utils_String::ALPHANUMERIC), 4, '-'), '-');
      $debugContext = [
        'error_id' => $error_id
      ];
      if (!empty($errorCode)) {
        $debugContext['error_code'] = $errorCode;
      }
      $debugContext['_REQUEST'] = $_REQUEST;
      \Civi::log()->debug(E::LONG_NAME .': '. $logMessage, $debugContext);
      // Store an additional informative message for display to the user.
      CRM_Stepw_State::singleton()->storeInvalidMessage(E::ts('When requesting help with this issue, please provide Log Reference Number: %1', ['1' => $error_id]));
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
