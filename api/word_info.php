<?php
/* 
Return a list of words linked to the searched word
{ "results": [
	{
			"lemma": "الله",
			"connected_words": "بِاللَّهِ,اللَّهُ,وَلِلَّهِ,فَاللَّهُ,وَاللَّهُ,فَلِلَّهِ,وَاللَّهِ,اللَّهَ,لِلَّهِ,اللَّهِ"
	}]
}

Sample request: 
http://localhost/quran/api/word_info.php?search=الله
*/

include_once ("api.php");


// Turn cache on or off
// This is useful for testing purposes
$cacheOn = false;

$search = isset ($_GET['search']) ? make_safe($_GET['search']) : NULL;
$keyword = $search;

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
// $inst = 'word_info.php?search=الله
// $cache->deleteItem($inst);

if ($cacheOn) {
	// Get instance of the cache
	$cachedInstance = $cache->getItem('word_info.php?search='.$search);
	$isCached = !(is_null ($cachedInstance->get ()));	
} else {
	$isCached = false;
}


// Fetch from database if not cached
if (!$isCached) {
	$error=false;
	if (!$error) {
		if (strlen ($keyword) === 0) {
			$error = true;
			$reason = "There is no keyword provided for search.";
		}
		
		$searchLemma = false;
		$searchQuranicWord = false;

		if (does_lemma_exist ($keyword)) {			
			$searchLemma = true;
		}
		elseif (has_lemma_for_quranic_word ($keyword)) {
			$searchQuranicWord = true;
		}

		if (($searchLemma === false) && ($searchQuranicWord === false)) {
			$error = true;
			$reason = "The keyword provided was not found in the Qur'an.";
		}
	}

	if ($error) {
		$json = '{ "error": "'.$reason.'" }';
	} else {
				
		if ($searchLemma) {
			$lemmatizedWord = $keyword;
			$quranicWords = get_quranic_words_for_lemma ($keyword);
			$quranicWords = implode (",", $quranicWords);		
		}
		elseif ($searchQuranicWord) {
			$lemmatizedWord = get_lemma_for_quranic_word ($keyword);
			// get a list of quranic words for lemma
			$quranicWords = get_quranic_words_for_lemma ($lemmatizedWord);
			$quranicWords = implode (",", $quranicWords);					
		}

		// $json = '{ "results": [';
		// $json .= '
		// {
		// 		"lemma": "'.$lemmatizedWord.'",
		// 		"connected_words": "'.$quranicWords.'"
		// }';
		// $json .= ']}';
		
		$json = '{';
		$json .= 
'"lemma": "'.$lemmatizedWord.'",
"connected_words": "'.$quranicWords.'"';
		$json .= '}';		

		// $json = json_encode ($json);

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
?>