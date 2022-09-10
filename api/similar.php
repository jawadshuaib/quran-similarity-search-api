<?php
/* 
Sample request: 
https://quran-similarity-score.000webhostapp.com/api/similar.php?translation=456&surah_number=10&aya_number=49&results=5&method=without_stop_words
http://localhost/quran/api/similar.php?translation=456&surah_number=10&aya_number=49&results=5&method=without_stop_words
*/
include_once ("api.php");

define("DEFAULT_NUMBER_OF_RESULTS", 5);
define("MAX_RESULTS_ALLOWED", 50);
define("MIN_RESULTS_ALLOWED", DEFAULT_NUMBER_OF_RESULTS);

// Turn cache on or off
// This is useful for testing purposes
$cacheOn = true;

$surahNumber = !isset($_GET['surah_number']) ? 0 : intval ($_GET['surah_number']);
$ayaNumber = !isset($_GET['aya_number']) ? 0 : intval($_GET['aya_number']);
$results = !isset($_GET['results']) ? pick_default_number_of_results () : intval ($_GET['results']);
$translationId = !isset($_GET['translation']) ? pick_default_translation_id () : intval ($_GET['translation']);
$method = !isset($_GET['method']) ? DEFAULT_SIMILARITY_METHOD : pick_method ($_GET['method']);
$cutOff = get_cut_off ($method);

// If Arabic method is requested, then resort to the default English translation for it
$tid = is_translation_id_arabic_simple ($translationId) === true ? pick_default_translation_id () : $translationId;

// Cache adapter for phpFastCache
// Usaged: https://grohsfabian.com/how-to-use-php-caching-with-mysql-queries-to-improve-performance/
// Get the cache key for Arabic. It is a good idea to keep the english and arabic methods separate
// for performance purposes
$cacheKey = is_translation_id_arabic_simple ($translationId) === true ? CACHE_SECURITY_KEY_SIMILAR_ARABIC : CACHE_SECURITY_KEY_SIMILAR;
$cacheConfig = new \Phpfastcache\Drivers\Files\Config([
    'path' => realpath(__DIR__) . '/cache',
    'securityKey' => $cacheKey,
    'preventCacheSlams' => true,
    'cacheSlamsTimeout' => 20,
    'secureFileManipulation' => true
]);
\Phpfastcache\CacheManager::setDefaultConfig($cacheConfig);
$cache = \Phpfastcache\CacheManager::getInstance('Files');


// $inst = 'similar.php?translation='.$translationId.'&surah_number='.$surahNumber.'&aya_number='.$ayaNumber.'&method='.$method;
// $cache->deleteItem($inst);
// exit;

if ($cacheOn) {	
	// Get instance of the cache
	$cachedInstance = $cache->getItem('similar.php?translation='.$translationId.'&surah_number='.$surahNumber.'&aya_number='.$ayaNumber."&results=".$results."&method=".$method);
	$isCached = !(is_null ($cachedInstance->get ()));
} else {
	$isCached = false;
}

// Fetch from database if not cached
if (!$isCached) {
	// echo "DATABASE";
	$error=false;
	if (!$error) {
		if (!does_this_translation_exist ($translationId)) {
			$error=true;
			$reason = "Incorrect id provided for the translation.";
		}
	}

	if (!$error) {
		if (!does_translation_for_this_method_exist ($translationId, $method)) {
			$error=true;
			$reason = "The method chosen for this translation does not exist. Please pick a different method.";
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
		
		$totalVersesAboveCutOff = total_verses_above_cut_off ($translationId, $surahNumber, $ayaNumber, $method, $cutOff);
		
		// If no similar verses were found above the cut off or if the total verses above cut off are too few, then just fetch 
		// the requested number of results as long as they do not exceed the maximum results allowed		
		if (($totalVersesAboveCutOff===0) || ($totalVersesAboveCutOff < MIN_RESULTS_ALLOWED)) {		
			$results = $results > MAX_RESULTS_ALLOWED ? MAX_RESULTS_ALLOWED : $results;	
		}
		// If we found plenty of similar verses above cut off, then results should pull all of them		
		// Again, we don't want too many results so we cut it off at a certain point
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
			list ($quranicText, $minimal, $arabicLemmatized, $arabicLemmatizedWithoutStopWords) = get_quranic_text ($similarSurahNumber, $similarAyaNumber);
			// Translation of Surah and Aya
			$translation = get_translation ($tid, $similarSurahNumber, $similarAyaNumber);

			$similar .= '
	{ 
		"surah_number": '.$similarSurahNumber.', 
		"aya_number": '.$similarAyaNumber.',
		"quranic_text": "'.$quranicText.'",		
		"minimal": "'.$minimal.'",
		"arabic_lemmatized": "'.$arabicLemmatized.'",
		"arabic_lemmatized_without_stop_words": "'.$arabicLemmatizedWithoutStopWords.'",
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
		list ($quranicText, $minimal, $arabicLemmatized, $arabicLemmatizedWithoutStopWords) = get_quranic_text ($surahNumber, $ayaNumber);		
		$translation = get_translation ($tid, $surahNumber, $ayaNumber);

		$json = '{
"info": {
	"name": "'.$translationName.'",						
	"source": "'.$translationSource.'",
	"method": "'.$method.'",
	"surah_number": "'.$surahNumber.'",
	"aya_number": "'.$ayaNumber.'",
	"quranic_text": "'.$quranicText.'",
	"minimal": "'.$minimal.'",
	"arabic_lemmatized": "'.$arabicLemmatized.'",
	"arabic_lemmatized_without_stop_words": "'.$arabicLemmatizedWithoutStopWords.'",
	"translation": "'.$translation.'"
},
"similar": [
	'.$similar.'
]
}';

		if ($cacheOn) {
		    // Save the json data to the cache
		    $cache->save(
		        $cachedInstance->set($json)->expiresAfter(CACHE_EXPIRY)
		    );				
		}

	}
}
// Fetch from the cache
else {				
    $json = $cachedInstance->get();
}

echo $json;


function pick_default_number_of_results () {
	return DEFAULT_NUMBER_OF_RESULTS;
}
?>