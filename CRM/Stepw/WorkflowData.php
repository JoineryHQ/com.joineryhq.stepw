<?php

/**
 * Class to handle workflow configuration data.
 */
use CRM_Stepw_ExtensionUtil as E;

class CRM_Stepw_WorkflowData {

  static public $_singleton;

  /**
   * Array of all workflow data
   * @var array
   */
  private $data;

  /**
   * List of afformNames used in workflow data.
   * @var array
   */
  private $allAfformNames = [];
  private $workflowIdsWithBadConfig = [];

  private function __construct() {

    // fixme: somewhere (perhaps not here?) we need a data structure validator:
    // - make sure multi-option steps don't follow steps with any 'afform' options
    // - first and final steps must contain only 'page' options
    // - first step must contain exactly 1 option
    // - afforms named in 'afform' steps must exist
    // - afform configuration:
    //   - must include individual1
    // - wp page configuration:
    //   - must include [stepwise-button] shortcode
    //

    $rawData = CRM_Stepw_Fixme_LoadData::getSampleData();
    $data = [];
    foreach ($rawData as $workflowId => $workflow) {
      $publicId = $workflow['settings']['public_id'];
      $data[$publicId] = $workflow;
      $data[$publicId]['id'] = $workflowId;
      foreach (($workflow['steps'] ?? []) as $stepId => $step) {
        foreach ($step['options'] as $optionId => $option) {
          if (($option['type'] ?? '') == 'afform' && ($afformName = ($option['afformName'] ?? FALSE))) {
            // If this afform doesn't exist, throw an exception.
            if (self::afformExists($afformName)) {
              // Add afform url to config data for this afform step/option.
              $afform = \Civi\Api4\Afform::get()
                ->setCheckPermissions(FALSE)
                ->addWhere('name', '=', $afformName)
                ->setLimit(1)
                ->execute()
                ->first();
              $data[$publicId]['steps'][$stepId]['options'][$optionId]['url'] = CRM_Utils_System::url($afform['server_route']);

              // Add this afformName to allAfformNamtes, for future reference.
              $this->allAfformNames[] = $afformName;
            }
            else {
              // This function is sometimes called (e.g. when debug is on, or when otherwise
              // afform cache is being rebuilt) from execution paths that will
              // end with WSOD if we throw an exception. Therefore, just flag
              // this workflow config as invalid, log it, and we'll throw an exception
              // elsewhere -- see, e.g. this->getWorkflowConfigById().
              $errorContext = [
                'error_id' => $this->getsetWorkflowConfigErrorId($workflowId),
                'workflowId' => $workflowId,
                'stepId' => $stepId,
                'optionId' => $optionId,
                'afformName' => $afformName,
              ];
              \Civi::log()->error("Workflow step/option configured with non-existent afformName.", $errorContext);
            }
          }
        }
      }
      // Mark the previous-to-last step as "close workflow instance on complete", so
      // that any workflowInstance will be closed as soon as that step is completed.
      $data[$publicId]['steps'][($stepId - 1)]['closeWorkflowInstanceOnComplete'] = TRUE;
    }
    $this->data = $data;
  }

  /**
   * Singleton pattern.
   *
   * @see __construct()
   *
   * @return object This
   */
  public static function &singleton() {
    if (self::$_singleton === NULL) {
      self::$_singleton = new CRM_Stepw_WorkflowData();
    }
    return self::$_singleton;
  }

  public function getWorkflowConfigById(String $workflowId) {
    if (!empty($this->workflowIdsWithBadConfig[$workflowId])) {
      throw new CRM_Stepw_Exception('Workflow has bad configuration. For more info, see error log entries for error_id ' . $this->workflowIdsWithBadConfig[$workflowId], 'CRM_Stepw_WorkflowData_getWorkflowConfigById_bad-workflow-config');
    }
    return ($this->data[$workflowId] ?? NULL);
  }

  public function getAllAfformNames() {
    return $this->allAfformNames;
  }

  public static function getPseudoFinalStepConfig() {
    return [
      'options' => [
        [
          'type' => 'url',
          'url' => CRM_Utils_System::url('civicrm/stepwise/final', '', TRUE, NULL, FALSE, TRUE),
        ],
      ],
    ];
  }

  private static function afformExists(string $afformName) {
    $afformCount = \Civi\Api4\Afform::get()
      ->setCheckPermissions(FALSE)
      ->addWhere('name', '=', $afformName)
      ->setLimit(1)
      ->execute()
      ->count();
    return ($afformCount ? TRUE : FALSE);
  }

  private function getsetWorkflowConfigErrorId(string $workflowId) {
    if (empty($this->workflowIdsWithBadConfig[$workflowId])) {
      $this->workflowIdsWithBadConfig[$workflowId] = CRM_Stepw_Utils_General::generateLogId();
    }
    return $this->workflowIdsWithBadConfig[$workflowId];
  }

}
