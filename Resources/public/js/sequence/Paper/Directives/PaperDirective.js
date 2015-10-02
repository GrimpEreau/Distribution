
(function () {
    'use strict';

    angular.module('Paper').directive('paperDetails', [
        function () {
            return {
                restrict: 'E',
                replace: true,
                controller: 'PaperCtrl',
                controllerAs: 'paperCtrl',
                templateUrl: AngularApp.webDir + 'bundles/ujmexo/js/sequence/Paper/Partials/paper.details.html',
                scope: {
                    sequence: '=',
                    paper: '='
                },
                link: function (scope, element, attr, paperCtrl) {
                    console.log('paperDetails directive link method called');
                    paperCtrl.init(scope.sequence, scope.paper);
                }
            };
        }
    ]);
})();


