(function(angular, $, _) {
  // Declare a list of dependencies.
  angular.module('stepwAfform', CRM.angRequires('stepwAfform'));

  angular.module('stepwAfform').controller('stepwAfform', function($scope, crmApi4) {

      $scope.init = function() {
        // Set button label text.
        if (CRM.vars.stepw) {
          $scope.submitButtonLabel = CRM.vars.stepw.submitButtonLabel;
          // Set redirect property per workflow step config.
          $scope.$parent.meta.redirect = CRM.vars.stepw.redirectUrl;
        }
        console.log('scope', $scope);
        
        // fixme3: the button should only show under these conditions
        // - we're not in a stepwise workflow; AND
        // - $scope.$parent.afform.showSubmitButton is true
        // OR
        // - we are in a stepwise workflow; AND
        // - stepwise workflow is valid;
        $scope.stepwiseShowSubmitButton = function() {
          return true;
          // return $scope.$parent.afform.showSubmitButton;
        };
        
        $scope.hideIfInvalid = function() {
          var sid = Number($scope.$parent.routeParams.sid);
          
          if (
            // we don't do anything if there's no crm.vars.stepw (because it means
            // we'er not in a stepwise workflow.
            typeof CRM.vars.stepw != 'undefined'
            // If we have either of: afform 'sid' or the sid given in crm.vars, then:
            // they must be equal to each other, or this is some kind of url 
            // tampering. (See more about 'stepAfformSid' in hook_civicrm_pageRun().
            && (sid || CRM.vars.stepw.stepAfformSid)
            && (sid !== CRM.vars.stepw.stepAfformSid)
          ){
            // URL tampering detected. Replace the afform with an "invalid" message.
            CRM.$('af-form').replaceWith('<p>Invaild request</p>');            
          }
          
        }
      }
      $scope.init();

    });  
})(angular, CRM.$, CRM._);
