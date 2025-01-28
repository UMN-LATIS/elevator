import { terser } from "rollup-plugin-terser";
import sourcemaps from "rollup-plugin-sourcemaps";
import path from "node:path";
import fs from "node:fs";

const baseDir = path.resolve(__dirname, "assets", "build", "js");

const config = [
  // Task: 3dhop
  {
    input: "./assets/3dviewer/js/index.mjs",
    output: {
      file: path.join(baseDir, "3dhop.min.js"),
      format: "iife",
      sourcemap: true,
    },
    plugins: [terser(), sourcemaps()],
  },
  // Task: webRTI
  {
    input: "./assets/webViewer/spidergl/multires.js",
    output: {
      file: path.join(baseDir, "webrti.min.js"),
      format: "iife",
      sourcemap: true,
    },
    plugins: [terser(), sourcemaps()],
  },
  // Task: basicFiles
  {
    input: "./assets/js/serializeForm.js",
    output: {
      file: path.join(baseDir, "serializeDateTemplate.min.js"),
      format: "iife",
      sourcemap: true,
    },
    plugins: [terser(), sourcemaps()],
  },
  // Task: assetMaster
  {
    input: "./assets/js/assetView.js",
    output: {
      file: path.join(baseDir, "assetMaster.min.js"),
      format: "iife",
      sourcemap: true,
    },
    plugins: [terser(), sourcemaps()],
  },
  // Task: searchMaster
  {
    input: "./assets/js/search.js",
    output: {
      file: path.join(baseDir, "searchMaster.min.js"),
      format: "iife",
      sourcemap: true,
    },
    plugins: [terser(), sourcemaps()],
  },
];

// add configs for each individual JS file in `assets/js` dir
// maybe it's better to use a single entry point for all the JS files?
const individualFiles = fs
  .readdirSync("./assets/js")
  .filter((file) => file.endsWith(".js"));
const configsForIndividualFiles = individualFiles.map((file) => {
  return {
    input: `./assets/js/${file}`,
    output: {
      file: path.join(baseDir, `${file.replace(".js", ".min.js")}`),
      format: "iife",
      sourcemap: true,
    },
    plugins: [terser(), sourcemaps()],
  };
});

config.push(...configsForIndividualFiles);

export default config;
