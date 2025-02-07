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
  
  public static function getRefererQueryParams($name = '') {
    static $params = [];
    if (empty($params)) {
      if (!empty($_SERVER['HTTP_REFERER'])) {
        parse_str(parse_url($_SERVER['HTTP_REFERER'], PHP_URL_QUERY), $params);
        // Limit to valid params.
        $validParams = self::getValidParams();
        $params = array_intersect_key(array_flip($validParams), $params);
      }
    }
    if (!empty($name)) {
      return $params[$name];
    }
    else {
      return $params;
    }
  }
  
  public static function getUrlQueryParams($name = '') {
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
   * Append given query parameters to a given url.
   * 
   * @param array $params Query parameters to append. 
   *   Query parameter names are controlled by this class, and correspond to
   *   supported keys in the $params array:
   *     s: step id
   *     i: workflow instance public identifier
   * @param srtring $url The url to be modified
   * @return string
   */
  public static function appendParamsToUrl($params, $url) {
    $queryParams = [];
    $supportedParamKeys = [
      'i' => self::QP_WORKFLOW_INSTANCE_ID,
      's' => self::QP_STEP_ID,
    ];
    foreach ($supportedParamKeys as $shortKey => $actualKey) {
      if (!empty($params[$shortKey])) {
        $queryParams[$actualKey] = $params[$shortKey];
      }
    }
    
    $u = \Civi::url($url);
    $u->addQuery($queryParams);
    return (string)$u;
  }
  
}
