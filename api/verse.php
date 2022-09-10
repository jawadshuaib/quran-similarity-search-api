<?php
/* 
Sample request: 
https://quran-similarity-score.000webhostapp.com/api/verse.php?translation=456&surah_number=10&aya_number=49
http://localhost/quran/api/verse.php?translation=456&surah_number=10&aya_number=49
*/
include_once ("api.php");

$surahNumber = !isset($_GET['surah_number']) ? 0 : intval ($_GET['surah_number']);
$ayaNumber = !isset($_GET['aya_number']) ? 0 : intval ($_GET['aya_number']);
$translationId = !isset($_GET['translation']) ? pick_default_translation_id () : intval ($_GET['translation']);

$tid = is_translation_id_arabic_simple ($translationId) === true ? pick_default_translation_id () : $translationId;

// Cache adapter for phpFastCache
// Usaged: https://grohsfabian.com/how-to-use-php-caching-with-mysql-queries-to-improve-performance/
$cacheConfig = new \Phpfastcache\Drivers\Files\Config([
    'path' => realpath(__DIR__) . '/cache',
    'securityKey' => CACHE_SECURITY_KEY_VERSE,
    'preventCacheSlams' => true,
    'cacheSlamsTimeout' => 20,
    'secureFileManipulation' => true
]);
\Phpfastcache\CacheManager::setDefaultConfig($cacheConfig);
$cache = \Phpfastcache\CacheManager::getInstance('Files');


// Delete cache
// $inst = 'verse.php?translation='.$translationId.'&surah_number='.$surahNumber.'&aya_number='.$ayaNumber;
// $cache->deleteItem($inst);


// Get instance of the cache
$cachedInstance = $cache->getItem('verse.php?translation='.$translationId.'&surah_number='.$surahNumber.'&aya_number='.$ayaNumber);
$isCached = !(is_null ($cachedInstance->get ()));

// Fetch from database if not cached
if (!$isCached) {
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
				  WHERE q.`surah_number` = t.`surah_number` AND q.`aya_number` = t.`aya_number` AND q.`surah_number` = $surahNumber AND q.`aya_number`= $ayaNumber AND t.`translation_id` = $tid";
				  
		list ($arabicText, $englishText) = r (get_properties ($query));

		$json = '{	
		"surah_number": '.$surahNumber.',						
		"aya_number": '.$ayaNumber.',						
		"arabic_text": "'.$arabicText.'",						
		"english_text": "'.$englishText.'"
	}';

	    /* Save the json data to the cache */
	    $cache->save(
	        $cachedInstance->set($json)->expiresAfter(CACHE_EXPIRY)
	    );	
	}	
} 
// Fetch from cache
else {
	$json = $cachedInstance->get();
}

echo $json;
?>