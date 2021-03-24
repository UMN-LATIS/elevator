## Repository Link: [leaflet-treering](https://github.com/UMN-LATIS/leaflet-treering)

## To Run Code:
1. Select the green 'Code' dropdown while on the 'main' GitHub branch.
2. Select 'Download ZIP' or open file with GitHub Desktop.
3. Unzip/Extract files from ZIP folder.
4. Run any .html file in folder through preferred browser.

## General Code Style/Organization:
####  Variable Conventions:
Simple variables use CamelCase naming convention. More complex variables or variables which relate to other variables use SnakeCase.

Descriptive variables names should be used along with comments explaining **why** code exists are required.

Example:
```
// Asset title in separate parts so title can be arranged in any order
var assetArea = 'CCS';
var assetNumber = '130';
var assetCode = 'A';
var asset_area_number_code = assestArea + assetNumber + assetCode;
```

####  Function Conventions:
Syntax:
- **Constructor:** &nbsp; `function ThisThat ()`
- **Global:** &nbsp; `function hereThere ()`
- **Prototype:** &nbsp; `ThisThat.prototype.whoWhat = function ()`

Functions which are used more than once or have the possibility to be reused in the future are located under `Function Helper()` at the bottom.

Multi-line comments above them explaining **purpose**, **function type**, and **inputs** are required.

Example:
```
/**
 * Calculate distance between two points
 * @function distanceCalc
 * @param {point A} - ptA
 * @param {point B} - ptB
 */
 function distanceCalc (ptA, ptB) {...}
```

####  Object/Method Connections:
Callback functions, located under LTreering, are used to connect all objects and methods. Most functions input 'Lt', short for LTreering, as their keyword to access object/methods from other areas of leaflet-treering.js.

Example:
```
function AssetAttributes (Lt) {
  this.name = Lt.meta.assetName;
  this.pointsIndex = Lt.data.index;
  this.enableMeasurements = Lt.createPoint.enable();
}
```

## How to Test Code:
All tools/features should be tested on multiple scenarios through at least Google Chrome, Mozilla FireFox, and Safari before merging to main.

#### All Tools/Features:
- **Annotations:**
  - Create annotation
  - Drag annotation
  - Edit annotation
  - Delete annotation
- **Dating:**
  - Change date of last point
  - Change date of non-last point
  - Check change applied to all points
- **Create measurements forward, one increment per year:**
  - Create measurement line
  - Create zero growth year
  - Create breakpoint
- **Create measurements backward, one increment per year:**
  - Create measurement line
  - Create zero growth year
  - Create breakpoint
- **Create measurements forward, two increments per year:**
  - Create measurement line
  - Create zero growth year
  - Create breakpoint
- **Create measurements backward, two increments per year:**
  - Create measurement line
  - Create zero growth year
  - Create breakpoint
- **Edit measurements:**
  - Delete point
  - Cut section of points
  - Add point
  - Add zero growth year
  - Add breakpoint **(BROKEN)**
- **Settings:**
  - Calibrate PPM
  - Change lighting/hues
  - Attempt to modify preferences with & without points
- **Saving JSON file:**
  - Save local copy
  - Upload local copy
- **View measurement data:**
  - Download Ascii file to check format
  - Delete data
  - Create & edit measurements


####  Various Measurement Scenarios:
Different scenarios are located in index_**descriptor_descriptor** .html files.

Current scenarios:
  - **index_backwards_annual:** &nbsp; Points measured annually backward in time.
  - **index_backwards_subAnnual:** &nbsp; Points measured sub-annually backward in time.
  - **index_hasPoints_hasLatewood:** &nbsp; Points measured sub-annually forward in time using true hasLatewood attribute.
  - **index_hasPoints_subAnnual:** &nbsp; Points measured sub-annually forward in time using false sub annual attribute.
  - **index_noPoints:** &nbsp; No points measured. Blank tree core.
