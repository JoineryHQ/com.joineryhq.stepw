<?php

/**
 * WorkflowInstance class
 */
class CRM_Stepw_WorkflowInstance {

  /**
   * StepwWorkflow id for this intance's workflow
   * @var int
   */
  private $workflowId;

  /**
   * DB entity
   * @var array
   */
  private $entity;

  private $publicId;
  private $modifiedTimestamp;
  private $isClosed = FALSE;

  private $createdIndividualCid;

  /**
   * Workflow configuration for the workflow used by this WorkflowInstance.
   * @var array
   */
  private $workflowConfig;

  /**
   * Array of steps in this workflowInstance, keyed by stepNumber. Each step has
   * is a WorkflowInstanceStep object.
   * @var array
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
   * @var array
   */
  private $stepNumbersByPublicId = [];

  public function __construct(String $publicWorkflowId) {
    $workflowConfig = CRM_Stepw_WorkflowData::singleton()->getWorkflowConfigById($publicWorkflowId);
    if (empty($workflowConfig)) {
      // If we're given an invaild workflowId, throw an exception.
      throw new CRM_Stepw_Exception("Given QP_START_WORKFLOW_ID ('$publicWorkflowId') does not match an available configured workflow, in " . __METHOD__, 'CRM_Stepw_WorkflowInstance_construct_invalid-workflow-id');
    }
    $this->updateModifiedTimestamp();
    $this->workflowId = $workflowConfig['id'];
    $this->publicId = CRM_Stepw_Utils_General::generatePublicId();
    $this->saveEntity();
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

  private function updateModifiedTimestamp() {
    $this->modifiedTimestamp = time();
  }

  /**
   * Return the most-recently completed step if any, else null.
   * @return CRM_Stepw_WorkflowInstanceStep|null
   */
  private function getLastCompletedStep() : ?CRM_Stepw_WorkflowInstanceStep {
    $stepsTimestamps = [];
    $stepsToSort = [];
    foreach ($this->steps as $stepNumber => $step) {
      if ($timestamp = $step->getVar('mostRecentCompletedTimestamp')) {
        $stepsTimestamps[] = $timestamp;
        $stepsToSort[] = $step;
      }
    }
    if (empty($stepsToSort)) {
      // If we're here, it means no steps have been completed, so return null;
      $lastCompletedStep = NULL;
    }
    else {
      array_multisort($stepsTimestamps, $stepsToSort);
      $lastCompletedStep = array_pop($stepsToSort);
    }
    return $lastCompletedStep;
  }

  /**
   * Determine most-recently completed step (if any), and return the subsequent
   * step to that one, if any, or else pseudoFinal step (as a fallback)
   * @return CRM_Stepw_WorkflowInstanceStep
   */
  private function getFirstUncompletedStep() : CRM_Stepw_WorkflowInstanceStep {
    if ($this->isClosed) {
      // Instance is closed, so the only step available is the last one
      // (which, per configuration standards, must be a CMS page (not afform).
      // This of course implies that the last step will never be completed,
      // but we're okay with that because the intention is that it's a "thank-you"
      // page of some sort.
      $firstUncompletedStep = $this->steps[array_key_last($this->steps)];
      return $firstUncompletedStep;
    }
    $lastCompletedStep = $this->getLastCompletedStep();
    if (is_null($lastCompletedStep)) {
      // If we're here, it means no steps have been completed, so first uncompleted
      // step is step 0.
      $firstUncompletedStep = $this->steps[0];
    }
    else {
      $lastCompletedStepNumber = $lastCompletedStep->getVar('stepNumber');
      $firstUncompletedStepNumber = ($lastCompletedStepNumber + 1);
      // Note that if $lastCompletedStep was really the last step in the workflow,
      // $firstUncompletedStep will be NULL.
      $firstUncompletedStep = ($this->steps[$firstUncompletedStepNumber] ?? NULL);
    }
    if (!$firstUncompletedStep) {
      // If we really could not find any ucompleted step, use the fall-back
      // pseudoFinal step.
      $firstUncompletedStep = $this->pseudoFinalStep;
    }
    if (empty($firstUncompletedStep) || !is_a($firstUncompletedStep, 'CRM_Stepw_WorkflowInstanceStep')) {
      throw new CRM_Stepw_Exception("When calculating 'First Uncompleted Step', no valid step was found, in " . __METHOD__, 'CRM_Stepw_WorkflowInstance_getFirstUncompletedStep_invalid');
    }
    return $firstUncompletedStep;
  }

  /**
   * Given an identifier, return the matching workflowInstancance step.
   *
   * @param String $stepKey Either a stepNumber (which is an integer), or a publicId
   * @param Boolean $abort (Default TRUE); if true, an invalid $stepkey will throw an exception.
   * @return CRM_Stepw_WorkflowInstanceStep|NULL The matching step (or null if no such step)
   */
  private function getStepByKey(String $stepKey, bool $abort = TRUE) {
    if (is_numeric(($stepKey))) {
      $step = ($this->steps[$stepKey] ?? NULL);
    }
    else {
      $stepNumber = $this->stepNumbersByPublicId[$stepKey];
      $step = ($this->steps[$stepNumber] ?? NULL);
    }
    if ($abort && !is_a($step, 'CRM_Stepw_WorkflowInstanceStep')) {
      throw new CRM_Stepw_Exception("Provided stepKey '$stepKey' does not match a step in this workflowInstance, in " . __METHOD__, 'CRM_Stepw_WorkflowInstance_getStepByKey-mismatch-stepkey');
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

  public function getModifiedTimestamp() {
    return $this->modifiedTimestamp;
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
      throw new CRM_Stepw_Exception("Invalid attempt to alter workflowInstance:createdIndividualCid" . __METHOD__, 'CRM_Stepw_WorkflowInstance_setCreatedIndividualCid_invalid', $exceptionExtra);
    }
    $this->createdIndividualCid = $contactId;
    $this->updateModifiedTimestamp();
    $this->saveEntity(['contact_id' => $this->createdIndividualCid]);
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
    $this->updateModifiedTimestamp();
  }

  public function createFirstUncompletedStepEntity() {
    $step = $this->getFirstUncompletedStep();
    $step->saveEntity();
  }

  public function getFirstUncompletedStepUrl() {
    $step = $this->getFirstUncompletedStep();
    $url = $step->getUrl();
    return $url;
  }

  public function getStepUrl($stepKey) {
    $step = $this->getStepByKey($stepKey);
    $url = $step->getUrl();
    return $url;
  }

  public function setStepAfformSubmissionId(string $stepKey, int $afformSubmissionId) {
    $step = $this->getStepByKey($stepKey);
    $step->setAfformSubmissionId($afformSubmissionId);
    $this->updateModifiedTimestamp();
  }

  public function setStepCreatedActivityId(string $stepKey, int $activityId) {
    $step = $this->getStepByKey($stepKey);
    $step->setCreatedActivityId($activityId);
    $this->updateModifiedTimestamp();
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
    $ret = (bool) $step->getSelectedOptionVar('requireOnpageEnforcer');
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

    if ($step->getSelectedOptionVar('mostRecentlyCompletedTimestamp')) {
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
   * Determine if stepKey represents an existing step in this workflowInstance.
   * @param String $stepKey Either a stepNumber (which is an integer), or a publicId
   * @return Boolean True if step exists, otherwise false.
   */
  public function hasStep ($stepKey) {
    $step = $this->getStepByKey($stepKey, FALSE);
    return (!empty($step));
  }

  private function saveEntity($updateProperties = []) {
    if (empty($this->entity)) {
      // No entity exists. Create a new one.
      $workflowInstance = \Civi\Api4\StepwWorkflowInstance::create()
        ->setCheckPermissions(FALSE)
        ->addValue('workflow_id', $this->workflowId)
        ->execute()
        ->first();
      $this->entity = (array) $workflowInstance;
    }
    else {
      $this->entity += $updateProperties;
      $result = civicrm_api4('StepwWorkflowInstance', 'update', [
        'checkPermissions' => FALSE,
        'values' => $this->entity,
      ]);
    }
  }

  public function close() {
    if ($this->isClosed) {
      // We're already closed. Nothing to do here.
      return;
    }
    // update db that this workflow instance is closed.
    $this->entity['closed'] = CRM_Utils_Date::currentDBDate();
    $this->saveEntity();

    $this->isClosed = TRUE;
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
      throw new CRM_Stepw_Exception("Invalid variable name requested in " . __METHOD__, 'CRM_Stepw_WorkflowInstance_getVar_invalid', ['requested var name' => $name]);
    }
    return ($this->$name ?? NULL);
  }

  public function validateLastCompletedStep(&$errors) : bool {
    $isError = FALSE;

    $lastCompletedStep = $this->getLastCompletedStep();

    if ($lastCompletedStep) {
      // FIXME: do this with Smart Groups, not where clauses (it's more flexible, and less development)
      $postSubmitValidation = $lastCompletedStep->getSelectedOptionVar('postSubmitValidation');

      if ($this->createdIndividualCid && ($individualWhere = $postSubmitValidation['where']['Individual1'])) {
        // FIXME: modify api4 call to specify smart group id.
        // LIKE SO: if ($this->createdIndividualCid && ($smartGroupId = $postSubmitValidation['smartGroupId'])) {
        $individualGet = \Civi\Api4\Individual::get()
          ->setCheckPermissions(FALSE)
          ->addWhere('id', '=', $this->createdIndividualCid);
        foreach ($individualWhere as $whereArgs) {
          list($fieldName, $op, $value, $isExpression) = $whereArgs;
          $individualGet = $individualGet->addWhere($fieldName, $op, $value, (bool) $isExpression);
        }
        $individualGet = $individualGet->execute()->count();
        if (!$individualGet) {
          $isError = TRUE;
        }
      }

      if (($activityId = $lastCompletedStep->getCreatedActivityId()) && ($activityWhere = $postSubmitValidation['where']['Activity1'])) {
        $activityGet = \Civi\Api4\Individual::get()
          ->setCheckPermissions(FALSE)
          ->addWhere('id', '=', $activityId);
        foreach ($activityWhere as $whereArgs) {
          list($fieldName, $op, $value, $isExpression) = $whereArgs;
          $activityGet = $activityGet->addWhere($fieldName, $op, $value, (bool) $isExpression);
        }
        $activityGet = $activityGet->execute()->count();
        if (!$activityGet) {
          $isError = TRUE;
        }
      }
    }

    if ($isError) {
      $errors[] = $postSubmitValidation['failureMessage'];
    }

    return ($isError == FALSE);
  }

}
