(function(angular) {
  'use strict';
  
  angular.module('filterExample', [])
  .controller('MainCtrl', function($scope, $filter) {
  	$scope.originalText = 'hello';
  	$scope.filteredText = $filter('uppercase')($scope.originalText);
  })
  .controller("NameTest", function($scope, $filter){
  	$scope.namePeople = "Erik";
  	console.log($scope.__proto__);
  });

})(window.angular);