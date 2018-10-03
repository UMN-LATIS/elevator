User-Agent: *
<?if(!$allowRoot):?>
Disallow: /search
Disallow: /asset

<?endif?>
<?foreach($paths as $path=>$allowIndexing):?>
<?if(!$allowIndexing):?>
Disallow: /<?=$path?>

<?endif?>
<?endforeach?>

