<?php
/* 
Return a list of most closely related relatives of a lemma
{ "results": [
	{
			"keyword": "الله",
			"is_lemma": "true",
			"relatives": "بِاللَّهِ,اللَّهُ,وَلِلَّهِ,فَاللَّهُ,وَاللَّهُ,فَلِلَّهِ,وَاللَّهِ,اللَّهَ,لِلَّهِ,اللَّهِ"
	}]
}

Sample request: 
http://localhost/quran/api/lemma_relative.php?search=الله
*/

include_once ("api.php");

// Turn cache on or off
// This is useful for testing purposes
$cacheOn = true;

$search = isset ($_GET['search']) ? make_safe($_GET['search']) : NULL;
$keyword = $search;

// Cache adapter for phpFastCache
// Usaged: https://grohsfabian.com/how-to-use-php-caching-with-mysql-queries-to-improve-performance/
$cacheConfig = new \Phpfastcache\Drivers\Files\Config([
    'path' => realpath(__DIR__) . '/cache',
    'securityKey' => CACHE_SECURITY_LEMMA_RELATIVES_KEYWORDS,
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
	$cachedInstance = $cache->getItem('lemma_relative.php?search='.$search);
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
		
		$isKeywordLemma = false;
		$isKeywordQuranicWord = false;

		if (does_lemma_exist ($keyword)) {			
			$isKeywordLemma = true;			
		}
		elseif (has_lemma_for_quranic_word ($keyword)) {
			$isKeywordQuranicWord = true;
		}

		if (($isKeywordLemma === false) && ($isKeywordQuranicWord === false)) {
			$error = true;
			$reason = "The keyword provided was not found in the Qur'an.";
		}
	}

	if ($error) {
		$json = '{ "error": "'.$reason.'" }';
	} else {
				
		if ($isKeywordLemma) {
			$lemmatizedWord = $keyword;
			
			// Get relatives
			$relatives = get_lemma_relatives ($keyword);
			$relatives = implode (",", $relatives);		
		}
		elseif ($isKeywordQuranicWord) {
			$lemmatizedWord = get_lemma_for_quranic_word ($keyword);
			
			// Get relatives
			$relatives = get_lemma_relatives ($lemmatizedWord);
			$relatives = implode (",", $relatives);					
		}

		
		$json = '{';
		$json .= 
'"keyword": "'.$lemmatizedWord.'",
"is_lemma": '.($isKeywordLemma === true ? 'true' : 'false').',
"lemma_relatives": "'.$relatives.'"';
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


// We get lemma relatives, starting with strict limits for character matches
// to get the most relevant results. And if there are not enough results returned
// then we progressively lower the boundary of results
function get_lemma_relatives ($lemma1) {
global $db;

	define ('MAX_RESULTS', 3);

	$count = 0;	
	$lemmas = [];
	$limit1 = MAX_RESULTS;
	// First restriction: cosine_similarity > 0.9, initial_character_match = 3
	$options = array (
					'lemma_1' => $lemma1, 
					'cosine_similarity' => 0.6, 
					'initial_character_match' => 3, 
					'limit' => $limit1);	

	$lemmas[] = execute_sql ($options);


	// Second restriction: cosine_similarity > 0.9, initial_character_match = 2
	$limit2 = $limit1 - count ($lemmas);
	if (count ($lemmas) < $limit1) {		

		$options = array (
						'lemma_1' => $lemma1, 
						'cosine_similarity' => 0.6, 
						'initial_character_match' => 2, 
						'limit' => $limit2);

		$lemmas[] = execute_sql ($options);	
	}

	// Third restriction: cosine_similarity > 0.9, initial_character_match = 1
	$limit3 = $limit2 - count ($lemmas);
	if (count ($lemmas) < $limit2) {		

		$options = array (
						'lemma_1' => $lemma1, 
						'cosine_similarity' => 0.7, 
						'initial_character_match' => 1, 
						'limit' => $limit3);

		$lemmas[] = execute_sql ($options);	
	}

	// Fourth restriction: cosine_similarity > 0.7, initial_character_match = 0
	$limit4 = $limit3 - count ($lemmas);
	if (count ($lemmas) < $limit3) {		
		
		$options = array (
						'lemma_1' => $lemma1, 
						'cosine_similarity' => 0.8, 
						'initial_character_match' => 0, 
						'limit' => $limit4
					);

		$lemmas[] = execute_sql ($options);		
	}	

return array_merge (...$lemmas);
}

function execute_sql ($options) {
global $db;
	
	$lemmas = [];
	$query = "SELECT lemma_2 FROM `tbl_lemma_relatives` WHERE `lemma_1` = '".$options['lemma_1']."' AND `cosine_similarity` > ".$options['cosine_similarity']." AND initial_character_match = ".$options['initial_character_match']." ORDER BY cosine_similarity DESC LIMIT ".$options['limit'];


	$q = $db->query ($query);		
	while ($theQ = mysqli_fetch_array ($q)) {
		$lemmas[] = $theQ['lemma_2'];			
	}

return $lemmas;
}
?>