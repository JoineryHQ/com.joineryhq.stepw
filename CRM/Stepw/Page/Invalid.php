<?php
use CRM_Stepw_ExtensionUtil as E;

class CRM_Stepw_Page_Invalid extends CRM_Core_Page {

  public function run() {
    $messages = (CRM_Stepw_State::singleton()->getPublicErrorMessages() ?? []);
    $this->assign('messages', $messages);
    parent::run();
  }

}
