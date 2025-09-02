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
    // (Important for things like CRM_Stepw_WorkflowInstance::getStepByKey(), which
    // treats its arguments differently based on whether they are numeric or not.)
    $chars = 'abcdefghijklmnopqrstuvwxyz';
    $alpha = substr(str_shuffle($chars), 0, 1);

    $ret .= $alpha;

    return $ret;
  }

  public static function generateLogId() {
    $errorId = rtrim(chunk_split(CRM_Utils_String::createRandom(12, CRM_Utils_String::ALPHANUMERIC), 4, '-'), '-');
    return $errorId;
  }

  /**
   * Handle verbose logging if enabled.
   *
   * @param Array|String $message The value to be logged
   * @param String $label Optional label (if null, E::SHORT_NAME will be used)
   * @param bool $coreLog If true, log to civicrm core log; otherwise log to our own log.
   *
   * @return bool True if verbose logging is enabled; otherwise FALSE.
   */
  public static function debugLog($message, $label = E::SHORT_NAME, $coreLog = FALSE) {
    if (!Civi::settings()->get('stepw_debug_log')) {
      return FALSE;
    }

    $prefix = ($coreLog ? '' : E::SHORT_NAME);

    if (is_string($message)) {
      $message = ['message' => $message];
    }

    $extraDataKey = __FUNCTION__ . '_extra';
    $message[$extraDataKey] = [
      'ip address' => $_SERVER['REMOTE_ADDR'],
      'workflow instance identifier' => self::getLogWorkflowLabel(),
    ];
    if (!empty($message['exception'])) {
      // If this is an exception message, append full stepw state for inspection.
      $message[$extraDataKey]['com.joineryhq.stepw_STATE'] = $_SESSION['CiviCRM']['com.joineryhq.stepw_STATE'];
    }

    // Convert $vars to a dump string; this has the desirable side effect
    // of exposing the properties of any exception contained in $message, which
    // properties are otherwise protected from the output.
    $messageDump = (new \Symfony\Component\VarDumper\Dumper\CliDumper('php://output'))
      ->dump(
        (new \Symfony\Component\VarDumper\Cloner\VarCloner())->cloneVar($message),
        TRUE);
    CRM_Core_Error::debug_log_message("$label :: " . $messageDump, FALSE, $prefix);

    return TRUE;
  }

  /**
   * Get a string indicating the closest we can to a useful public_id: for the
   * workflow instance if possible, or else for the workflow.
   *
   * @return String e.g. "stepw_wiid=[public_id", "stepw_wid=[public_id]"
   */
  public static function getLogWorkflowLabel() {
    $checkUserParams = [
      ['request', CRM_Stepw_Utils_Userparams::QP_WORKFLOW_INSTANCE_ID],
      ['referer', CRM_Stepw_Utils_Userparams::QP_WORKFLOW_INSTANCE_ID],
      ['request', CRM_Stepw_Utils_Userparams::QP_START_WORKFLOW_ID],
      ['referer', CRM_Stepw_Utils_Userparams::QP_START_WORKFLOW_ID],
    ];
    foreach ($checkUserParams as $checkUserParam) {
      $val = CRM_Stepw_Utils_Userparams::getUserParams($checkUserParam[0], $checkUserParam[1]);
      if ($val) {
        return "{$checkUserParam[1]}=$val";
      }
    }
  }

  /**
   * Handle our custom exception class.
   *
   * @param CRM_Stepw_Exception $e
   * @param bool $isAjax If true, assume we're in an ajax call, and append an
   *   identifiable error code message. If false, prepare to display that message
   *   on-screen, and then redirect the user to our invalid/ page.
   */
  public static function handleException(CRM_Stepw_Exception $e, $isAjax = FALSE) {
    // Add a uniq log identifier both to the logMessage and to a publicMessage.
    // This will allow users to report something that will be meaningful in debugging/log-inspection.
    // This would be similar to what civicrm core does for ajax-context errors, as in
    // https://github.com/civicrm/civicrm-core/blob/5.81.0/CRM/Api4/Page/AJAX.php#L159
    $errorId = self::generateLogId();
    $publicMessage = E::ts('When requesting help with this issue, please provide Log Reference Number: %1', ['1' => $errorId]);

    $logMessage = $e->getMessage();
    $errorCode = $e->getErrorCode();
    $errorData = $e->getErrorData();
    unset($errorData['error_code']);

    // Assemble context for the logged error.
    $debugContext = [
      'error_id' => $errorId,
      'isAjax' => ($isAjax ? 'true' : 'false'),
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

    $vars = [
      'debugContext' => $debugContext,
      'exception' => $e,
    ];

    // Log error message EITHER to our own verbose log OR to civicrm's ConfigAndLog.
    $doOwnLogging = self::debugLog($vars, "Stepw details for Log Reference Number $errorId");
    if (!$doOwnLogging) {
      \Civi::log()->debug(E::LONG_NAME . ': ' . $logMessage, $vars);
    }

    // Store an additional informative message for display to the user.
    CRM_Stepw_State::singleton()->storePublicErrorMessage($publicMessage);

    if (Civi::settings()->get('debug_enabled')) {
      // If 'debug' is on, go ahead and show the original logged message to the user.
      CRM_Stepw_State::singleton()->storePublicErrorMessage("Debug message: " . $logMessage);
    }

    // If this is ajax, hide the original exception message and replace it with
    // whatever is in publicErrorMessages (this matches the error display of
    // non-ajax exceptions). Otherwise, redirect to invalid/, which will show
    // those publicErrorMessages.
    if ($isAjax) {
      $e->alterMessage(implode("\n\n", CRM_Stepw_State::singleton()->getPublicErrorMessages()));
    }
    else {
      $redirect = CRM_Utils_System::url('civicrm/stepwise/invalid', '', TRUE, NULL, FALSE);
      CRM_Utils_System::redirect($redirect);
    }
  }

  /**
   * Set a public error message, then thrown an exception.
   *
   * @param String $message
   * @throws CRM_Stepw_Exception
   */
  public static function throwExceptionWithPublicMessage($message) {
    CRM_Stepw_State::singleton()->storePublicErrorMessage($message);
    throw new CRM_Stepw_Exception($message);
  }

  public static function redirectToValidationError($errors) {
    // Store errors for display on the page.
    foreach ($errors as $error) {
      CRM_Stepw_State::singleton()->storePublicErrorMessage($error);
    }
    $redirect = CRM_Utils_System::url('civicrm/stepwise/requirements', '', TRUE, 'messages', FALSE);
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

  /**
   * Add civicrm page resources, as given.
   *
   * @param Array $resources Array of script and/or style resources, as defined,
   *   e.g. by CRM_Stepw_Utils_WpShortcode::getPageAssets().
   */
  public static function addCivicrmResources($resources) {
    foreach ($resources as $asset) {
      $src = ($asset['src'] ?? '');
      if (empty($src)) {
        // Some assets may be just handles for WP and include no src. Skip those.
        continue;
      }
      if ($asset['type'] == 'script') {
        CRM_Core_Resources::singleton()->addScriptUrl($src);
      }
      elseif ($asset['type'] == 'style') {
        CRM_Core_Resources::singleton()->addStyleUrl($src);
      }
    }
  }

}
