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
    // fixme3: we must support adding a css class name to any field in the afform.
    // This is possible only with civicrm patch:
    // https://github.com/civicrm/civicrm-core/pull/32266/
    //
    
    // $doc is a phpquery object:
    //  - built with code in: https://github.com/TobiaszCudnik/phpquery)
    //  - Best phpquery documentation I've found so far: https://github.com/electrolinux/phpquery/blob/master/wiki/README.md

    // fixme3: Change any RBAC (role-based) security to FBAC (form-based), because
    //   we want this to work for anon users; we are going to handle
    //   validation/permissions via stepw_* query params.
    //  $doc->find('af-entity')->attr('security', 'FBAC');
    
    // Force 'url-autofill' to true for Individual1, so that we're sure to support
    // continued tracking of the contact Id (created from the first form submission)
    // in subsequent forms in the workflowInstance.
    $doc->find('af-entity[type="Individual"]')->attr('url-autofill', '1');

    // Change the button properties and relocate it into a new <div> that uses our controller.
    $button = $doc->find('button[ng-click="afform.submit()"]');
    $button->attr('ng-if', 'stepwiseShowSubmitButton()');
    $buttonOriginalHtml = $button->html();
    $button->html('{{ submitButtonLabel ? submitButtonLabel : "' . $buttonOriginalHtml . '" }}');
    $buttonHtml = $button->htmlOuter();
    $button->remove();
    $appendToDoc = <<< "END"
      <div ng-controller="stepwAfform">
      $buttonHtml
      {{ hideIfInvalid() }}
      </div>
    END;   
    $doc->find('af-form')->append($appendToDoc);
    
  }
}
