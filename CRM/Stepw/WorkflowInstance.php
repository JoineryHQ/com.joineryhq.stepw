<?php


/**
 * WorkflowInstance class
 */
class CRM_Stepw_WorkflowInstance {
  
  // fixme: we need to support post-submit validation handling (e.g. demographics) for each afform step/option.
  //
  
  private $workflowId;
  private $publicId;
  private $lastModified;
  private $isClosed = FALSE;
  
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
  
  public function __construct(String $workflowId) {
    $workflowConfig = CRM_Stepw_WorkflowData::singleton()->getWorkflowConfigById($workflowId);
    if (empty($workflowConfig)) {
      // If we're given an invaild workflowId, throw an exception.
      throw new  CRM_Stepw_Exception("Given QP_START_WORKFLOW_ID ('$workflowId') does not match an available configured workflow, in " . __METHOD__, 'CRM_Stepw_WorkflowInstance_construct_invalid-workflow-id');
    }
    $this->updateLastModified();
    $this->workflowId = $workflowId;
    $this->publicId = CRM_Stepw_Utils_General::generatePublicId();
    foreach ($workflowConfig['steps'] as $configStepNumber => &$configStep) {
      // Create a new Step object, store ->steps and map the ids in ->stepNumbersByPublicId
      $step = new CRM_Stepw_WorkflowInstanceStep($this, $configStepNumber, $configStep);
      $stepPublicId = $step->getVar('publicId');
      $this->steps[$configStepNumber] = $step;
      $this->stepNumbersByPublicId[$stepPublicId] = $configStepNumber;
    }
    
    // Define the fallback 'final' step object.
    $this->pseudoFinalStep = new CRM_Stepw_WorkflowInstanceStep($this, -1, CRM_Stepw_WorkflowData::getPseudoFinalStepConfig());
    
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
    if ($this->isClosed) {
      // Instance is closed, so the only step available is the last one
      // (which, per configuration standards, must be a CMS page (not afform).
      // This of course implies that the last step will never be completed,
      // but we're okay with that because the intention is that it's a "thank-you"
      // page of some sort.
      $nextStep = $this->steps[array_key_last($this->steps)];
      return $nextStep;
    }
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
      throw new  CRM_Stepw_Exception("When calculating 'Next Step', no valid step was found, in " . __METHOD__, 'CRM_Stepw_WorkflowInstance_getNextStep_invalid');        
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
    if (!is_a($step, 'CRM_Stepw_WorkflowInstanceStep')) {
      throw new CRM_Stepw_Exception("Provided stepKey '$stepKey' does not match a step in this workflowInstnce, in " . __METHOD__, 'CRM_Stepw_WorkflowInstance_getStepByKey-mismatch-stepkey');      
    }
    return $step;
  }
  
  public function getSubsequentStepOptionButtonProperties($stepPublicId) {
    $stepNumber = $this->stepNumbersByPublicId[$stepPublicId];
    $subsequentStepNumber = $stepNumber + 1;
    $subsequentStep = ($this->steps[$subsequentStepNumber] ?? NULL);
    if (!$subsequentStep) {
      // If there is no subsequent step (this is the final configured step),
      // return an empty array (i.e., there are no subsequent step button properties).
      return [];
    }
    $subsequentOptions = $subsequentStep->getVar('options');
    
    $buttonsDisabled = $this->getStepButtonsDisabled($stepPublicId);
    $currentStepOptionLabels = $this->getStepButtonLabels($stepPublicId);
    $i = 0;
    foreach ($subsequentOptions as &$subsequentOption) {
      $subsequentOption['buttonDisabled'] = $buttonsDisabled;
      $subsequentOption['buttonLabel'] = ($currentStepOptionLabels[$i++] ?? self::getDefaultOptionButtonLabel());
    }
    return $subsequentOptions;
  }
  
  public function setSubsequentStepOptionId($stepPublicId, $optionPublicId) {
    $stepNumber = $this->stepNumbersByPublicId[$stepPublicId];
    $subsequentStepNumber = $stepNumber + 1;
    $subsequentStep = $this->steps[$subsequentStepNumber];
    $subsequentStep->setSelectedOptionId($optionPublicId);
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
      throw new  CRM_Stepw_Exception("Invalid attempt to alter workflowInstance:createdIndividualCid". __METHOD__, 'CRM_Stepw_WorkflowInstance_setCreatedIndividualCid_invalid', $exceptionExtra);      
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
    
    if ($this->workflowConfig['settings']['progressOmitFirstStep']) {
      if ($stepNumber == 0) {
        $ret['omitProgressbar'] = TRUE;
      }
      $ret['stepOrdinal']--;
      $ret['stepTotalCount']--;
    }
    
    return $ret;
  }
  
  public function getStepLastAfformSubmissionId ($stepKey) {
    $step = $this->getStepByKey($stepKey);
    return $step->getLastAfformSubmissionId();
  }
  
  public function stepHasAfformSubmissionId ($stepKey, $submissionId) {
    $step = $this->getStepByKey($stepKey);
    $sids = $step->getSelectedOptionVar('afformSids');
    $ret = in_array($submissionId, $sids);
    return $ret;
  }
  
  public function getStepAfformName ($stepKey) {
    $ret = NULL;
    $step = $this->getStepByKey($stepKey);
    $optionType = $step->getSelectedOptionVar('type');
    if ($optionType == 'afform') {
      $ret = $step->getSelectedOptionVar('afformName');
    }
    return $ret;
  }
  
  public function stepRequiresOnpageEnforcer ($stepKey) {
    $step = $this->getStepByKey($stepKey);
    $ret = (bool)$step->getSelectedOptionVar('requireOnpageEnforcer');
    return $ret;
  }
  
  public function getStepButtonLabels($stepKey) {
    $step = $this->getStepByKey($stepKey);
    $optionLabels = $step->getSelectedOptionVar('optionLabels');
    return ($optionLabels);
  }
  
  private static function getDefaultOptionButtonLabel() {
    return 'Continue';
  }
  
  private function getStepButtonsDisabled($stepPublicId) {
    // This gets the disabled status for the CURRENTLY VIEWED step/option,
    // (i.e. not for the subsequent step/option).
    
    $step = $this->getStepByKey($stepPublicId);

    // button is disabled by default; it is only enabled if:
    //   - option has been completed (ever, in this workflowInstance), OR
    //   - option does NOT require onpage enforcement.
    $disabled = TRUE;
    
    if ($step->getSelectedOptionVar('lastCompleted')) {
      // Option has been completed.
      $disabled = FALSE;
    }
    else {
      
      $requireOnpageEnforcer = ($this->stepRequiresOnpageEnforcer($stepPublicId) ?? FALSE);
      if (!$requireOnpageEnforcer) {
        // option does NOT require onpage enforcement.
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

  public function close() {
    $this->isClosed = TRUE;
    // fixme: need to record everything correctly in civicrm, upon closure of this instance
    //  (i.e., when the user has completed the workflow).
  }

  /**
   *
   * Get a property of this object by name.
   *
   * @param string $name
   * @throws CRM_Stepw_Exception
   */
  public function getVar(string $name) {
    if (!property_exists($this, $name)) {
      throw new  CRM_Stepw_Exception("Invalid variable name requested in ". __METHOD__, 'CRM_Stepw_WorkflowInstance_getVar_invalid', ['requested var name' => $name]);
    }
    return ($this->$name ?? NULL);
  }
}
