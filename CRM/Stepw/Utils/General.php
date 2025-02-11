<?php

use CRM_Stepw_ExtensionUtil as E;

/**
 * General-purpose utilities for stepw extension.
 *
 */
class CRM_Stepw_Utils_General {
  public static function generatePublicId() {
    return bin2hex(random_bytes(18));
  }

  public static function redirectToInvalid(string $message = '') {
    if ($message) {
      \Civi::log()->critical(E::LONG_NAME .': '. $message, $_REQUEST);      
      CRM_Stepw_State::singleton()->storeInvalidMessage($message);
    }
    $redirect = CRM_Utils_System::url('civicrm/stepwise/invalid', '', FALSE, NULL, TRUE, TRUE);
    CRM_Utils_System::redirect($redirect);
  }
  
  public static function isStepwiseWorkflow($source) {
    $workflowInstancePublicId = CRM_Stepw_Utils_Userparams::getUserParams($source, CRM_Stepw_Utils_Userparams::QP_WORKFLOW_INSTANCE_ID);
    $ret = (!empty($workflowInstancePublicId));
    return $ret;
  }
  
  public static function alterAfformInvalid(phpQueryObject $doc) {
    // fixme: parse invalid.tpl and insert it here.
    $doc->find('*')->remove();
    $doc->append('<p>Invalid request.</p>');    
  }
}
