<script>

window.offsetCount = new Array();

<?if($this->user_model->user->getFastUpload()):?>
var fastUpload = true;
<?else:?>
var fastUpload = false;
<?endif?>

</script>


<?
$objectId = null;
$readyForDisplay = "CHECKED";
$collectionId = null;
$availableAfter = null;

if(isset($asset)) {
	$objectId = $asset->getObjectId();
	$readyForDisplay = $asset->getGlobalValue("readyForDisplay")?"CHECKED":null;
	$collectionId = $asset->getGlobalValue("collectionId");
	if($asset->getGlobalValue("availableAfter") && $asset->getGlobalValue("availableAfter")->getTimestamp() > 0) {
		$availableAfter = $asset->getGlobalValue("availableAfter")->format("Y-m-d");
	}

}

if(strlen($this->template->collectionId)>0) {
	$collectionId = intval($this->template->collectionId->__toString());
}


?>

<?if(isset($asset) && $asset->getGlobalValue("collectionMigration")):?>
<input type="hidden" id="collectionMigrationInProcess" value="true">
<?else:?>
<input type="hidden" id="collectionMigrationInProcess" value="false">
<?endif?>


<form class="form-horizontal clean" role="form" method="post" name="entryForm" id="entryForm" novalidate onSubmit="submitForm(); return false;">

<style>
.affix-bottom {
    position: relative
}
@media (max-width: 767px) {
    .affix {
        position: static;
    }
}
@media (min-width: 768px) {
    .affix, .affix-top {
        /*position: fixed;*/
    }

	.floatTabList {
		
		background-color: white;
	}
	.floatTabList {
		/*position: fixed;*/
		top: 50px;
		display: block;
		/*width: 25%;*/
		padding-left: 15px;
		margin-left: -15px;
		overflow-y: auto;
		overflow-x: hidden;
		height: 93%;
	}
	.floatTableList.affix {
		/*top: 50px;*/
	}

}


@media screen and (min-width: 768px) {
	.floatTabList {
		width: 157px;
	}
}



@media screen and (min-width: 992px) {
	.floatTabList {
		width: 213px;
	}
}

@media screen and (min-width: 1200px) {
	.floatTabList {
		width: 262px;
	}

}

.tab-pane {
	padding-top: 5px;
	padding-right: 5px;
	padding-bottom: 5px;
	margin-top: 5px;
	margin-bottom: 5px;
	background-color: #eff2f3;
	border: 1px solid #96a2a7;
	border-radius: 3px;
}

@media (min-width: 768px) {
	.leftPane {
		height: 100vh;
	}
}

.widgetContentsContainer {
	margin-left: 10px;
	margin-right: 10px;
	margin-bottom: 10px;
}

</style>
<script>

var affixManagement = function() {
	if($(window).width() > 768) {
		$(".leftPane").css("height", ($(window).height() - 50) + "px");	
	}
	
	$('.leftPane').removeData('affix').removeClass('affix affix-top affix-bottom');
	$('body').scrollspy({ target: '#tablist', offset: navbarHeight + 5 })
	if ($("body").first().innerHeight() > $(".leftPane").height() + 180) {
		$('.leftPane').affix({
			offset: {
				top: navbarHeight + 20,
				bottom: 50
			}
		});
	}
};

$(window).load(function() {
	scrollManage();
	affixManagement();
});


$(window).resize(function() {
	scrollManage();
	affixManagement();
});


