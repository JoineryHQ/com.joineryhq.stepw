(function(angular, $, _) {
  // Declare a list of dependencies.
  angular.module('stepwAfform', CRM.angRequires('stepwAfform'));

  angular.module('stepwAfform').controller('stepwAfform', function($scope, crmApi4) {

      $scope.init = function() {
        // Set button label text.
        if (CRM.vars.stepw) {
          $scope.submitButtonLabel = CRM.vars.stepw.submitButtonLabel;
          // Set redirect property per workflow step config.
          $scope.$parent.meta.redirect = CRM.vars.stepw.redirect;
        }
        console.log('scope', $scope);
        
        // fixme: the button should only show under these conditions
        // - we're not in a stepwise workflow; AND
        // - $scope.$parent.afform.showSubmitButton is true
        // OR
        // - we are in a stepwise workflow; AND
        // - stepwise workflow is valid; AND
        $scope.stepwiseShowSubmitButton = true;
        var parentShowSubmitButton = $scope.$parent.afform.showSubmitButton;
      }
      $scope.init();

    });  
})(angular, CRM.$, CRM._);
