<?php
include_once ("api.php");

define ("DEFAULT_SIMILARITY_METHOD", "cosine_similarity_translation");
define("DEFAULT_NUMBER_OF_RESULTS", 5);
define("MAX_RESULTS_ALLOWED", 50);
define("MIN_RESULTS_ALLOWED", DEFAULT_NUMBER_OF_RESULTS);
define("CUT_OFF", 0.5);

$surahNumber = !isset($_GET['surah_number']) ? 0 : intval ($_GET['surah_number']);
$ayaNumber = !isset($_GET['aya_number']) ? 0 : intval($_GET['aya_number']);
$results = !isset($_GET['results']) ? pick_default_number_of_results () : intval ($_GET['results']);
$translationId = !isset($_GET['translation']) ? pick_default_translation_id () : intval ($_GET['translation']);
$method = pick_method (!isset($_GET['method']) ? NULL : $_GET['method']);

$error=false;
if (!$error) {
	if (!does_this_translation_exist ($translationId)) {
		$error=true;
		$reason = "Incorrect id provided for the translation.";
	}
}

if (!$error) {
	if (!does_surah_exist ($surahNumber)) {
		$error=true;
		$reason = "Invalid surah number provided.";
	}
}

if (!$error) {
	if (!does_aya_exist ($surahNumber, $ayaNumber)) {
		$error=true;
		$reason = "Invalid aya number provided.";
	}
}

if (!$error) {
	
	$totalVersesAboveCutOff = total_verses_above_cut_off ($translationId, $surahNumber, $ayaNumber, $method, CUT_OFF);
	
	// If no similar verses were found above the cut off or if the total verses above cut off are too few, then just fetch 
	// the requested number of results as long as they do not exceed the maximum results allowed		
	if (($totalVersesAboveCutOff===0) || ($totalVersesAboveCutOff < MIN_RESULTS_ALLOWED)) {		
		$results = $results > MAX_RESULTS_ALLOWED ? MAX_RESULTS_ALLOWED : $results;	
	}
	// If we found plenty of similar verses above cut off, then results should pull all of them
	// Again, we don't want to show too many results so keep them under the max allowed
	else {
		$results = $totalVersesAboveCutOff > MAX_RESULTS_ALLOWED ? MAX_RESULTS_ALLOWED : $totalVersesAboveCutOff;
	}

	$similar = '';
	$query = "SELECT `compare_to_surah_number`, `compare_to_aya_number`, `$method` AS `similarity` 
			  FROM `tbl_similarity_score` 
			  WHERE 
			  	`translation_id` = $translationId AND 
			  	`surah_number` = $surahNumber AND 
			  	`aya_number` = $ayaNumber AND
			  	`$method` > 0
			  ORDER BY `$method` DESC 
			  LIMIT $results";

	$q = $db->query ($query);	

	while ($theQ = mysqli_fetch_array ($q)) {
		
		$similarSurahNumber = $theQ['compare_to_surah_number'];
		$similarAyaNumber = $theQ['compare_to_aya_number'];

		// Skip if the comparison is with the same Surah and Aya
		if (($surahNumber == $similarSurahNumber) && ($ayaNumber == $similarAyaNumber)) {
			continue;
		}
	
		// Quranic verse
		$quranicText = get_quranic_text ($similarSurahNumber, $similarAyaNumber);
		// Translation of Surah and Aya
		$translation = get_translation ($translationId, $similarSurahNumber, $similarAyaNumber);

		$similar .= '{ 
			"surah_number": '.$similarSurahNumber.', 
			"aya_number": '.$similarAyaNumber.',
			"quranic_text": "'.$quranicText.'",
			"translation": "'.$translation.'",
			"similarity": '.$theQ['similarity'].'
		},
		'; 
	}	

	// Remove last comma
	$similar = substr (trim($similar), 0, -1);
}

if ($error) {
	$json = '{ "error": "'.$reason.'" }';
} else {

	// Information about the translation
	list ($translationName, $translationSource) = r (get_properties ("SELECT name, source FROM `tbl_translation_info` WHERE translation_id = $translationId"));

	// Quranic verse
	$quranicText = get_quranic_text ($surahNumber, $ayaNumber);
	// Translation of Surah and Aya
	$translation = get_translation ($translationId, $surahNumber, $ayaNumber);

	$json = '{
	"info": {
		"name": "'.$translationName.'",						
		"source": "'.$translationSource.'",
		"method": "'.$method.'",
		"surah_number": "'.$surahNumber.'",
		"aya_number": "'.$ayaNumber.'",
		"quranic_text": "'.$quranicText.'",						
		"translation": "'.$translation.'"
	},
	"similar": [
		'.$similar.'
	]
}';
}

echo $json;


function pick_default_number_of_results () {
	return DEFAULT_NUMBER_OF_RESULTS;
}

function pick_method ($method) {
	if ($method == "formatted")	
		return "cosine_similarity_translation_formatted";
	elseif ($method == "tokenized")
		return "cosine_similarity_translation_tokenized";
	elseif ($method == "without_stop_words")
		return "cosine_similarity_translation_without_stop_words";	
	else
		return DEFAULT_SIMILARITY_METHOD;
}
?>