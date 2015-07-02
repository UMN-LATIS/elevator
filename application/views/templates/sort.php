
<div class="row rowContainer">
	<div class="col-md-12">
 <style>
	  #sortable { list-style-type: none; margin: 0; padding: 0; width: 60%;cursor:default; }
	  #sortable li { margin: 0 3px 3px 3px; padding: 0.4em; padding-left: 1.5em; font-size: 1.4em; height: 18px; }
	  #sortable li span { position: absolute; margin-left: -1.3em; }
  </style>

<form action="<?= instance_url("templates/sort_update/"); ?>" method="POST" role="form">
<input type="hidden" name="templateId" id="inputTemplateId" class="form-control" value="<?= $template->getId(); ?>">
	View Order
	<ul id="sortable_view_order">
		<?php foreach ($widgetsViewOrder as $key => $widget): ?>
			<li class="ui-state-default"><?= $widget->getFieldTitle(); ?>
				<input type="hidden" name="widget[<?= $widget->getId(); ?>][view_order]" id="inputViewOrderWidget<?= $widget->getId(); ?>TemplateOrder" class="form-control widget-item" value="<?= $widget->getTemplateOrder(); ?>">
			</li>
		<?php endforeach ?>
	</ul>
<br><br>
	Template Order
	<ul id="sortable_template_order">
		<?php foreach ($widgetsTemplateOrder as $key => $widget): ?>
			<li class="ui-state-default"><?= $widget->getFieldTitle(); ?>
				<input type="hidden" name="widget[<?= $widget->getId(); ?>][template_order]" id="inputTemplateOrderWidget<?= $widget->getId(); ?>TemplateOrder" class="form-control widget-item" value="<?= $widget->getTemplateOrder(); ?>">
			</li>
		<?php endforeach ?>
	</ul>

	<br>
	<button type="submit" class="btn btn-primary">Submit</button>
</form>
</div>
</div>
<script>
	$(document).ready(function () {

  	// Update the sort order when they are sorted


    $( "#sortable_view_order" ).sortable({
    	update: function (e, ui) {
			// Update the template/view orders of the widgets when the list gets updated
			$('.widget-item').each(function(index) {
				$(this).val(index + 1);
			});
		}
    });

    $( "#sortable_view_order" ).disableSelection();

    // Update the sort order when they are sorted
    $( "#sortable_template_order" ).sortable({
    	update: function (e, ui) {
			// Update the template/view orders of the widgets when the list gets updated
			$('.widget-item').each(function(index) {
				$(this).val(index + 1);
			});
		}
    });

    $( "#sortable_template_order" ).disableSelection();
  });
  </script>
