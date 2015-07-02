#!/bin/bash

BASEPATH=$1
pandoc -s -S --toc -V documentclass=report --template=/Users/colin/Documents/Development/divergentDocumentation/pandocTemplates/customTemplate.tex --variable fontsize=12pt $BASEPATH.md --latex-engine=xelatex -o "$BASEPATH Manual.pdf"
