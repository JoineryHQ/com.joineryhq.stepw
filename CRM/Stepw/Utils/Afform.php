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

    // fixme: Change any RBAC (role-based) security to FBAC (form-based), because
    //   we want this to work for anon users; we are going to handle
    //   validation/permissions via stepw_* query params.
    //  $doc->find('af-entity')->attr('security', 'FBAC');
    
    // Force 'url-autofill' to true for Individual1, so that we're sure to support
    // continued tracking of the contact Id (created from the first form submission)
    // in subsequent forms in the workflowInstance.
    $doc->find('af-entity[type="Individual"]')->attr('url-autofill', '1');

    // Change the button properties and relocate it into a new <div> that uses our controller.
    /* TODO: Here I'm altering the form to achieve  the following:
     *  - Overide the logic that hides the submit button, by replacing the
     *    button's ng-if attribute with my own. 
     *  - Alter the button label with a value that's passed to CRM.vars in hook_civicrm_pageRun().
     * 
     * But i had scoping issues, and the only way I could 
     * figure to resolve that was to wrap the button in a div that uses my custom 
     * controller.
     * 
     * Scoping issue were (I think):
     * - my function stepwiseShowSubmitButton() does not exist in current $scope,
     *   and I can't see how to add it thereto.
     * - my angular controller's init() function doesn't seem to be able to modify
     *   variables in current $scope.
     * 
     * -- per coleman: Apparently this is a race condition in my controller.init(),
     *    in which $scope.$parent.$scope is not completely ready by the time my init()
     *    fires. So, todo: implement in controller:
     *     $timeout(function_name, 0) with anonymous function to access (modify?) $scope.$parent.$scope
     */
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