$(function() {
  $('a[href*=#]:not([href=#])').click(function() {
    if (location.pathname.replace(/^\//,'') == this.pathname.replace(/^\//,'') 
&& location.hostname == this.hostname) {

      var target = $(this.hash);
      target = target.length ? target : $('[name=' + this.hash.slice(1) +']');
      if (target.length) {
        $('html,body').animate({
          scrollTop: target.offset().top - navbarHeight //offsets for fixed header
        }, 300);
        return false;
      }
    }
  });
  //Executed on page load with URL containing an anchor tag.
  if($(location.href.split("#")[1])) {
      var target = $('#'+location.href.split("#")[1]);
      if (target.length) {
        $('html,body').animate({
          scrollTop: target.offset().top - navbarHeight //offset height of header here too.
        }, 300);
        return false;
      }
    }
});

</script>

<div class="row theme-<?=$template->getTemplateColor()?>">

	<div class="col-sm-3"> <!-- required for floating -->
		<div  class="floatTabList leftPane" >
		<!-- Nav tabs -->
		<div class="row">
			<div class="col-sm-12 miniPreview">
			</div>
		</div>
		<div class="row">
			<div class="col-sm-12">
				<button type="submit" class="btn btn-sm btn-primary saveButton">Save</button>
				<button type="button" class="btn btn-sm btn-info viewAsset">View</button>
			</div>
		</div>
		<div class="row">
			<div class="col-sm-12" id="tablist">
				<ul class="nav nav-tabs tabs-left hidden-xs" role="tablist">
					<li class="active"><a href="#general" data-toggle="tab">General</a></li>
					<? foreach($widgetList as $widgetTitle=>$widgetLabel):?>
					<li><a href="#<?=$widgetTitle?>" ><?=$widgetLabel?> <span class="glyphicon glyphicon-ok haveContent"></span><span class="glyphicon glyphicon-exclamation-sign requiredContent"></span></a></li>
					<?endforeach?>
				</ul>
			</div>
		</div>
		</div>
	</div>

	<div class="col-sm-9 rightPane">
		<div class="" >
			<div class="tab-pane" id="general">
				<div class="control-group">
				<div class="panel widgetContentsContainer">
					<div class="panel-body widgetContents">
						<div class="form-group">
							<label for="inputObjectId" class="col-sm-2 control-label">Object Id:</label>
							<div class="col-sm-3">
								<input type="text" name="objectId" displayed id="inputObjectId" class="form-control" disabled value="<?=$objectId?>" >
							</div>

						</div>
						<div class="form-group">
							<label for="templateId" class="col-sm-2 control-label">Template:</label>
							<div class="col-sm-3">
								<input type="hidden" name="templateId" value="<?=$template->getId()?>" id="sourceTemplate">
								<select name="newTemplateId" id="inputNewTemplateId" class="form-control input-large">
									<option>---</option>
									<? foreach($this->instance->getTemplates() as $internalTemplate):?>
									<option <?=($internalTemplate->getId()==$template->getId())?"SELECTED":null?> value="<?=$internalTemplate->getId()?>"><?=$internalTemplate->getName()?></option>
									<?endforeach?>
								</select>
							</div>
						</div>

						<div class="form-group">
							<label for="inputCollectionId" class="col-sm-2 control-label">Collection:</label>
							<div class="col-sm-3">
							<input type="hidden" name="collectionId" value="<?=$collectionId?$collectionId:-1?>" id="collectionId">
								<select name="newCollectionId" id="newCollectionId" class="form-control input-large">
									<option val=-1>---</option>
									<?=$this->load->view("collection_select_partial", ["selectCollection"=>$collectionId, "collections"=>$this->instance->getCollectionsWithoutParent(), "allowedCollections"=>$allowedCollections],true);?>

								</select>
							</div>
						</div>
						<div class="form-group">
							<div class="col-sm-3 col-sm-offset-2">
								<div class="checkbox">
									<label>
										<input value="on" name="readyForDisplay" <?=$readyForDisplay?> type="checkbox">
										Ready For Display
									</label>
								</div>
							</div>
						</div>
						<div class="form-group">
							<label for="inputAvailableAfter" class="col-sm-2 control-label">Available After:</label>
							<div class="col-sm-3">
								<input  name="availableAfter" id="inputAvailableAfter" class="form-control" value="<?=$availableAfter?>">
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>




