<?php

/**
 * WorkflowInstanceStep class
 */
class CRM_Stepw_WorkflowInstanceStep {

  /**
   * The workflowInstance to which this step is attached.
   * @var CRM_Stepw_WorkflowInstance
   */
  private $workflowInstance;

  /**
   * Configuration from this step, per workflow config.
   * @var array
   */
  private $config;

  /**
   * Sequential, zero-based indicator of step order.
   * @var int
   */
  private $stepNumber;

  /**
   * Unique public identifier
   * @var string
   */
  private $publicId;

  /**
   * DB entity
   * @var array
   */
  private $entity;

  /**
   * Microtimestamp representing the moment this step was most recently completed.
   * (NULL if never submitted)
   * @var Float
   */
  private $mostRecentCompletedTimestamp;

  private $options = [];
  private $selectedOptionId = NULL;

  public function __construct(CRM_Stepw_WorkflowInstance $workflowInstance, Int $stepNumber, array $config) {
    $this->workflowInstance = $workflowInstance;
    $this->publicId = CRM_Stepw_Utils_General::generatePublicId();
    $this->stepNumber = $stepNumber;
    $this->config = $config;
    foreach ($config['options'] as $option) {
      $optionPublicId = CRM_Stepw_Utils_General::generatePublicId();
      // Define an array member to hold the mostRecentlyCompletedTimestamp for this option.
      $option['mostRecentlyCompletedTimestamp'] = '';
      // For this option, define an array to hold Afform submission ids (if any),
      // which will be populated when the step-option type is afform and the form
      // has been submitted.
      // Note: When a form step is submitted more than once in a workflowInstance (e.g.
      // by use of the back button), a new afformsubmission is created, with a new id,
      // and the most recent id is appended to this array.
      $option['afformSids'] = [];
      // Note the publicId inside of the option.
      $option['publicId'] = $optionPublicId;

      $this->options[$optionPublicId] = $option;
    }
    if (count($this->options) == 1) {
      $this->selectedOptionId = array_key_first($this->options);
    }
  }

