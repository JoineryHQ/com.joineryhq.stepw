<?php

/**
 * Description of CRM_Stepw_APIWrapper
 *
 * @author as
 */
class CRM_Stepw_Utils {

  public static function getWorkflowConfig() {
    return CRM_Stepw_Fixme_Data::getSampleData();
  }

  public static function alterUrlParams($url, $params) {
    $u = \Civi::url($url);
    $u->addQuery($params);
    return (string)$u;
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
}
