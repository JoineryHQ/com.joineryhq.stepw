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
  
  private $workflowId;
  private $publicId;
  private $lastModified;
  private $steps = [];
  private $createdEntityIds = [];
  
  // fixme: should each workflow instance store its own original config, as it was at the
  // time of instance creation? if not, any configuration changes could have very
  // surprising consequences for workflowInstances in-progress at that time.
  // 
  // fixme: probably good to track certain properties per configStep (not publicStepId,
  // which may change in the back-button flow direction), such as:
  // - submission id
  // - has ever been closed (this could be redundant to submisssion_id for afforms, 
  //   but not for wp pages; for wp pages, this could also apply to video-page steps,
  //   so that we'll know "video was fully watched" even on back-button flow)
  // 
  //

  const STEPW_WI_STEP_STATUS_OPEN = 0;
  const STEPW_WI_STEP_STATUS_CLOSED = 1;
  
  public function __construct(Int $workflowId) {
    $this->workflowId = $workflowId;
    $this->initialize();
  }
  
  private function updateLastModified() {
    $this->lastModified = time();
  }

  private function initialize() {
    $this->publicId = CRM_Stepw_Utils_General::generatePublicId();
    $this->updateLastModified();

    $state = CRM_Stepw_State::singleton();
    $state->storeWorkflowInstance($this);
  }
  
  public function openStep($stepId) {
    $this->updateLastModified();
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
    $this->updateLastModified();
    $this->steps[$stepPublicId]['status'] = self::STEPW_WI_STEP_STATUS_CLOSED;
    // fixme: as in ::open(), we should archive all subsequent steps in this workflowInstance

    // fixme: create a top-level array property in this class to record a list 
    // of steps (per config Id at least, and perhaps public id if that seems useful)
    // which have ever been closed. this will help with back-button detection.
    //
  }
  
  public function setStepSubmissionId(string $stepPublicId, int $afformSubmissionId) {
    $this->updateLastModified();
    $this->steps[$stepPublicId]['afform_submission_id'] = $afformSubmissionId;
  }

  public function getVar($name) {
    return ($this->$name ?? NULL);
  }
  
  public function setCreatedEntityId(string $entityName, int $entityId) {
    $this->createdEntityIds[$entityName] = $entityId;
    $this->updateLastModified();
  }
  
  public function getCreatedEntityId(string $entityName) {
    return ($this->createdEntityIds[$entityName] ?? NULL);
  }

  public function getStepByPublicId($publicId) {
    return ($this->steps[$publicId] ?? NULL);
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
  
  /**
   * Validate whether a given step is valid, at a given status (open/closed) in this workflowInstance
   * @param String $stepPublicId A step publicId, presumably passed in from the user (_GET in WP, or REFERER in afform)
   * @param String $requireStatusName One of:
   *  open
   *  closed
   * 
   * @return bool True on valid; false otherwise.
   */
  public function validateStep(string $stepPublicId, string $requireStatusName = NULL) {
    switch ($requireStatusName) {
      case 'open':
        $requireStatusValue = CRM_Stepw_WorkflowInstance::STEPW_WI_STEP_STATUS_OPEN;
        break;
      case 'closed':
        $requireStatusValue = CRM_Stepw_WorkflowInstance::STEPW_WI_STEP_STATUS_CLOSED;
        break;
      default:
        $requireStatusValue = NULL;
        break;
    }

    $isValid = FALSE;
    
    $step = ($this->steps[$stepPublicId] ?? NULL);
    
    if (!empty($requireStatusValue)) {
      $isValid = (($step['status'] ?? NULL) === $requireStatusValue);
    }
    else {
      $isValid = !empty($step);
    }
    return $isValid;
  }

}
