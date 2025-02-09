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
  private $workflowId;
  private $publicId;
  private $lastModified;
  private $steps = [];

  const STEPW_WI_STEP_STATUS_OPEN = 0;
  const STEPW_WI_STEP_STATUS_CLOSED = 1;
  
  private function __construct(Int $workflowId) {
    $this->workflowId = $workflowId;
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
  
  public function openStep($stepId) {
    $publicId = CRM_Stepw_Utils_General::generatePublicId();
    $this->steps[$publicId] = [
      'status' => self::STEPW_WI_STEP_STATUS_OPEN,
    ];
    // Fixme: We can't allow more than one open step at a time. If we open this step,
    // we must (obliterate? close?) any steps that would come later.
    //  - obliterate: but what about long-term record that some afform submission was tied to a certain step/workflowInstance?
    //  - close: seems odd to leave them lying about in a "closed" state. If the user starts step 2 anew, he surely must
    //    be required to also complete steps 2,3,4... anew, right?
    //
    return $publicId;
  }
  
  public function closeStep($stepPublicId) {
    $this->steps[$stepPublicId] = [
      'status' => self::STEPW_WI_STEP_STATUS_CLOSED,
    ];
    // fixme: as in ::open(), do we need to obliterate future steps at this point?
  }

  public function getVar($name) {
    return ($this->$name ?? NULL);
  }

}
