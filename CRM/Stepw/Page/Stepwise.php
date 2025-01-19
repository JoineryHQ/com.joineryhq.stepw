<?php
use CRM_Stepw_ExtensionUtil as E;

class CRM_Stepw_Page_Stepwise extends CRM_Core_Page {

  public function run() {

    $data = CRM_Stepw_Utils::getWorkflowConfig();
    $workflowId = CRM_Utils_Request::retrieve('sw', 'Int');
    $stepId = CRM_Utils_Request::retrieve('ss', 'Int');
    // fixme: if no stepid, we should start at step 1, but also need a mechanism to ensure previous steps have been completed -- i.e., no bookmarking 'step 2' and starting there.
    if (empty($stepId)) {
      $stepId = 1;
    }
      
    $workflow = $data[$workflowId] ?? NULL;
    $step = $workflow[$stepId] ?? NULL;
    if (empty($workflow)) {
      die('fixme: workflow not found; should return 404');
    }
    if (empty($step)) {
      die('fixme: step not found in this workflow; should return 404');
    }
    
    $redirect = $step['url'] . "&sw={$workflowId}&ss={$stepId}";
    CRM_Utils_System::redirect($redirect);
    
    
    
    
//    CRM_Utils_System::redirect();
//    parent::run();
  }

}
