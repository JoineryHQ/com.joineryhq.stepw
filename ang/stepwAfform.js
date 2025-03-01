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
        
        // fixme: the button should only show under these conditions
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
          // Note: We'll hide the form if either afform.sid or CRM.vars.stepw.stepAfformSid
          // are provided, AND they don't match each other. HOWEVER, this form
          // hiding is merely a convenience, to prevent a confusing user experience
          // in the event that someone is mucking about with the parameters
          // (which is the only way such a mismatch should happen).
          // This form hiding is NOT a security measure. Validation of the sid
          // is happening on server-side. If this were a security measure,
          // we'd need to be doing something more secure than comparing the value 
          // of globally editable values in CRM.vars against the value of afform.sid,
          // which comes from the URL #fragment.
          
          var sid = Number($scope.$parent.routeParams.sid);
          
          if (
            // We don't do anything if there's no crm.vars.stepw (because it means
            // we'er not in a stepwise workflow.)
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
