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
    // fixme: this method assumes that the step is not already open and that it doesn't
    // already have a public id. But what if it does? Does that cause problems?
    $publicId = CRM_Stepw_Utils_General::generatePublicId();
    $this->steps[$publicId] = [
      'status' => self::STEPW_WI_STEP_STATUS_OPEN,
      'stepId' => $stepId,
    ];
    // Fixme: We can't allow more than one open step at a time. If we open this step,
    // we must archive any steps that would come later.
    return $publicId;
  }
  
  public function closeStep($stepPublicId) {
    $this->steps[$stepPublicId]['status'] = self::STEPW_WI_STEP_STATUS_CLOSED;
    // fixme: as in ::open(), we should archive all subsequent steps in this workflowInstance
  }

  public function getVar($name) {
    return ($this->$name ?? NULL);
  }

  
  /**
   * Determine next un-completed step, per workflow config, in this workflow instance.
   * @return Array Step configuration.
   */
  public function getNextStep() {
    // Build a list of all closed steps in this instance.
    $closedWorfklowInstanceStepIds = [];
    foreach ($this->steps as $workflowInstanceStepPublicId => $workflowInstanceStep) {
      if ($workflowInstanceStep['status'] == CRM_Stepw_WorkflowInstance::STEPW_WI_STEP_STATUS_CLOSED) {
        $closedWorfklowInstanceStepIds[] = $workflowInstanceStep['stepId'];
      }
    }
    // Find the first configured step that isn't closed.
    $workflowConfig = CRM_Stepw_Utils_WorkflowData::getWorkflowConfigById($this->workflowId);
    foreach ($workflowConfig['steps'] as $workflowConfigStepId => $workflowConfigStep) {
      if (!in_array($workflowConfigStepId, $closedWorfklowInstanceStepIds)) {
        $nextStep = $workflowConfigStep;
        $nextStep['stepId'] = $workflowConfigStepId;
        break;
      }
    }
    
    return $nextStep;
  }

}
