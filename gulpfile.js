import gulp from "gulp";

/* import uglify from "gulp-uglify";
import cleanCss from "gulp-clean-css"; */
import zip from "gulp-zip";

import { src, dest, watch, series } from "gulp";
//import concat from "gulp-concat";

import {} from "gulp";

const pluginFolder = "ri-was-this-helpful"; // Sostituisci con il nome della tua cartella del plugin

export const zipPlugin = () =>
  src([
    `./**`,
    "!./node_modules/**",
    "!./gulpfile.js",
    "!./package.json",
    "!./package-lock.json",
    "!./.gitignore",
    "!./.vscode/**",
    "!./helpful-box-block/node_modules/**",
    "!./helpful-box-block/package.json",
    "!./helpful-box-block/package-lock.json",
    "!./helpful-box-block/.gitignore",
    "!./helpful-box-block/.editorconfig",
  ])
    .pipe(zip("ri-was-this-helpful.zip"))
    .pipe(dest("./../"));
