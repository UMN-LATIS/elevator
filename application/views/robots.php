User-Agent: *
<?if(!$allowRoot):?>
Disallow: /search
Disallow: /asset
Disallow: /loginManager

<?endif?>
<?foreach($paths as $path=>$allowIndexing):?>
<?if(!$allowIndexing):?>
Disallow: /<?=$path?>

<?endif?>
<?endforeach?>

