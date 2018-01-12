<!--Control group for <?=$widgetModel->getFieldTitle()?> -->
	<div class="tab-pane" id="<?=$widgetModel->getFieldTitle()?>" >
		<div class="control-group <?=$widgetModel->getAllowMultiple()?"sortableBlock":null?>" id="controlGroup_<?=$widgetModel->getFieldTitle()?>">
			<input type="hidden" name="widget_id" class="widget_id" value="<?=$widgetModel->getFieldId()?>">
			<div class="row tooltipRow">
				<div class="col-sm-12 widgetHeaderRow">
				<?if($widgetModel->getLabel()):?>
				<span class="widgetLabel"><?=$widgetModel->getLabel()?></span>
			<?endif?>
			<?if($widgetModel->getToolTip()):?>
				<small>: <?=$widgetModel->getToolTip()?></small>
			<?endif?>
			<?if($widgetModel->getAllowMultiple()):?>
				<button type="button" class="pull-right btn btn-default addAnother"><span class="glyphicon glyphicon-plus"></span></button>
			<?endif?>
				</div>
			</div>