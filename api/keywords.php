<?php
/* 
Sample request: 
http://localhost/quran/api/keywords.php?search=حمد,الله
*/

include_once ("api.php");

// Turn cache on or off
// This is useful for testing purposes
$cacheOn = true;

define("KEYWORDS_SEARCH_METHOD", "text_arabic_lemmatized");

$search = isset ($_GET['search']) ? make_safe($_GET['search']) : NULL;
$keywords = explode (",", $search);

// Cache adapter for phpFastCache
// Usaged: https://grohsfabian.com/how-to-use-php-caching-with-mysql-queries-to-improve-performance/
$cacheConfig = new \Phpfastcache\Drivers\Files\Config([
    'path' => realpath(__DIR__) . '/cache',
    'securityKey' => CACHE_SECURITY_KEY_KEYWORDS,
    'preventCacheSlams' => true,
    'cacheSlamsTimeout' => 20,
    'secureFileManipulation' => true
]);
\Phpfastcache\CacheManager::setDefaultConfig($cacheConfig);
$cache = \Phpfastcache\CacheManager::getInstance('Files');


// Delete cache
// $inst = 'keywords.php?search=حمد,الله
// $cache->deleteItem($inst);

if ($cacheOn) {
	// Get instance of the cache
	$cachedInstance = $cache->getItem('keywords.php?search='.$search);
	$isCached = !(is_null ($cachedInstance->get ()));	
} else {
	$isCached = false;
}

// Fetch from database if not cached
if (!$isCached) {
	$error=false;
	if (!$error) {
		if (count ($keywords) === 0) {
			$error = true;
			$reason = "There are no keywords provided for search.";
		}
	}

	if ($error) {
		$json = '{ "error": "'.$reason.'" }';
	} else {
				
		$query = "SELECT q.surah_number, q.aya_number, q.text, q.text_minimal, q.text_arabic_lemmatized, q.text_arabic_lemmatized_without_stop_words, t.translation 
				  FROM `tbl_quran` AS q, `tbl_formatted_translation` AS t
				  WHERE 
				  q.`surah_number` = t.`surah_number` AND 
				  q.`aya_number` = t.`aya_number` AND "
				  .construct_sql ($keywords);
				
		$i=0;				
		$q = $db->query ($query);	
		$results = [];
		while ($theQ = mysqli_fetch_array ($q)) {
			$surahNumber = $theQ['surah_number'];
			$ayaNumber = $theQ['surah_number'];
			$surahNumber = $theQ['aya_number'];
			$quranicText = $theQ['text'];
			$minimal = $theQ['text_minimal'];
			$arabicLemmatized = $theQ['text_arabic_lemmatized'];
			$arabicLemmatizedWithoutStopWords = $theQ['text_arabic_lemmatized_without_stop_words'];
			$translation = $theQ['translation'];

			$result = '
				{	
					"surah_number": '.$surahNumber.',						
					"aya_number": '.$ayaNumber.',						
					"quranic_text": "'.$quranicText.'",						
					"minimal": "'.$minimal.'",
					"arabic_lemmatized": "'.$arabicLemmatized.'",
					"arabic_lemmatized_without_stop_words": "'.$arabicLemmatizedWithoutStopWords.'",		
					"translation": "'.$translation.'"
				}';

			$results[$i] = $result;
		$i++;
		}		

		$totalResults = count ($results);
		$json = '{ "results": [';
		for ($i=0;$i<$totalResults;$i++) {
			$json .= $results[$i];
			if ($i<($totalResults-1)) {
				$json .= ",";
			}
		}
		$json .= ']}';

	    // Save the json data to the cache 
		if ($cacheOn) {
			$cache->save(
		        $cachedInstance->set($json)->expiresAfter(CACHE_EXPIRY)
		    );	
		}
	    
	}	
} 
// Fetch from cache
else {
	$json = $cachedInstance->get();
}

echo $json;

function construct_sql ($keywords) {	
	$sql = "(";
	$totalKeywords = count ($keywords);	
	for ($i=0;$i<$totalKeywords;$i++) {
		if (trim ($keywords[$i]) != '') {
			$keyword = make_safe (trim ($keywords[$i]));
			$sql .= "q.`".KEYWORDS_SEARCH_METHOD."` LIKE '%".$keyword."%'";
			if ($i < ($totalKeywords - 1)) {
				$sql .= " AND ";
			}
		}
	}
	$sql .= ")";

	return $sql;
}
?>