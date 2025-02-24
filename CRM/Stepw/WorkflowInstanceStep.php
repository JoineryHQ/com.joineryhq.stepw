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
    * Afform submission id (if any). Populated when the step is afform and the form
    * has been submitted. 
    * Note: When a form step is submitted more than once in a workflowInstance (e.g.
    * by use of the back button), a new afformsubmission is created, with a new id,
    * and the most recent id is written to this property.
    * @var Int
    */
   private $afformSids = [];
   
   /**
    * Microtimestamp representing the moment this step was most recently completed.
    * (NULL if never submitted)
    * @var Float
    */
   private $lastCompleted;

  
  public function __construct(CRM_Stepw_WorkflowInstance $workflow, Int $stepNumber, array $config) {
    $this->workflowInstance = $workflow;
    $this->publicId = CRM_Stepw_Utils_General::generatePublicId();
    $this->stepNumber = $stepNumber;
    $this->config = $config;
  }
  
  /**
   * Get a property of this object by name.
   */
  public function getVar($name) {
    if (!property_exists($this, $name)) {
      throw new CRM_Extension_Exception("Invalid variable name requested in ". __METHOD__, 'CRM_Stepw_WorkflowInstanceStep_getVar_invalid', ['requested var name' => $name]);
    }
    return ($this->$name ?? NULL);
  }
  
  /**
   * Build and return the URL for this step, i.e., to a WP post or afform, with all 
   * appropriate query params and afform params.
   * 
   * @return String URL for this step with all appropriate params.
   */  
  public function getUrl() {
    $baseUrl = $this->config['url'];

    // Define stepw instance and step parameters to append to url.
    $params = [
      'i' => $this->workflowInstance->getPublicId(),
      's' => $this->publicId,
    ];
    
    if($this->config['type'] == 'afform') {
        $afformParams = [];
      // If this step is afform, and if ->workflowInstance has a created contactId, append that in the
      // afform #? params.
      if ($individualCid = $this->workflowInstance->getCreatedIndividualCid()) {
        $afformParams['Individual1'] = $individualCid;
      }
      if ($sid = $this->getLastAfformSubmissionId()) {
        $afformParams['sid'] = $sid;
        $params['r'] = $sid;
      }
    }
    $ret = CRM_Stepw_Utils_Userparams::appendParamsToUrl($baseUrl, $params, $afformParams);
    return $ret;
  }

  public function complete() {
    $this->lastCompleted = microtime(TRUE);
  }

  public function setAfformSubmissionId($afformSubmissionId) {
    $this->afformSids[] = $afformSubmissionId;
  }

  public function getLastAfformSubmissionId() {
    return $this->afformSids[array_key_last($this->afformSids)];
  }
}
