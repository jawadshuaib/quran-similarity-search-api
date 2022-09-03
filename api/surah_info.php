<?php
/*
Sample request:
https://quran-similarity-score.000webhostapp.com/api/surah_info.php?surah_number=2
http://localhost/quran/api/surah_info.php?surah_number=2
*/
include_once ("api.php");

$surahNumber = !isset($_GET['surah_number']) ? 0 : intval ($_GET['surah_number']);

// Cache adapter for phpFastCache
// Usaged: https://grohsfabian.com/how-to-use-php-caching-with-mysql-queries-to-improve-performance/
$cacheConfig = new \Phpfastcache\Drivers\Files\Config([
    'path' => realpath(__DIR__) . '/cache',
    'securityKey' => CACHE_SECURITY_KEY_SURAH_INFO,
    'preventCacheSlams' => true,
    'cacheSlamsTimeout' => 20,
    'secureFileManipulation' => true
]);
\Phpfastcache\CacheManager::setDefaultConfig($cacheConfig);
$cache = \Phpfastcache\CacheManager::getInstance('Files');

// Get instance of the cache
$cachedInstance = $cache->getItem('surah_info.php?surah_number='.$surahNumber);
$isCached = !(is_null ($cachedInstance->get ()));

// Fetch from the database
if (!$isCached) {
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

	    /* Save the json data to the cache */
	    $cache->save(
	        $cachedInstance->set($json)->expiresAfter(CACHE_EXPIRY)
	    );	
	}
}
// Fetch from the cache
else {
	$json = $cachedInstance->get();
}

echo $json;
?>