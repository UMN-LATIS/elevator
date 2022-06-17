var __extends = (this && this.__extends) || (function () {
    var extendStatics = function (d, b) {
        extendStatics = Object.setPrototypeOf ||
            ({ __proto__: [] } instanceof Array && function (d, b) { d.__proto__ = b; }) ||
            function (d, b) { for (var p in b) if (Object.prototype.hasOwnProperty.call(b, p)) d[p] = b[p]; };
        return extendStatics(d, b);
    };
    return function (d, b) {
        if (typeof b !== "function" && b !== null)
            throw new TypeError("Class extends value " + String(b) + " is not a constructor or null");
        extendStatics(d, b);
        function __() { this.constructor = d; }
        d.prototype = b === null ? Object.create(b) : (__.prototype = b.prototype, new __());
    };
})();
var __spreadArray = (this && this.__spreadArray) || function (to, from) {
    for (var i = 0, il = from.length, j = to.length; i < il; i++, j++)
        to[j] = from[i];
    return to;
};
import { matrix, transpose, identity, multiply, add, inv } from "mathjs";
var InvalidLambdaError = /** @class */ (function (_super) {
    __extends(InvalidLambdaError, _super);
    function InvalidLambdaError() {
        return _super !== null && _super.apply(this, arguments) || this;
    }
    return InvalidLambdaError;
}(Error));
var InvalidDataObject = /** @class */ (function (_super) {
    __extends(InvalidDataObject, _super);
    function InvalidDataObject() {
        return _super !== null && _super.apply(this, arguments) || this;
    }
    return InvalidDataObject;
}(Error));
// Positive or 0. Needed in basis functions
var pos = function (val) { return Math.max(val, 0); };
// given collection of {x, y} points, get all x values
var getAllXs = function (pts) { return pts.map(function (pt) { return pt.x; }); };
// given collection of {x, y} points, get all y values
var getAllYs = function (pts) { return pts.map(function (pt) { return pt.y; }); };
/**
 * creates a matrix, X, of basis functions
 * using x values from our data set.
 * We'll need this when solving for betas.
 */
function createBasisMatrix(data) {
    var X = [];
    var _loop_1 = function (i) {
        var x = data[i].x;
        var row = __spreadArray([
            1,
            x,
            Math.pow(x, 2),
            Math.pow(x, 3)
        ], getAllXs(data).map(function (x_k) { return Math.pow(pos(x - x_k), 3); }));
        X.push(row);
    };
    for (var i = 0; i < data.length; i++) {
        _loop_1(i);
    }
    return matrix(X);
}
/**
 * wrapper for matrix multiply that can handle
 * more than 2 arguments
 */
function mult(firstMatrix) {
    var matrices = [];
    for (var _i = 1; _i < arguments.length; _i++) {
        matrices[_i - 1] = arguments[_i];
    }
    return matrices.reduce(function (runningProduct, matrix) { return multiply(runningProduct, matrix); }, firstMatrix);
}
/**
 * Uses Ridge regression to solve the linear system:
 *    X*β = y
 * where:
 *  X is the matrix of basis functions made from the
 *    x values of the given data set
 *  β is the coefficients to our smoothing
 *    spline function
 *  y is the column vector of y values from the given
 *    data set
 *
 * The solution for β̂ can be found with:
 *  β̂ = inverseMatrix(transpose(X) * X + λ*I)
 *       * transpose(X) * y
 *
 * See: https://online.stat.psu.edu/stat857/node/155/
 */
function solveForBetas(data, lambda) {
    var X = createBasisMatrix(data);
    var y = transpose(matrix(getAllYs(data)));
    var Xtrans = transpose(X);
    var numOfColsOfX = X.size()[1];
    // transpose(M) * M + λ*I
    var inner = add(multiply(Xtrans, X), multiply(lambda, identity(numOfColsOfX)));
    var invInner = inv(inner);
    var betas = mult(invInner, Xtrans, y);
    return betas;
}
/**
 * creates a column vector of the spline
 * basis function, transpose([1 x x^2 x^3 ...])
 * To be used to beta coefficients to generate
 * the full spline
 */
function createBasisColVector(x, allXs) {
    return transpose(matrix(__spreadArray([1, x, Math.pow(x, 2), Math.pow(x, 3)], allXs.map(function (x_k) { return Math.pow(pos(x - x_k), 3); }))));
}
function generateSplinePoints(spline, data) {
    var splinePoints = [];
    var minX = Math.min.apply(Math, getAllXs(data));
    var maxX = Math.max.apply(Math, getAllXs(data));
    var stepSize = (maxX - minX) / 1000;
    for (var i = minX; i <= maxX; i += stepSize) {
        splinePoints.push({ x: i, y: spline(i) });
    }
    return splinePoints;
}
function isDataValid(data) {
    var checks = [
        function (data) { return data instanceof Array; },
        function (data) {
            return data.every(function (pt) { return pt.hasOwnProperty("x") && pt.hasOwnProperty("y"); });
        },
    ];
    return checks.every(function (check) { return check(data); });
}
export default function smoothingSpline(data, _a) {
    var _b = _a === void 0 ? {} : _a, _c = _b.lambda, lambda = _c === void 0 ? 1000 : _c;
    if (lambda <= 0) {
        throw new InvalidLambdaError("lambda must be greater than 0");
    }
    if (!isDataValid(data)) {
        throw new InvalidDataObject("Invalid data object. data must be an array of points {x, y}.");
    }
    // the coefficients of our spline
    var betas = solveForBetas(data, lambda);
    // the function that can generate a spline's y
    // from a given x.
    // y = β_0 + β_1*x + β_2*x^2 + β_3*x^3
    //     + β_4*pos((x - x_0)^3)
    //     + β_5*pos((x - x_1)^3)
    //     + ...
    //     + β_{n-1}*pos(x_{n-1} - x_{n-1})^3
    //
    // Or in matrix form:
    // f(x) = βvector * transpose([1 x x^2 x^3 ...])
    var splineFn = function (x) {
        return multiply(betas, // row
        createBasisColVector(x, getAllXs(data)));
    };
    var splinePoints = generateSplinePoints(splineFn, data);
    return {
        fn: splineFn,
        points: splinePoints
    };
}
