var myApp = angular.module('cascadeCreator', ['ngRoute', 'cascadeController']);

myApp.config(['$routeProvider', function($routeProvider) {
	$routeProvider.
	when('/create', {
		templateUrl: 'partials/create.html',
		controller: 'CascadeController'
	}).
	otherwise({
		redirectTo: '/create'
	});
}]);