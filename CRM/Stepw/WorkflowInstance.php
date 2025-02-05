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
  private $lastModified;
  
  private function __construct(Int $workflowId) {
    $this->workflowid = $workflowId;
    $this->updateLastModified();
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
  
  private function updateLastModified() {
    $this->lastModified = time();
  }

  public function initialize() {
    $this->publicId = CRM_Stepw_Utils_General::generatePublicId();
    $this->updateLastModified();

    $state = CRM_Stepw_State::singleton();
    $state->storeWorkflowInstance($this);
  }
  
  public function getVar($name) {
    return ($this->$name ?? NULL);
  }

}
