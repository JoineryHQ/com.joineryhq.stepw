<?php

/**
 * Description of Userparams
 *
 * @author as
 */


class CRM_Stepw_Utils_Userparams {
  
  /**
   * A workflow ID matching a confgiured workflow. Presence of this parameter
   * implies we're biginning a new worfkflowInstance.
   */
  const QP_START_WORKFLOW_ID = 'stepw_wid';
  
  /**
   * PublicId for an existing workflowInstance.
   */
  const QP_WORKFLOW_INSTANCE_ID = 'stepw_wiid';
  
  /**
   * PublicId for a given step within the current workflowInstance; indicates the
   * step currently being loaded/viewed.
   */
  const QP_STEP_ID = 'stepw_sid';
  
  /**
   * PublicId for a given step within the current workflowInstance; indicates that
   * this step has been completed. (Designed to support completion of wp-page steps
   * by inclusion of this parameter in the href of the button link created by the
   * [stepwise-button] shortcode.)
   */
  const QP_DONE_STEP_ID = 'stepw_dsid';
  
  /**
   * PublicId-type string, intended as an indicator that an afform step was
   * loaded/viewed with an afform submission 'sid' (Designed to allow our APIWrappers
   * and other event listeners, called during the prefill of such forms and during 
   * the processing of such form submissions, to recognize that this is a valid
   * re-load/re-submission, and therefore to alter api parameters or permissions
   * in order to allow the prefill/re-submission.)
   */
  const QP_STEP_RELOAD_PUBLIC_ID = 'stepw_rid';
  
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

  
}
