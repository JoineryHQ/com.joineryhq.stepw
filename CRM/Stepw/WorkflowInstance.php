<?php


/**
 * WorkflowInstance class
 */
class CRM_Stepw_WorkflowInstance {
  
  private $workflowId;
  private $publicId;
  private $lastModified;
  
  private $createdIndividualCid;

  /* Workflow configuration for the workflow used by this WorkflowInstance.
   * @var Array
   */
  private $workflowConfig;
  
  /**
   * Array of steps in this workflowInstance, keyed by stepNumber. Each step has
   * is a WorkflowInstanceStep object.
   * @var Array
   */
  private $steps = [];

  /**
   * A WorkflowInstanceStep object representing the fallback 'final' step url
   * provided by this extension.
   * @var WorkflowInstanceStep
   */
  private $pseudoFinalStep;
  
  /**
   * Map of step numbers per public id, for easier reference to steps.
   * @var Array
   */
  private $stepNumbersByPublicId = [];
  
  public function __construct(Int $workflowId) {
    $this->updateLastModified();
    $this->workflowId = $workflowId;
    $this->publicId = CRM_Stepw_Utils_General::generatePublicId();
    $workflowConfig = CRM_Stepw_Utils_WorkflowData::getWorkflowConfigById($workflowId);
    foreach ($workflowConfig['steps'] as $configStepNumber => &$configStep) {
      // Create a new Step object, store ->steps and map the ids in ->stepNumbersByPublicId
      $step = new CRM_Stepw_WorkflowInstanceStep($this, $configStepNumber, $configStep);
      $stepPublicId = $step->getVar('publicId');
      $this->steps[$configStepNumber] = $step;
      $this->stepNumbersByPublicId[$stepPublicId] = $configStepNumber;

      // Add 'stepNumber' to configStep for future reference.
      $configStep['stepNumber'] = $configStepNumber;
    }
    
    // Define the fallback 'final' step object.
    $this->pseudoFinalStep = new CRM_Stepw_WorkflowInstanceStep($this, -1, CRM_Stepw_Utils_WorkflowData::getPseudoFinalStepConfig());
    
    // Store the workflow config for easy reference.
    $this->workflowConfig = $workflowConfig;

    // Store this workflowInstance in state.
    $state = CRM_Stepw_State::singleton();
    $state->storeWorkflowInstance($this);
  }
  
  private function updateLastModified() {
    $this->lastModified = time();
  }

  /**
   * Determine most-recently completed step (if any), and return the subsequent
   * step to that one, if any.
   * @return CRM_Stepw_WorkflowInstanceStep
   */
  private function getNextStep() : CRM_Stepw_WorkflowInstanceStep {
    $stepsLastCompleted = [];
    $stepsToSort = [];
    foreach ($this->steps as $stepNumber => $step) {
      if ($lastCompleted = $step->getVar('lastCompleted')) {
        $stepsLastCompleted[] = $lastCompleted;
        $stepsToSort[] = $step;
      }
    }
    if(empty($stepsToSort)) {
      // If we're here, it means no steps have been completed, so nextStep 
      // is step 0.
      $nextStep = $this->steps[0];
    }
    else {
      array_multisort($stepsLastCompleted, $stepsToSort);
      $lastCompletedStep = array_pop($stepsToSort);
      $lastCompletedStepNumber = $lastCompletedStep->getVar('stepNumber');
      $nextStepNumber = ($lastCompletedStepNumber + 1);
      // Note that if $lastCompletedStep was really the last step in the workflow,
      // $nextStep will be NULL.
      $nextStep = ($this->steps[$nextStepNumber] ?? NULL);
    }    
    if (!$nextStep) {
      $nextStep = $this->pseudoFinalStep;
    }
    if (empty($nextStep) || !is_a($nextStep, 'CRM_Stepw_WorkflowInstanceStep')) {
      throw new CRM_Extension_Exception("When calculating 'Next Step', no valid step was found, in " . __METHOD__, 'CRM_Stepw_WorkflowInstanceStep_getNextStep_invalid');        
    }
    return $nextStep;
  }
    
  /**
   * Given an identifier, return the matching workflowInstancance step.
   * 
   * @param String $stepKey Either a stepNumber (which is an integer), or a publicId
   * @return CRM_Stepw_WorkflowInstanceStep|NULL The matching step (or null if no such step)
   */
  private function getStepByKey(String $stepKey) {
    if (is_numeric(($stepKey))) {
      $step = ($this->steps[$stepKey] ?? NULL);
    }
    else {
      $stepNumber = $this->stepNumbersByPublicId[$stepKey];
      $step = ($this->steps[$stepNumber] ?? NULL);
    }
    return $step;
  }
    
  public function getLastModified() {
    return $this->lastModified;
  }
  
  
  public function getPublicId() {
    return $this->publicId;
  }
  
