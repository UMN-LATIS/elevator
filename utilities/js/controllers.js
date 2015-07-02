var cascadeController = angular.module('cascadeController', ['ngAnimate']);

cascadeController.directive('selectOnClick', function () {
    return {
        restrict: 'A',
        link: function (scope, element, attrs) {
            element.on('click', function () {
                this.select();
            });
        }
    };
});


cascadeController.controller('CascadeController', ['$scope', function($scope) {
	$scope.showContent = function($fileContent){
		var csvContent = $scope.cleanArray($.csv.toArrays($fileContent));

		var multilevelArray = {};

		for (var i = 0; i <  csvContent.length; i++) {
			var blankArray = {};
			var j = csvContent[i].length - 1;
			var lastElement = {};
			lastElement[[$.trim(csvContent[i][j])]] = {};

			j--;

			if(j === 0) {
				blankArray = lastElement;
			}
			while(j>0) {
				blankArray = {};
				blankArray[$.trim(csvContent[i][j])] = lastElement;
				lastElement = blankArray;
				j--;
			}
			var firstKey = $.trim(csvContent[i][0]);
			if(multilevelArray[firstKey] !== undefined) {
				$scope.extendDeep(blankArray, multilevelArray[firstKey]);
				multilevelArray[firstKey] = blankArray;
			}
			else {
				multilevelArray[firstKey] = blankArray;
			}


		}

		var source = angular.toJson(multilevelArray, true);
		$scope.content = source;



	};
	$scope.extendDeep = function extendDeep(dst) {
		angular.forEach(arguments, function(obj) {
	if (obj !== dst) {
		angular.forEach(obj, function(value, key) {
		if (dst[key] && dst[key].constructor && dst[key].constructor === Object) {
			extendDeep(dst[key], value);
		} else {
			if(dst[key] && dst[key].constructor && dst[key].constructor === Array) {
				$.merge(value, dst[key]);
			}

			dst[key] = value;
		}
		});
	}
  });
  return dst;
};
	$scope.cleanArray = function(actual){
		var newArray = [];
		for(var i = 0; i<actual.length; i++){
			if(actual[i] instanceof Array) {
				newArray.push($scope.cleanArray(actual[i]));
			}
			else if (actual[i] && actual[i] !== ""){
				newArray.push(actual[i]);
			}
		}
		return newArray;
	}

}]);


cascadeController.directive('onReadFile', function ($parse) {
	return {
		restrict: 'A',
		scope: false,
		link: function(scope, element, attrs) {
			var fn = $parse(attrs.onReadFile);

			element.on('change', function(onChangeEvent) {
				var reader = new FileReader();

				reader.onload = function(onLoadEvent) {
					scope.$apply(function() {
						fn(scope, {$fileContent:onLoadEvent.target.result.replace(/\r/g, "\n")});
					});
				};

				reader.readAsText((onChangeEvent.srcElement || onChangeEvent.target).files[0]);
			});
		}
	};
});


// angular.module('app', []).controller('MyController', MyController);

