cat serializeForm.js dateWidget.js template.js > master.js
yui-compressor -o master.min.js master.js
cat assetView.js drawers.js > assetMaster.js
yui-compressor -o assetMaster.min.js assetMaster.js
cat search.js searchForm.js > searchMaster.js
yui-compressor -o searchMaster.min.js searchMaster.js 

