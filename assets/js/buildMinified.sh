cat serializeForm.js dateWidget.js template.js > master.js
yuicompressor -o master.min.js master.js
cat assetView.js drawers.js > assetMaster.js
yuicompressor -o assetMaster.min.js assetMaster.js
cat search.js searchForm.js > searchMaster.js
yuicompressor -o searchMaster.min.js searchMaster.js 

