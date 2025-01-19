<?php

/**
 * Description of CRM_Stepw_APIWrapper
 *
 * @author as
 */
class CRM_Stepw_APIWrapper {

  public static function RESPOND($event) {
    $request = $event->getApiRequestSig();
    $apiRequest = $event->getApiRequest();
    $result = $event->getResponse();

    if ($request == "4.afform.get") {
      $a = $result[0];
      $a = 1;

      if (!empty($result[0]['redirect'])) {
        $result[0]['redirect'] = 'http://example.com';
        $session = $_SESSION['CiviCRM'];
        $get = $_GET;
        $event->setResponse($result);
      }
    }
  }

}
