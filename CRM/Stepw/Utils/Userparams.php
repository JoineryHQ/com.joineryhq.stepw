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

  static function getStartWorkflowid() {
    return CRM_Utils_Request::retrieve(self::QP_START_WORKFLOW_ID, 'Int', NULL, FALSE, NULL, 'GET');
  }
  
  public static function getRefererQueryParams($name = '') {
    static $ret = [];
    if (empty($ret)) {
      parse_str(parse_url($_SERVER['HTTP_REFERER'], PHP_URL_QUERY), $ret);
    }
    if (!empty($name)) {
      return $ret[$name];
    }
    else {
      return $ret;
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
