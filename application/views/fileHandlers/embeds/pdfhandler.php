<?

$targetFile = null;
if (isset($fileContainers['shrunk_pdf'])) {
	$targetFile = $fileContainers['shrunk_pdf']->getProtectedURLForFile();
} else if (isset($fileContainers['ocr_pdf'])) {
	$targetFile = $fileContainers['ocr_pdf']->getProtectedURLForFile();
} else if (isset($fileContainers['pdf'])) {
	$targetFile = $fileContainers['pdf']->getProtectedURLForFile();
} else if ($allowOriginal) {
	$targetFile = $fileObject->sourceFile->getProtectedURLForFile();
}

$iconSrc = isset($fileContainers['thumbnail2x'])
	? stripHTTP($fileContainers['thumbnail2x']->getProtectedURLForFile())
	: getIconPath() . "pdf.png"
?>

<? if ($targetFile) : ?>
	<iframe class="vrview" frameborder=0 width="100%" height="100%" scrolling="no" allowfullscreen src="/assets/pdf/web/viewer.html?file=<?= urlencode(striphttp($targetFile)) ?>#zoom=page-fit&page=0"></iframe>
<? else : ?>
	<img src="<?= $iconSrc ?>" class="img-responsive embedImage" style="width: 50%; margin-left:auto; margin-right:auto" />
<? endif ?>