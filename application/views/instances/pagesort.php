<form action="<?= instance_url("instances/sortUpdate/"); ?>" method="POST" role="form">

<div class="row rowContainer">
	<div class="col-md-12">

		<ul id="sortable_view_order">
		<?php foreach ($this->instance->getPages() as $page): ?>
			<li class="sortHover ui-state-default"><?= $page->getTitle(); ?>
				<input type="hidden" name="page[<?= $page->getId(); ?>]" id="inputViewOrderWidget<?= $page->getId(); ?>TemplateOrder" class="form-control widget-item" value="<?= $page->getSortOrder(); ?>">
			</li>
		<?php endforeach ?>
	</ul>
	</div>
</div>
	<button type="submit" class="btn btn-primary">Submit</button>
<script>
	$(document).ready(function () {

  	// Update the sort order when they are sorted


    $( "#sortable_view_order" ).sortable({
    	update: function (e, ui) {
			// Update the template/view orders of the widgets when the list gets updated
			$('.widget-item').each(function(index) {
				$(this).val(index + 1);
			});
			$("#needsRebuildId").val(1);
		}
    });

    $( "#sortable_view_order" ).disableSelection();


  });
  </script>
