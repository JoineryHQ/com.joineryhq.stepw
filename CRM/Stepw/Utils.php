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
}
