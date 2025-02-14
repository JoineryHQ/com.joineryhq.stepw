<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

/**
 * Description of Afform
 *
 * @author as
 */
class CRM_Stepw_Utils_Afform {
  public static function alterForm(phpQueryObject $doc) {
    // $doc is a phpquery object:
    //  - built with code in: https://github.com/TobiaszCudnik/phpquery)
    //  - Best phpquery documentation I've found so far: https://github.com/electrolinux/phpquery/blob/master/wiki/README.md

    // Change any RBAC security to FBAC
    // fixme: this MUST be done only after we verify the workflowInstance, step, and contactId
  //  $doc->find('af-entity')->attr('security', 'FBAC');
    $doc->find('af-entity[type="Individual"]')->attr('url-autofill', '1');

    // Change the button properties and relocate it into a new <div> that uses our controller.
    $button = $doc->find('button[ng-click="afform.submit()"]');
    $button->attr('ng-if', 'stepwiseShowSubmitButton');
    $buttonOriginalHtml = $button->html();
    $button->html('{{ submitButtonLabel ? submitButtonLabel : "' . $buttonOriginalHtml . '" }}');
    $buttonHtml = $button->htmlOuter();
    $button->remove();
    $appendToDoc = <<< "END"
      <div ng-controller="stepwAfform">
      $buttonHtml
      </div>
    END;
    $doc->find('af-form')->append($appendToDoc);
    
  }
}