  /**
   * Given a contactId, store it as the cid of the Individual created in this 
   * workflowInstance (we assume there will be only one).
   *
   * @param int $contactId
   */
  public function setCreatedIndividualCid(int $contactId) {
    if (
      !empty($this->createdIndividualCid)
      && ($this->createdIndividualCid != $contactId)
    ) {
      $exceptionExtra = [
        'given cid' => $contactId,
        'existing_cid' => $this->createdIndividualCid,
      ];
      throw new CRM_Extension_Exception("Invalid attempt to alter workflowInstance:createdIndividualCid". __METHOD__, 'CRM_Stepw_WorkflowInstance_setCreatedIndividualCid_invalid', $exceptionExtra);      
    }
    $this->createdIndividualCid = $contactId;
    $this->updateLastModified();
  }
  
  /**
   * Return the cid of the Individual created in this workflowInstance, if any
   * (we assume there will be only one).
   *
   * @return Int|NULL
   */
  public function getCreatedIndividualCid() {
    return $this->createdIndividualCid;
  }

  /**
   * Given an identifier, mark the matching workflowInstancance step as completed.
   * 
   * @param String $stepKey Either a stepNumber (which is an integer), or a publicId
   * @return void
   */  
  public function completeStep($stepKey) {
    $step = $this->getStepByKey($stepKey);
    $step->complete();
    $this->updateLastModified();    
  }
  
  public function getNextStepUrl() {
    $step = $this->getNextStep();
    $url = $step->getUrl();
    if (empty($url)) {
      throw new CRM_Extension_Exception("When calculating 'Next Step url', an empty value was found, in " . __METHOD__, 'CRM_Stepw_WorkflowInstanceStep_getNextStepUrl_empty');  
    }
    return $url;
  }
    
  public function getStepUrl($stepKey) {
    $step = $this->getStepByKey($stepKey);
    $url = $step->getUrl();
    return $url;
  }

  public function setStepAfformSubmissionId(int $afformSubmissionId, string $stepKey) {
    $step = $this->getStepByKey($stepKey);
    $step->setAfformSubmissionId($afformSubmissionId);
    $this->updateLastModified();
  }
  
  public function getProgress($stepKey) {
    $step = $this->getStepByKey($stepKey);
    $stepNumber = $step->getVar('stepNumber');
    $stepTotalCount = count($this->steps);
    
    $ret = [
      'stepOrdinal' => ($stepNumber + 1),
      'stepTotalCount' => $stepTotalCount,
    ];
    
    return $ret;
  }
  
  public function getStepLastAfformSubmissionId ($stepKey) {
    $step = $this->getStepByKey($stepKey);
    return $step->getLastAfformSubmissionId();
  }
  
  public function stepHasAfformSubmissionId ($stepKey, $submissionId) {
    $step = $this->getStepByKey($stepKey);
    $sids = $step->getVar('afformSids');
    $ret = in_array($submissionId, $sids);
    return $ret;
  }
  
  public function getStepAfformName ($stepKey) {
    $ret = NULL;
    $step = $this->getStepByKey($stepKey);
    $stepConfig = $step->getVar('config');
    if (($stepConfig['type'] ?? NULL) == 'afform') {
      $ret = ($stepConfig['afform_name'] ?? NULL);
    }
    return $ret;
  }
  
  public function getStepButtonLabel($stepKey) {
    $step = $this->getStepByKey($stepKey);
    $stepConfig = $step->getVar('config');
    return ($stepConfig['button_label'] ?? '');
  }
  
  public function getStepButtonDisabled($stepKey) {
    // fixme3: we're returning FALSE here because we don't yet have the video-enforcer
    //   javascript in place, which means our "real" logic for this method will
    //   always return TRUE. Once the video-enforcer is working, we should remove
    //   this comment and the hard-coded FALSE return.
    return false;

    // button is disabled by default; it is only enabled if:
    //   - step has ever been completed, OR
    //   - step is NOT a video page.
    //
    $disabled = TRUE;
    
    $step = $this->getStepByKey($stepKey);
    if ($step->getVar('lastCompleted')) {
      $disabled = FALSE;
    }
    else {
      $config = $step->getVar('config');
      $isVideoPage = ($config['is_video_page'] ?? FALSE);
      if (!$isVideoPage) {
        $disabled = FALSE;
      }
    }
    
    return $disabled;
  }
  
  /**
   * Determin if stepKey represents an existing step in this workflowInstance.
   * @param String $stepKey Either a stepNumber (which is an integer), or a publicId
   * @return Boolean True if step exists, otherwise false.
   */
  public function hasStep ($stepKey) {
    $step = $this->getStepByKey($stepKey);
    return (!empty($step));
  }  
}
