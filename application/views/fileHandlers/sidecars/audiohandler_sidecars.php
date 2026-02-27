<div class="form-group">
	<label for="<?=$formFieldRoot?>_captions" class="col-sm-3 control-label">Captions</label>
	<div class="col-sm-5">
		<textarea class="form-control" id="<?=$formFieldRoot?>_captions" name="<?=$formFieldRoot?>[captions]" placeholder="Captions (WebVTT or SRT)"><?=isset($sidecarData['captions'])?$sidecarData['captions']:null?></textarea>
	</div>
	<div class="col-sm-3">
		<label for="<?=$formFieldRoot?>_upload" class="btn btn-default">Load from File</label>
		<input id="<?=$formFieldRoot?>_upload" type="file" class="importTextForSidecar" style="display: none;" data-target="<?=$formFieldRoot?>[captions]" />
	</div>
</div>

<div class="form-group">
	<label for="<?=$formFieldRoot?>_chapters" class="col-sm-3 control-label">Chapter Markers</label>
	<div class="col-sm-5">
		<textarea class="form-control" id="<?=$formFieldRoot?>_captions"name="<?=$formFieldRoot?>[chapters]" placeholder="Chapter Markers (WebVTT)"><?=isset($sidecarData['chapters'])?$sidecarData['chapters']:null?></textarea>
	</div>
</div>

<?php

$iso_639_1 = [
    "aa" => "Afar","ab" => "Abkhaz","ae" => "Avestan","af" => "Afrikaans","ak" => "Akan","am" => "Amharic","an" => "Aragonese","ar" => "Arabic","as" => "Assamese","av" => "Avaric","ay" => "Aymara","az" => "Azerbaijani","ba" => "Bashkir","be" => "Belarusian","bg" => "Bulgarian","bh" => "Bihari languages","bi" => "Bislama","bm" => "Bambara","bn" => "Bengali","bo" => "Tibetan","br" => "Breton","bs" => "Bosnian","ca" => "Catalan","ce" => "Chechen","ch" => "Chamorro","co" => "Corsican","cr" => "Cree","cs" => "Czech","cu" => "Church Slavic","cv" => "Chuvash","cy" => "Welsh","da" => "Danish","de" => "German","dv" => "Divehi","dz" => "Dzongkha","ee" => "Ewe","el" => "Greek","en" => "English","eo" => "Esperanto","es" => "Spanish","et" => "Estonian","eu" => "Basque","fa" => "Persian","ff" => "Fulah","fi" => "Finnish","fj" => "Fijian","fo" => "Faroese","fr" => "French","fy" => "Western Frisian","ga" => "Irish","gd" => "Scottish Gaelic","gl" => "Galician","gn" => "Guarani","gu" => "Gujarati","gv" => "Manx","ha" => "Hausa","he" => "Hebrew","hi" => "Hindi","ho" => "Hiri Motu","hr" => "Croatian","ht" => "Haitian Creole","hu" => "Hungarian","hy" => "Armenian","hz" => "Herero","ia" => "Interlingua","id" => "Indonesian","ie" => "Interlingue","ig" => "Igbo","ii" => "Sichuan Yi","ik" => "Inupiaq","io" => "Ido","is" => "Icelandic","it" => "Italian","iu" => "Inuktitut","ja" => "Japanese","jv" => "Javanese","ka" => "Georgian","kg" => "Kongo","ki" => "Kikuyu","kj" => "Kwanyama","kk" => "Kazakh","kl" => "Kalaallisut","km" => "Khmer","kn" => "Kannada","ko" => "Korean","kr" => "Kanuri","ks" => "Kashmiri","ku" => "Kurdish","kv" => "Komi","kw" => "Cornish","ky" => "Kyrgyz","la" => "Latin","lb" => "Luxembourgish","lg" => "Ganda","li" => "Limburgish","ln" => "Lingala","lo" => "Lao","lt" => "Lithuanian","lu" => "Luba-Katanga","lv" => "Latvian","mg" => "Malagasy","mh" => "Marshallese","mi" => "Māori","mk" => "Macedonian","ml" => "Malayalam","mn" => "Mongolian","mr" => "Marathi","ms" => "Malay","mt" => "Maltese","my" => "Burmese","na" => "Nauru","nb" => "Norwegian Bokmål","nd" => "North Ndebele","ne" => "Nepali","ng" => "Ndonga","nl" => "Dutch","nn" => "Norwegian Nynorsk","no" => "Norwegian","nr" => "South Ndebele","nv" => "Navajo","ny" => "Chichewa","oc" => "Occitan","oj" => "Ojibwe","om" => "Oromo","or" => "Oriya","os" => "Ossetian","pa" => "Punjabi","pi" => "Pali","pl" => "Polish","ps" => "Pashto","pt" => "Portuguese","qu" => "Quechua","rm" => "Romansh","rn" => "Kirundi","ro" => "Romanian","ru" => "Russian","rw" => "Kinyarwanda","sa" => "Sanskrit","sc" => "Sardinian","sd" => "Sindhi","se" => "Northern Sami","sg" => "Sango","si" => "Sinhala","sk" => "Slovak","sl" => "Slovene","sm" => "Samoan","sn" => "Shona","so" => "Somali","sq" => "Albanian","sr" => "Serbian","ss" => "Swati","st" => "Southern Sotho","su" => "Sundanese","sv" => "Swedish","sw" => "Swahili","ta" => "Tamil","te" => "Telugu","tg" => "Tajik","th" => "Thai","ti" => "Tigrinya","tk" => "Turkmen","tl" => "Tagalog","tn" => "Tswana","to" => "Tongan","tr" => "Turkish","ts" => "Tsonga","tt" => "Tatar","tw" => "Twi","ty" => "Tahitian","ug" => "Uyghur","uk" => "Ukrainian","ur" => "Urdu","uz" => "Uzbek","ve" => "Venda","vi" => "Vietnamese","vo" => "Volapük","wa" => "Walloon","wo" => "Wolof","xh" => "Xhosa","yi" => "Yiddish","yo" => "Yoruba","za" => "Zhuang","zh" => "Chinese","zu" => "Zulu"
];
?>

<div class="form-group">
	<label for="<?=$formFieldRoot?>_language" class="col-sm-3 control-label">Language</label>
	<div class="col-sm-5">
		<select class="form-control" id="<?=$formFieldRoot?>_language" name="<?=$formFieldRoot?>[language]">
			<option value="0">-- Select Language --</option>
			<?php
			foreach($iso_639_1 as $code => $name) {
				$selected = (isset($sidecarData['language']) && $sidecarData['language'] == $code) ? "selected" : "";
				echo "<option value=\"" . htmlspecialchars($code) . "\" " . $selected . ">" . htmlspecialchars($name) . "</option>\n";
			}
			?>
		</select>
	</div>
</div>