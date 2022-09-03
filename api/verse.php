<?php
include_once ("api.php");

$surahNumber = !isset($_GET['surah_number']) ? 0 : intval ($_GET['surah_number']);
$ayaNumber = !isset($_GET['aya_number']) ? 0 : intval ($_GET['aya_number']);
$translationId = !isset($_GET['translation']) ? pick_default_translation_id () : intval ($_GET['translation']);

$error=false;

if (!$error) {

	if (!does_this_translation_exist ($translationId)) {
		$error=true;
		$reason = "Incorrect id provided for the translation.";
	}

	if (!does_surah_exist ($surahNumber)) {
		$error=true;
		$reason = "Invalid surah number provided.";
	}

	if (!does_aya_exist ($surahNumber, $ayaNumber)) {
		$error=true;
		$reason = "Invalid aya number provided.";
	}	
}

if ($error) {
	$json = '{ "error": "'.$reason.'" }';
} else {

	$query = "SELECT q.text, t.translation 
			  FROM `tbl_quran` AS q, `tbl_formatted_translation` AS t
			  WHERE q.`surah_number` = t.`surah_number` AND q.`aya_number` = t.`aya_number` AND q.`surah_number` = $surahNumber AND q.`aya_number`= $ayaNumber AND t.`translation_id` = $translationId";
			  
	list ($arabicText, $englishText) = r (get_properties ($query));

	$json = '{	
	"surah_number": '.$surahNumber.',						
	"aya_number": '.$ayaNumber.',						
	"arabic_text": "'.$arabicText.'",						
	"english_text": "'.$englishText.'"
}';
}

echo $json;
?>