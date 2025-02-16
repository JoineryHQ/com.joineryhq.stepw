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
  private $createdEntityIds = [];

  /* Workflow configuration for the workflow used by this WorkflowInstance.
   * @var Array
   */
  private $workflowConfig;

  /**
   * Map of stepNumbers by publicId
   * @var Array
   */
  private $stepNumbersByPublicId = [];
  
  /**
   * Array of active steps, keyed by stepNumber
   * @var Array
   */
  private $activeSteps = [];

  /**
   * Array of archived steps, keyed by publicId
   * @var Array
   */
  private $archivedSteps = [];
  
  /**
   * List of stepNumber values for all steps which have ever been closed.
   * @var Array
   */
  private $stepsEverClosed = [];
  
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
    $this->updateLastModified();
    $this->workflowId = $workflowId;
    $this->publicId = CRM_Stepw_Utils_General::generatePublicId();
    $workflowConfig = CRM_Stepw_Utils_WorkflowData::getWorkflowConfigById($workflowId);
    foreach ($workflowConfig['steps'] as $configStepNumber => &$configStep) {
      $configStep['stepNumber'] = $configStepNumber;
    }
    $this->workflowConfig = $workflowConfig;

    $state = CRM_Stepw_State::singleton();
    $state->storeWorkflowInstance($this);
  }
  
  private function updateLastModified() {
    $this->lastModified = time();
  }
  
  /**
   * Open an active step (and give it a publicId) in the workflow instance for
   * the given stepNumber,
   * 
   * @param int $stepNumber
   * @return string The public id for the created active step.
   */
  public function openStepNumber(int $stepNumber) {
    $this->updateLastModified();
    // fixme: this method assumes that the step is not already open and that it doesn't
    // already have a public id. But what if it does? Does that cause problems?
    $stepPublicId = CRM_Stepw_Utils_General::generatePublicId();
    $this->stepNumbersByPublicId[$stepPublicId] = $stepNumber;
    
    $this->activeSteps[$stepNumber] = [
      'stepNumber' => $stepNumber,
      'publicId' => $stepPublicId,
      'status' => self::STEPW_WI_STEP_STATUS_OPEN,
    ];
    // We can't allow more than one open step at a time. If we open this step,
    // we must archive any steps that would come later.
    $this->archiveStepsAfter($stepNumber);
    return $stepPublicId;
  }
  
  public function closeStepPublicId(string $stepPublicId) {
    $this->updateLastModified();
    $stepNumber = $this->getStepNumberByPublicId($stepPublicId);
    // Record this step is closed.
    $this->activeSteps[$stepNumber]['status'] = self::STEPW_WI_STEP_STATUS_CLOSED;
    // Record this step has ever been closed.
    $this->stepsEverClosed[] = $stepNumber;

    // We're restarting from this step, so
    // we must archive any steps that would come later.
    $this->archiveStepsAfter($stepNumber);

  }
  
  private function archiveStepsAfter(int $stepNumber) {
    $this->updateLastModified();
    // for any active steps with number > $stepNumber, move them to $archivedSteps[$publicId]
    // (steps in $this->activeSteps are, like steps in $this->workflowConfig['steps']
    // keyed sequentially starting from 0.)
    foreach ($this->activeSteps as $activeStepNumber => $activeStep) {
      if ($activeStepNumber > $stepNumber) {
        $activeStepPublicId = $activeStep['publicId'];
        $this->archivedSteps[$activeStepPublicId] = $activeStep;
        unset($this->activeSteps[$activeStepNumber]);
      }
    }
  }
  
  public function setStepSubmissionId(string $stepPublicId, int $afformSubmissionId) {
    $this->updateLastModified();
    $stepNumber = $this->getStepNumberByPublicId($stepPublicId);
    $this->activeSteps[$stepNumber]['afformSubmissionId'] = $afformSubmissionId;
  }

  public function getVar($name) {
    if (!property_exists($this, $name)) {
      throw new CRM_Extension_Exception("Invalid variable name requested in ". __METHOD__, 'CRM_Stepw_WorkflowInstance_getVar_invalid', ['requested var name' => $name]);
    }
    return ($this->$name ?? NULL);
  }
  
  public function setCreatedEntityId(string $entityName, int $entityId) {
    $this->createdEntityIds[$entityName] = $entityId;
    $this->updateLastModified();
  }
  
  public function getCreatedEntityId(string $entityName) {
    return ($this->createdEntityIds[$entityName] ?? NULL);
  }
  
  /**
   * Determine next un-completed step, per workflow config, in this workflow instance.
   * @return Array|Boolean Step configuration if any remain; otherwise FALSE.
   */
  public function getNextStep() {
    foreach ($this->workflowConfig['steps'] as $stepNumber => $stepConfig) {
      if (($this->activeSteps[$stepNumber]['status'] ?? '') != self::STEPW_WI_STEP_STATUS_CLOSED) {
        return $stepConfig;
      }
    }
    return FALSE;
  }
  
  public function getStepNumberByPublicId ($stepPublicId) {
    return ($this->stepNumbersByPublicId[$stepPublicId] ?? NULL);
  }
  
  /**
   * Validate whether a given step is valid, at a given status (open/closed) in this workflowInstance
   * @param String $stepPublicId A step publicId, presumably passed in from the user (_GET in WP, or REFERER in afform)
   * @param String $requireStatusName One of:
   *  open
   *  closed
   * 
   * @return bool True on valid; False otherwise.
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
    
    $stepNumber = $this->getStepNumberByPublicId($stepPublicId);
    $step = ($this->activeSteps[$stepNumber] ?? NULL);
    
    if (!empty($requireStatusValue)) {
      $isValid = (($step['status'] ?? NULL) === $requireStatusValue);
    }
    else {
      $isValid = !empty($step);
    }
    return $isValid;
  }

}
