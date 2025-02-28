<?php

/**
 * WorkflowInstanceStep class
 */
class CRM_Stepw_WorkflowInstanceStep {
  
  /**
   * The workflowInstance to which this step is attached.
   * @var Object CRM_Stepw_WorkflowInstance
   */
  private $workflowInstance;
  
  /**
   * Configuration from this step, per workflow config.
   * @var Array
   */
  private $config;
  
  /**
   * Sequential, zero-based indicator of step order.
   * @var Int
   */
  private $stepNumber;
  
  /**
   * Unique public identifier
   * @var String
   */
   private $publicId;
   
   /**
    * Microtimestamp representing the moment this step was most recently completed.
    * (NULL if never submitted)
    * @var Float
    */
   private $lastCompleted;

   private $options = [];
   private $selectedOptionId = NULL;
  
  public function __construct(CRM_Stepw_WorkflowInstance $workflow, Int $stepNumber, array $config) {
    $this->workflowInstance = $workflow;
    $this->publicId = CRM_Stepw_Utils_General::generatePublicId();
    $this->stepNumber = $stepNumber;
    $this->config = $config;
    foreach ($config['options'] as $option) {
      $optionPublicId = CRM_Stepw_Utils_General::generatePublicId();
      // Define an array member to hold the lastCompleted timestamp for this option.
      $option['lastCompleted'] = '';
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
  
  /**
   * 
   * Get a property of this object by name.
   *
   * @param string $name
   * @throws CRM_Stepw_Exception
   */
  public function getVar(string $name) {
    if (!property_exists($this, $name)) {
      throw new  CRM_Stepw_Exception("Invalid variable name requested in ". __METHOD__, 'CRM_Stepw_WorkflowInstanceStep_getVar_invalid', ['requested var name' => $name]);
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
      throw new  CRM_Stepw_Exception("No option has been selected for this step, ". __METHOD__, 'CRM_Stepw_WorkflowInstanceStep_getOption_no-selected-option-id');      
    }
    return $this->options[$this->selectedOptionId];
  }
  
  /**
   * Build and return the URL for the selected option in this step, i.e., to a WP post or afform, with all 
   * appropriate query params and afform params.
   * 
   * @return String URL for this step with all appropriate params.
   */  
  public function getUrl() {
    $option = $this->getSelectedOption();
    
    $baseUrl = $option['url'];
    
    // Define stepw instance and step parameters to append to url.
    $params = [
      'i' => $this->workflowInstance->getPublicId(),
      's' => $this->publicId,
    ];
    $afformParams = [];
    
    if($option['type'] == 'afform') {
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
      throw new  CRM_Stepw_Exception("When calculating 'Step url', the result was empty, in " . __METHOD__, 'CRM_Stepw_WorkflowInstanceStep_getUrl_empty');        
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
    $option['lastCompleted'] = $timestamp;
    $this->updateOption($option);
    
    $this->lastCompleted = $timestamp;
  }

  public function setAfformSubmissionId($afformSubmissionId) {
    $option = $this->getSelectedOption();
    $option['afformSids'][] = $afformSubmissionId;
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
    $sids = $option['afformSids'];
    return $sids[array_key_last($sids)];
  }
}
