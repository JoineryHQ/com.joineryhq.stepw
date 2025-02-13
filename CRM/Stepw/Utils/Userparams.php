<?php

/**
 * Description of Userparams
 *
 * @author as
 */


class CRM_Stepw_Utils_Userparams {
  

  const QP_START_WORKFLOW_ID = 'stepw_wid';
  const QP_WORKFLOW_INSTANCE_ID = 'stepw_wiid';
  const QP_STEP_ID = 'stepw_sid';
  const QP_DONE_STEP_ID = 'stepw_dsid';

  static function getStartWorkflowid() {
    return CRM_Utils_Request::retrieve(self::QP_START_WORKFLOW_ID, 'Int', NULL, FALSE, NULL, 'GET');
  }
  
  private static function getValidParams() {
    $validParams = [];
    $relectionClass = new ReflectionClass(__CLASS__);
    $classConstants = $relectionClass->getConstants();
    foreach ($classConstants as $constantName => $constantValue) {
      if (substr($constantName, 0, 3) == 'QP_') {
        $validParams[] = $constantValue;
      }
    }
    return $validParams;
  }
  
  private static function getRefererQueryParams($name = '') {
    static $params = [];
    if (empty($params)) {
      if (!empty($_SERVER['HTTP_REFERER'])) {
        parse_str(parse_url($_SERVER['HTTP_REFERER'], PHP_URL_QUERY), $params);
        // Limit to valid params.
        $validParams = self::getValidParams();
        $params = array_intersect_key($params, array_flip($validParams));
      }
    }
    if (!empty($name)) {
      return $params[$name];
    }
    else {
      return $params;
    }
  }
  
  private static function getUrlQueryParams($name = '') {
    static $params = [];
    if (empty($params)) {
      $validParams = self::getValidParams();
      foreach ($validParams as $validParam) {
        $params[$validParam] = CRM_Utils_Request::retrieveValue($validParam, 'String');
      }
    }
    if (!empty($name)) {
      return $params[$name];
    }
    else {
      return $params;
    }
  }
  
  /**
   * Get one or all parameters supplied by user in the given source, limited only
   * to parameters named in QP_* constants of this class.
   *
   * @param string $source One of: referer, request
   * @param string $name If given, only return this named parameter; otherwise
   *   return an array of all parameters.
   * @return array|string see $name param.
   */
  public static function getUserParams(string $source, string $name = '') {
    if ($source == 'referer') {
      return self::getRefererQueryParams($name);
    }
    elseif ($source == 'request') {
      return self::getUrlQueryParams($name);
    }
    else {
      \Civi::log()->warning(__METHOD__ . ': Unsupported source', ['source' => $source]);
      return NULL;
    }
  }
  
  /**
   * Append given query parameters to a given url.
   * 
   * @param srtring $url The url to be modified
   * @param array $params Query parameters to append. 
   *   Query parameter names are controlled by this class, and correspond to
   *   supported keys in the $params array:
   *     s: step id
   *     i: workflow instance public identifier
   * @param array $fragmentQuery Query parameters to append in url fragment, e.g. for afform #?...
   * @return string
   */
  public static function appendParamsToUrl($url, $params = [], $fragmentQuery = []) {
    $queryParams = [];
    $supportedParamKeys = [
      'i' => self::QP_WORKFLOW_INSTANCE_ID,
      's' => self::QP_STEP_ID,
      'rr' => 'rr',
    ];
    foreach ($params as $paramKey => $paramValue) {
      if (!empty($supportedParamKeys[$paramKey])) {
        $queryParams[$supportedParamKeys[$paramKey]] = $paramValue;
      }
      else {
        \Civi::log()->warning(__METHOD__ . ': Unsupported parameter found', [$paramKey => $paramValue]);
      }
    }
    
    $u = \Civi::url($url);
    $u->addQuery($queryParams);
    $u->addFragmentQuery($fragmentQuery);
    return (string)$u;
  }

  public static function isStepwiseWorkflow($source = 'any') {
    static $cache;
    
    if (empty($cache[$source])) {
      if ($source == 'any') {
        $cache[$source] = (self::isStepwiseWorkflow('referer') || self::isStepwiseWorkflow('request'));
      }
      else {
        $workflowInstancePublicId = CRM_Stepw_Utils_Userparams::getUserParams($source, CRM_Stepw_Utils_Userparams::QP_WORKFLOW_INSTANCE_ID);
        $cache[$source] = (!empty($workflowInstancePublicId));
      }
    }
    
    return $cache[$source];
  }
  
  public static function validateWorkflowInstanceStep($source, $isFatal) {
    
    /////////////////////////////////// fixme: stub.
    return true;
    ///////////////////////////////////

    if (!self::isStepwiseWorkflow($source)) {
      // This is not a stepwise workflow, so it can't be invalid.
      return TRUE;
    }
    
    static $cache;
    
    $workflowinstancePublicId = self::getUserParams($source, self::QP_WORKFLOW_INSTANCE_ID);
    $stepPublicId = self::getUserParams($source, self::QP_STEP_ID);

    $cacheKey = "{$workflowinstancePublicId}|{$stepPublicId}";
    if (empty($cache[$cacheKey])) {
      $isValid = FALSE;
      
      // fixme: check if workflow and step exist in state.
//            // workflow instance exists? 
//        !empty($workflowInstance)
//        // step exists?
//        && $workflowInstance->validateStep($stepPublicId)

    }
    
    if (!$cache[$cacheKey] && $isFatal) {
      CRM_Stepw_Utils_General::redirectToInvalid();
    }
    return $cache[$cacheKey];
  }
  
  public static function currentWorkflowStepIsForAfform($source, $afformName) {

    $ret = FALSE;

    // If we're not in a stepwise workflow, there's no matching form, so ensure we're in a workflow.
    if (self::isStepwiseWorkflow($source)) {
      $stepPublicId = CRM_Stepw_Utils_Userparams::getUserParams($source, CRM_Stepw_Utils_Userparams::QP_STEP_ID);
      $workflowInstancePublicId = CRM_Stepw_Utils_Userparams::getUserParams($source, CRM_Stepw_Utils_Userparams::QP_WORKFLOW_INSTANCE_ID);
      $workflowInstance = CRM_Stepw_State::singleton()->getWorkflowInstance($workflowInstancePublicId);
      
      // step is for this afform?
      $workflowConfigStep = CRM_Stepw_Utils_WorkflowData::getCurrentWorkflowConfigStep($source);
      if (($workflowConfigStep['afform_name'] ?? NULL) == $afformName) {
        // fixme: must also validate :step_state_key (if given) is valid?
        $ret = TRUE;
      }
    }
    return $ret;
    
  }
}
