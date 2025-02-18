<?php
use CRM_Stepw_ExtensionUtil as E;

class CRM_Stepw_Page_Step extends CRM_Core_Page {

  public function run() {
  // fixme3 note: here we will:
  // - If we're given 'start_workflow_id', 
  //   - initialize a workflow instance and used this $wi
  // - else
  //   - get workflowintance $wi based on given 'stepw_wiid'
  // - if we're given step_done_step_id:
  //   - timestamp step completed in $wi
  // - get $wi next step url, and redirect thence.
  // 
  // fixme3val: validate page:step if done_step_id
  //  - given WI exists in state
  //  - given done_step_id exists in WI
  //  -- VALIDATION FAILURE: redirect to invalid
  //  
  // fixme3val: validate page:step before redirect
  //  - calculated next_url is not empty.
  //  -- VALIDATION FAILURE: redirect to invalid.
  //
    
    parent::run();
  }

}
