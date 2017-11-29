

<script id="result-template" type="text/x-handlebars-template">
<div class="col-lg-3 col-sm-6 col-md-4">
<div class="resultContainer searchContainer">
	<h5><a class="assetLink" href="{{base_url}}asset/viewAsset/{{objectId}}">{{#if title.length}} {{{join title}}} {{else}} (no title) {{/if}}</a></h5>
	<div class="previewCrop ">
	{{#if primaryHandlerThumbnail}}
		<a href="{{base_url}}asset/viewAsset/{{objectId}}" class="assetLink"><img class="img-responsive previewImage" src="{{primaryHandlerThumbnail}}" srcset="{{primaryHandlerThumbnail2x}} 2x"></a>
	{{/if}}
	{{#if fileAssets}}
		<span class="badge alert-success"><span class="glyphicon glyphicon-eye-open"></span> {{fileAssets}}</span>
	{{/if}}
	</div>
	<div class="previewContent">


		{{#each entries}}
		<div class="previewEntry"> <strong>{{this.label}}:</strong><ul>
					{{#each this.entries}}
						<li>{{{this}}}</li>
					{{/each}}
				</ul>
		</div>
		{{/each}}
	</div>
	</div>
</div>
</script>

<script id="list-template" type="text/x-handlebars-template">
<div class="row listResultContainer searchContainer">
	<div class="col-md-2 listImageContainer">
			<a href="{{base_url}}asset/viewAsset/{{objectId}}" class="assetLink"><img class="img-responsive listPreviewImage" src="{{primaryHandlerThumbnail}}" srcset="{{primaryHandlerThumbnail2x}} 2x"></a>
	</div>
	<div class="col-md-9 listTextContainer">
		<h3><a href="{{base_url}}asset/viewAsset/{{objectId}}" class="assetLink">{{#if title.length}} {{{join title}}} {{else}} (no title) {{/if}}</a></h3>
			{{#each entries}}
			<div class="previewEntry"> <strong>{{this.label}}:</strong><ul>
					{{#each this.entries}}
						<li>{{this}}</li>
					{{/each}}
				</ul>
			</div>
			{{/each}}
	</div>
</div>

</script>

<script id="drawer-list-template" type="text/x-handlebars-template">
{{#if this.excerpt}}
<div class="row rowPadding listResultContainer searchContainer {{excerptId}}">
{{else}}
<div class="row rowPadding listResultContainer searchContainer {{objectId}}">
{{/if}}
	<div class="col-md-2 listImageContainer">
		{{#if this.excerpt}}
			<a href="{{base_url}}asset/viewExcerpt/{{excerptId}}"><img class="img-responsive listPreviewImage" src="{{base_url}}fileManager/previewImageByFileId/{{excerptAsset}}" srcset="{{base_url}}fileManager/previewImageByFileId/{{excerptAsset}}/true 2x"></a>
		{{else}}
			<a href="{{base_url}}asset/viewAsset/{{objectId}}"><img class="img-responsive listPreviewImage" src="{{primaryHandlerThumbnail}}" srcset="{{primaryHandlerThumbnail2x}} 2x"></a>
		{{/if}}
	</div>
	<div class="col-md-9 listTextContainer">
	{{#if this.excerpt}}
		<h3><a href="{{base_url}}asset/viewExcerpt/{{excerptId}}">{{{join title}}} (excerpt) </a></h3>
		{{else}}
		<h3><a href="{{base_url}}asset/viewAsset/{{objectId}}">{{{join title}}} </a></h3>
	{{/if}}
			{{#each entries}}
			<div class="previewEntry"> <strong>{{this.label}}:</strong><ul>
					{{#each this.entries}}
						<li>{{{this}}}</li>
					{{/each}}
				</ul>
			</div>
			{{/each}}
	{{#if this.excerpt}}
		<button class="btn btn-primary removeButton" data-assettype="excerpt" data-excerptid="{{excerptId}}">Remove</button>
	{{else}}
		<button class="btn btn-primary removeButton" data-assettype="object" data-assetid="{{objectId}}">Remove</button>
	{{/if}}

	</div>
</div>
</script>



<script id="drawer-template" type="text/x-handlebars-template">
{{#if this.excerpt}}
<div class="col-lg-3 col-sm-6 col-md-4 {{excerptId}}">
{{else}}
<div class="col-lg-3 col-sm-6 col-md-4 {{objectId}}">
{{/if}}
<div class="resultContainer searchContainer drawerContainer">
	{{#if this.excerpt}}
	<h5><a class="assetLink" href="{{base_url}}asset/viewExcerpt/{{excerptId}}">{{excerptLabel}}</a></h5>
	{{else}}
	<h5><a class="assetLink" href="{{base_url}}asset/viewAsset/{{objectId}}">{{#if title.length}} {{join title}} {{else}} (no title) {{/if}}</a></h5>
	{{/if}}
	<div class="previewCrop">
		{{#if this.excerpt}}
	<a href="{{base_url}}asset/viewExcerpt/{{excerptId}}"><img class="img-responsive previewImage" src="{{base_url}}fileManager/previewImageByFileId/{{excerptAsset}}" srcset="{{base_url}}fileManager/previewImageByFileId/{{excerptAsset}}/true 2x"></a>
	{{else}}
	<a href="{{base_url}}asset/viewAsset/{{objectId}}"><img class="img-responsive previewImage" src="{{primaryHandlerThumbnail}}" srcset="{{primaryHandlerThumbnail2x}} 2x"></a>
	{{/if}}
	</div>
	<div class="previewContent">
		{{#each entries}}
		<div class="previewEntry"> <strong>{{this.label}}:</strong><ul>
					{{#each this.entries}}
						<li>{{{this}}}</li>
					{{/each}}
				</ul>
		</div>
		{{/each}}
	{{#if this.excerpt}}
		<button class="btn btn-primary removeButton" data-assettype="excerpt" data-excerptid="{{excerptId}}">Remove</button>
	{{else}}
		<button class="btn btn-primary removeButton" data-assettype="object" data-assetid="{{objectId}}">Remove</button>
	{{/if}}
	</div>

	</div>
</div>

</script>

<script id="autocompleter-template" type="text/x-handlebars-template">

<div class="autoCompleterBlock">
	<div class="row">
		<a>
		<div class="col-sm-3">

			<img class="img-responsive" src="{{primaryHandlerThumbnail}}">
		</div>
		<div class="col-sm-7 autoCompleteObjectInfo">
			<ol>
				<li class="autocompleteDescription" role="presentation" data-value="{{objectId}}">
					<p><strong>{{title}}</strong></p>
					{{#each entries}}
		<div class="previewEntry"><strong>{{this.label}}:</strong> <ul>
					{{#each this.entries}}
						<li>{{this}}</li>
					{{/each}}
				</ul>
		</div>
		{{/each}}
				</li>
			</ol>
		</div>
		</a>
	</div>
</div>

</script>


<script id="minipreview-template" type="text/x-handlebars-template">
<div class="row minipreviewContainer">
	<div class="col-sm-3">
		<img class="img-responsive" src="{{base_url}}fileManager/previewImage/{{objectId}}?{{random}}" srcset="{{base_url}}fileManager/previewImage/{{objectId}}/true?{{random}} 2x">
	</div>
	<div class="col-sm-9">
		<p>{{join title}}</p>

			{{#each entries}}
			<div class="previewEntry"> <strong>{{this.label}}:</strong><ul>
						{{#each this.entries}}
							<li>{{this}}</li>
						{{/each}}
					</ul>
			</div>
			{{/each}}
	</div>
</div>
</script>


<script id="person-autocompleter-template" type="text/x-handlebars-template">

<div class="autoCompleterBlock">
	<div class="row">
		<a>

		<div class="col-sm-7 autoCompleteObjectInfo">
			<ol>
				<li class="autocompleteDescription" role="presentation" data-value="{{username}}">
					<p>{{name}}</p>
					<p>{{email}}</p>
				</li>
			</ol>
		</div>
		</a>
	</div>
</div>

</script>


<script id="marker-template" type="text/x-handlebars-template">
<div class="row marker-template">
	<div class="col-md-12">
	<img class="img-responsive pull-left" src="{{primaryHandlerThumbnail}}">
	<p><a href="{{base_url}}asset/viewAsset/{{objectId}}">{{title}}</a></p>
	</div>
</div>
</script>

<script id="related-template" type="text/x-handlebars-template">
<div class="row relatedAsset">
	<div class="col-md-12">
	<img class="img-responsive pull-left" src="{{primaryHandlerThumbnail}}">
	<p><a href="{{base_url}}asset/viewAsset/{{objectId}}">{{title}}</a></p>
	</div>
</div>
</script>



<script id="template-switch" type="text/x-handlebars-template">

<p>Switching templates may result in the loss of data.  The following fields are not present in the new template:</p>
<ul>
	{{#each this}}
	<li>{{this.label}} ({{this.type}})</li>
	{{/each}}
</ul>

</script>

<script id="collection-switch" type="text/x-handlebars-template">

<p>Switching collections will prevent this asset from being accessed while the migration is taking place. It may also make assets temporarily unavilable.</p>


</script>
