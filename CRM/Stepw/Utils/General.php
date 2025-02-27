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

  public static function redirectToInvalid(CRM_Stepw_Exception $e) {
    
    // Add a uniq log identifier both to the logMessage and to a publicMessage.
    // This will allow users to report something that will be meaningful in debugging/log-inspection.
    // This would be similar to what civicrm core does for ajax-context errors, as in
    // https://github.com/civicrm/civicrm-core/blob/5.81.0/CRM/Api4/Page/AJAX.php#L159
    $errorId = rtrim(chunk_split(CRM_Utils_String::createRandom(12, CRM_Utils_String::ALPHANUMERIC), 4, '-'), '-');
    $publicMessage = E::ts('When requesting help with this issue, please provide Log Reference Number: %1', ['1' => $errorId]);

    $logMessage = $e->getMessage();
    $errorCode = $e->getErrorCode();
    $errorData = $e->getErrorData();
    unset($errorData['error_code']);
    
    // Assemble context for the logged error.
    $debugContext = [
      'error_id' => $errorId,
    ];
    if (!empty($errorCode)) {
      // Add error_code, if any.
      $debugContext['error_code'] = $errorCode;
    }
    if (!empty($errorData)) {
      // Add error_code, if any.
      $debugContext['error_data'] = $errorData;
    }
    $debugContext['_REQUEST'] = $_REQUEST;
    
    \Civi::log()->debug(E::LONG_NAME .': '. $logMessage, $debugContext);
    
    // Store an additional informative message for display to the user.
    CRM_Stepw_State::singleton()->storeInvalidMessage($publicMessage);

    if (Civi::settings()->get('debug_enabled')) {
      // If 'debug' is on, go ahead and show the original logged message to the user.
      CRM_Stepw_State::singleton()->storeInvalidMessage("Debug message: " . $logMessage);
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