  public function saveEntity($updateProperties = []) {
    if (empty($this->entity)) {
      $humanStepNumber = ($this->stepNumber + 1);
      $workflowinstanceId = $this->workflowInstance->getVar('entity')['id'];

      // create a workflowInstanceStep entity for this step.
      $selectedOption = $this->getSelectedOption();
      $stepwWorkflowInstanceStep = \Civi\Api4\StepwWorkflowInstanceStep::create()
        ->setCheckPermissions(FALSE)
        ->addValue('workflow_instance_id', $workflowinstanceId)
        ->addValue('step_number', ($humanStepNumber))
        ->addValue('url', $selectedOption['url'])
        ->addValue('public_id', $this->publicId)
        ->execute()
        ->first();
      $this->entity = $stepwWorkflowInstanceStep;
      $this->debugLogEvent(__FUNCTION__ . '.create');
    }
    else {
      $this->entity += $updateProperties;
      $result = civicrm_api4('StepwWorkflowInstanceStep', 'update', [
        'checkPermissions' => FALSE,
        'values' => $this->entity,
      ]);
      $this->debugLogEvent(__FUNCTION__ . '.update', $updateProperties);
    }
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
      throw new CRM_Stepw_Exception("Invalid variable name requested in " . __METHOD__, 'CRM_Stepw_WorkflowInstanceStep_getVar_invalid', ['requested var name' => $name]);
    }
    return ($this->$name ?? NULL);
  }

  /**
   * Get an array member, by the given name, for the selected option in this step.
   *
   * @param string $name
   */
  public function getSelectedOptionVar(string $name) {
    $option = $this->getSelectedOption();
    return ($option[$name] ?? NULL);
  }

  /**
   * Get the option which has been selected for this step.
   * @return Array A member of $this->options, per key in $this->selectedOptionId
   * @throws CRM_Stepw_Exception
   */
  private function getSelectedOption() {
    if (empty($this->selectedOptionId)) {
      throw new CRM_Stepw_Exception("No option has been selected for this step, " . __METHOD__, 'CRM_Stepw_WorkflowInstanceStep_getOption_no-selected-option-id');
    }
    return $this->options[$this->selectedOptionId];
  }

  /**
   * Build and return the URL for the selected option in this step, i.e., to a WP post or afform, with all
   * appropriate query params and afform params.
   *
   * @return String URL for this step with all appropriate params.
   */
  public function getBaseUrl() {
    $option = $this->getSelectedOption();
    $baseUrl = $option['url'];
    return $baseUrl;
  }

  /**
   * Build and return the URL for the selected option in this step, i.e., to a WP post or afform, with all
   * appropriate query params and afform params.
   *
   * @return String URL for this step with all appropriate params.
   */
  public function getUrl() {
    $baseUrl = $this->getBaseUrl();

    // Define stepw instance and step parameters to append to url.
    $params = [
      'i' => $this->workflowInstance->getPublicId(),
      's' => $this->publicId,
    ];

    $afformParams = [];
    $option = $this->getSelectedOption();
    if ($option['type'] == 'afform') {
      $afformParams = [];
      // If this option is afform, and if ->workflowInstance has a created contactId, append that in the
      // afform #? params.
      if ($individualCid = $this->workflowInstance->getCreatedIndividualCid()) {
        $afformParams['Individual1'] = $individualCid;
      }
      if ($sid = $this->getLastAfformSubmissionId()) {
        $afformParams['sid'] = $sid;
        $params['r'] = $sid;
      }
    }
    if (!empty($baseUrl)) {
      $ret = CRM_Stepw_Utils_Userparams::appendParamsToUrl($baseUrl, $params, $afformParams);
    }

    if (empty($ret)) {
      throw new CRM_Stepw_Exception("When calculating 'Step url', the result was empty, in " . __METHOD__, 'CRM_Stepw_WorkflowInstanceStep_getUrl_empty');
    }
    return $ret;
  }

  private function updateOption(array $option) {
    $optionPublicId = $option['publicId'];
    $this->options[$optionPublicId] = $option;
  }

  public function complete() {
    $timestamp = microtime(TRUE);

    $option = $this->getSelectedOption();
    $option['mostRecentlyCompletedTimestamp'] = $timestamp;
    $this->updateOption($option);

    // If called for in the step config, close the WorkflowInstance.
    if ($this->config['closeWorkflowInstanceOnComplete']) {
      $this->workflowInstance->close();
    }

    $this->mostRecentCompletedTimestamp = $timestamp;
    $this->saveEntity([
      'afform_submission_id' => $this->getLastAfformSubmissionId(),
      'completed' => CRM_Utils_Date::currentDBDate(),
      'activity_id' => $this->getCreatedActivityId(),
    ]);
    $this->debugLogEvent(__FUNCTION__);

  }

  public function setAfformSubmissionId($afformSubmissionId) {
    $option = $this->getSelectedOption();
    $option['afformSids'][] = $afformSubmissionId;
    $this->updateOption($option);
  }

  public function setCreatedActivityId($activityId) {
    $option = $this->getSelectedOption();
    $option['createdActivityId'] = $activityId;
    $this->updateOption($option);
  }

  public function setSelectedOptionId(string $optionPublicId) {
    if (!array_key_exists($optionPublicId, $this->options)) {
      throw new CRM_Stepw_Exception("Attempting to select invalid option '$optionPublicId', in " . __METHOD__);
    }
    $this->selectedOptionId = $optionPublicId;
  }

  public function getLastAfformSubmissionId() {
    $option = $this->getSelectedOption();
    $sids = ($option['afformSids'] ?? []);
    return ($sids[array_key_last($sids)] ?? NULL);
  }

  public function getCreatedActivityId() {
    $option = $this->getSelectedOption();
    $createdActivityId = ($option['createdActivityId'] ?? NULL);
    return $createdActivityId;
  }

  /**
   * Log an event message to our custom logger.
   *
   * @param string $eventName The name of the event
   * @param array|null $eventData Additional data to be logged, if any.
   */
  private function debugLogEvent($eventName, $eventData = NULL) {
    $messageData = [
      'message' => "event: $eventName, on " . __CLASS__,
      'workflow id' => $this->workflowInstance->getVar('workflowId'),
      'workflow_instance public_id' => $this->publicId,
      'step number' => $this->stepNumber,
      'step public_id' => $this->publicId,
    ];
    if (!is_null($eventData)) {
      $messageData['eventData'] = $eventData;
    }
    CRM_Stepw_Utils_General::debugLog($messageData);
  }

}
