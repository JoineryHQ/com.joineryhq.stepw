<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

/**
 * Description of WorkflowInstance
 *
 * @author as
 */
class CRM_Stepw_WorkflowInstance {
  
  private static $_singleton = NULL;
  private $workflowid;
  private $publicId;
  
  private function __construct(Int $workflowId) {
    $this->workflowid = $workflowId;
  }

  /**
   * Singleton pattern.
   *
   * @see __construct()
   *
   * @param Int $workflowId
   * @return object This
   */
  public static function &singleton(Int $workflowId) {
    if (self::$_singleton === NULL) {
      self::$_singleton = new CRM_Stepw_WorkflowInstance($workflowId);
    }
    return self::$_singleton;
  }
  
  public function initialize() {
    $this->publicId = CRM_Stepw_Utils_General::generatePublicId();
    $state = CRM_Stepw_State::singleton();
    $state->createWorkflowInstance($this);
  }
  
  public function getPublicId() {
    return $this->publicId;
  }

}
