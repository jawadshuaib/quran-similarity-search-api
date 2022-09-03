<?php
include_once ("api.php");

$surahNumber = !isset($_GET['surah_number']) ? 0 : intval ($_GET['surah_number']);

$error=false;

if (!$error) {
	if (!does_surah_exist ($surahNumber)) {
		$error=true;
		$reason = "Invalid surah number provided.";
	}
}

if ($error) {
	$json = '{ "error": "'.$reason.'" }';
} else {

	$query = "SELECT * 
			  FROM `tbl_surah_info` 
			  WHERE `surah_number` = $surahNumber";

	list (,,$ayatCount,$arabicName, $englishName, $type) = r (get_properties ("SELECT * FROM `tbl_surah_info` WHERE `surah_number` = $surahNumber"));

	$json = '{	
	"surah_number": '.$surahNumber.',						
	"ayat_count": '.$ayatCount.',						
	"arabic_name": "'.$arabicName.'",						
	"english_name": "'.$englishName.'",						
	"type": "'.$type.'"
}';
}

echo $json;
?>