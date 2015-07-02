
<strong><?=$widgetModel->getLabel()?>:</strong>
<ul>
	<?foreach($widgetModel->fieldContentsArray as $fieldContent):?>
		<?
		$haveCoordinates = false;
		if(isset($fieldContent->locationLabel) && strlen($fieldContent->locationLabel)>0) {
			$label = $fieldContent->locationLabel;
		}
		else {
			$label = $fieldContent->latitude . ", " . $fieldContent->longitude;
		}

		if($fieldContent->latitude != 0 && $fieldContent->longitude != 0) {
			$haveCoordinates = true;
		}

		?>
	<li>

		<?if($haveCoordinates):?>
		<A href="#mapModal"  data-toggle="modal" data-latitude="<?=$fieldContent->latitude?>" data-longitude="<?=$fieldContent->longitude?>">
		<?endif?>
			<?=$label?>
		<?if($haveCoordinates):?>
		</a>
		<?endif?>
	</li>
	<?endforeach?>
</ul>

