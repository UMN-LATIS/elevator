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

	$scope.convertContent = function(){
		var convertedJson = angular.fromJson($scope.jsonText);
		

		var convertedArray = $scope.flattenObject(convertedJson);
		var outputText = "";
		for(var i in convertedArray) {
			outputText += $.csv.fromArrays(convertedArray[i], {"experimental":true}) + "\r\n";
		}

		var blob = new Blob([outputText], {type: "text/csv;charset=utf-8"});
		saveAs(blob, "convertedCSV.csv");
		// $scope.jsonText = outputText;


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
	};

	$scope.flattenObject = function(sourceObject){
		var outputArray = [];
		if(!angular.isArray(sourceObject) && !angular.isObject(sourceObject)) {
			return [[sourceObject]];
		}

		for(var i in sourceObject) {
			if(angular.isString(i) && angular.isArray(sourceObject[i]) && sourceObject[i].length === 0) {
				outputArray.push([i]);
				continue;
			}

			if(angular.isArray(sourceObject[i]) && sourceObject[i].length === 0) {
				continue;
			}

			var childrenRows = $scope.flattenObject(sourceObject[i]);
			for(var j in childrenRows) {
				if(!Number.isInteger(parseInt(i))) {
					childrenRows[j].unshift(i);	
				}
				
				outputArray.push(childrenRows[j]);
			}
		}

		return outputArray;


	};


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

