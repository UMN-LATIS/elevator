# Smoothing Spline

> Fit a smoothing spline to collection of data points

## Usage

```js
const data = [
  { x: 1, y: 0.5 },
  { x: 2, y: 3 },
  { x: 3, y: 8.5 },
  { x: 4, y: 20 },
  { x: 1, y: 1 },
  { x: 2, y: 5 },
  { x: 3, y: 11 },
  { x: 4, y: 15 },
];

const spline = smoothingSpline(data, { lambda: 1000 });

// spline.points is a collection of {x, y} values
// between the min and max the x values in the data set
graphItWithYourOwnFunction(spline.points);

// spline.fn is a function that can return a y for a given x
const myY = spline.fn(2.5);
// 6.25
```

## Installation

```console
npm install simple-smoothing-spline
```

## API

### `smoothingSpline(data, opts)`

Parameters:

- `data` - an array of data points in the form of `{x: 1, y: 2}`.
- `opts.lambda = 1000` - lambda parameter for Ridge regression. This is the tuning parameter for the regression function. The higher the lambda, the smoother the spline will be. By default, this is 1000.

Returns:

- `spline.points` - An array of `{x, y}` points on the smoothing spline in the range of the data (between min(x) and max(x)).
- `spline.fn` - A function to get an arbitrary f(x) for a given x.
  Example:
  ```js
  const spline = smoothingSpline(data, { lambda: 2 ** 8 });
  const y = spline.fn(3);
  // y is value on the spline when x = 3
  ```

## Example

To see an example of using this package with [plotlyjs](https://github.com/plotly/plotly.js/) check out the [example folder](https://github.com/UMN-LATIS/simple-smoothing-spline/tree/main/example).

## About

- [Background notes and references](./NOTES.md)
- [Maintainers](.github/CODEOWNERS)
- [Contributors](https://github.com/UMN-LATIS/simple-smoothing-spline)

## License

MIT
