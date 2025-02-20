<?php
/* 
Sample request: 
http://localhost/quran/api/keywords.php?search=حمد,الله
*/

include_once ("api.php");
// Turn cache on or off
// This is useful for testing purposes

define("KEYWORDS_SEARCH_METHOD", "text_arabic_lemmatized");

$search = isset ($_GET['search']) ? make_safe($_GET['search']) : NULL;
$keywords = explode (",", $search);

$cacheOn = true;
// Turn cache on for individual keywords only
// if (count ($keywords) == 1) {
// 	$cacheOn = true;
// }

// $method1 = 'text';
// $method2 = 'text_arabic_lemmatized';


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

		// Get a list of Quranic words that match the keywords here so we don't
		// have to keep looping over them.
		$collection = [];
		for ($x=0; $x<count ($keywords);$x++) {
			$keyword = $keywords[$x];

			list ($lemmaWords, $quranicWords) = get_quranic_words_for_keyword ($keyword);
			$collection[$keyword] = array ($quranicWords, $lemmaWords);			
		}				

		$query = "SELECT q.surah_number, q.aya_number, q.text, q.text_minimal, q.text_arabic_lemmatized, q.text_arabic_lemmatized_without_stop_words, t.translation 
				  FROM `tbl_quran` AS q, `tbl_formatted_translation` AS t
				  WHERE 
				  q.`surah_number` = t.`surah_number` AND 
				  q.`aya_number` = t.`aya_number` AND "
				  .construct_sql3 ($keywords, $collection);	  
				  //.construct_sql2 ($keywords, $collection);
				  //.construct_sql ($keywords, $method1)." OR "
				  //.construct_sql ($keywords, $method2)						  	 

		// echo $query; exit;

		$i=0;				
		$q = $db->query ($query);	
		$results = [];

		while ($theQ = mysqli_fetch_array ($q)) {
			$surahNumber = $theQ['surah_number'];
			$ayaNumber = $theQ['aya_number'];			
			$quranicText = $theQ['text'];
			$minimal = $theQ['text_minimal'];
			$arabicLemmatized = $theQ['text_arabic_lemmatized'];
			$arabicLemmatizedWithoutStopWords = $theQ['text_arabic_lemmatized_without_stop_words'];
			$translation = $theQ['translation'];

			// Get Quranic words that matched for this keyword
			$matches = '';		
			for ($x=0; $x<count ($keywords);$x++) {
				$quranicWords = $collection[$keywords[$x]][0];			
				$arr = get_keywords_in_this_verse ($surahNumber, $ayaNumber, $quranicWords);				
				
				if (is_array ($arr))					
					$matches .= implode (",", $arr).",";				
			}
			
			// Remove duplicates			
			$matches = array_unique(explode (",", $matches));
			$matches = implode (",", $matches);
			// Clean up any extra commas
			$matches = trim($matches, ',');

			$result = '
				{	
					"surah_number": '.$surahNumber.',						
					"aya_number": '.$ayaNumber.',						
					"quranic_text": "'.$quranicText.'",						
					"minimal": "'.$minimal.'",
					"arabic_lemmatized": "'.$arabicLemmatized.'",
					"arabic_lemmatized_without_stop_words": "'.$arabicLemmatizedWithoutStopWords.'",		
					"translation": "'.$translation.'",
					"matches": "'.$matches.'"
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

		if ($totalResults > 0) {
		    // Save the json data to the cache 
			if ($cacheOn) {
				$cache->save(
			        $cachedInstance->set($json)->expiresAfter(CACHE_EXPIRY)
			    );	
			}
		}	    
	}	
} 
// Fetch from cache
else {
	$json = $cachedInstance->get();
}

echo $json;

function get_keywords_in_this_verse ($surahNumber, $ayaNumber, $quranicWords) {
		
	if (!is_array ($quranicWords))
		return [];

	$words = array ();	
	$totalQuranicWords = count ($quranicWords);
	// In this Surah, find all the string matches for the keyword
	for ($i=0;$i<$totalQuranicWords;$i++) {

		$quranicWord = $quranicWords[$i];
		if ((!in_array ($quranicWord, $words)) && (is_word_in_verse ($quranicWord, $surahNumber, $ayaNumber))) {
			$words[] = $quranicWord;
		}
	}

	return $words;
}

function is_word_in_verse ($word, $surahNumber, $ayaNumber) {

	$query = "SELECT * FROM `tbl_quran` WHERE `surah_number` = $surahNumber AND `aya_number` = $ayaNumber AND MATCH (`text`) AGAINST ('$word')";

	return count_rows ($query) > 0 ? true : false;
}

function get_quranic_words_for_keyword ($keyword) {
	$quranicWords = [];
	$lemmaWords = [];
	if (does_lemma_exist ($keyword)) {
		// This keyword is a lemma, get Quranic words for it
		$lemmaWords[] = $keyword;
		$quranicWords = get_quranic_words_for_lemma ($keyword);										
	}
	elseif (has_lemma_for_quranic_word ($keyword)) {
		// This keyword is a Quranic word, get lemma for it
		$lemmatizedWord = get_lemma_for_quranic_word ($keyword);
		$lemmaWords[] = $lemmatizedWord;
		// Use the lemma to get a list of all connected Quranic words
		$quranicWords = get_quranic_words_for_lemma ($lemmatizedWord);				
	}	

	return [$lemmaWords, $quranicWords];
}


function construct_sql3 ($keywords, $collection) {	
	
	$sql = "(";
	$totalKeywords = count ($keywords);		
	for ($i=0;$i<$totalKeywords;$i++) {		

		if (trim ($keywords[$i]) != '') {
			// These are the lemma words.
			// We need the lemma words since we want to only match the text_arabic_lemmatized.			
			$keyword = $collection[$keywords[$i]][1][0];			
			$keyword = make_safe (trim ($keyword));
								
			$sql .= "(";
			$sql .= "q.`text` LIKE '%".$keyword."%'";			
			$sql .= " OR ";
			$sql .= "q.`text_arabic_lemmatized` LIKE '%".$keyword."%'";
			$sql .= ")";

			if ($i < ($totalKeywords - 1)) {
				$sql .= " AND ";
			}
		}
	}	
	$sql .= ")";	

	return $sql == "()" ? "1=2" : $sql;
}

function construct_sql2 ($keywords, $collection) {	
	
	$sql = "(";
	$totalKeywords = count ($keywords);		
	for ($i=0;$i<$totalKeywords;$i++) {		

		if (trim ($keywords[$i]) != '') {

			$keyword = make_safe (trim ($keywords[$i]));

			$quranicWords = $collection[$keyword]; 
			
			$totalQuranicWords = count ($quranicWords);
			$sql .= "(";
			for ($m = 0; $m < $totalQuranicWords; $m++) {
				$sql .= "q.`text` LIKE '%".$quranicWords[$m]."%'";

				if ($m < ($totalQuranicWords - 1)) {
					$sql .= " OR ";
				}

			}			
			$sql .= ")";
			
			// If query is empty then use the following hack to get rid of empty parentheses
			if ($sql == "(()") {				
				$sql = str_replace ("()","",$sql);
				continue;
			}
			

			if ($i < ($totalKeywords - 1)) {
				$sql .= " AND ";
			}
		}
	}	
	$sql .= ")";	

	return $sql == "()" ? "1=2" : $sql;
}

function construct_sql ($keywords, $method) {	
	$sql = "(";
	$totalKeywords = count ($keywords);	
	for ($i=0;$i<$totalKeywords;$i++) {
		if (trim ($keywords[$i]) != '') {
			$keyword = make_safe (trim ($keywords[$i]));
			$sql .= "q.`".$method."` LIKE '%".$keyword."%'";
			if ($i < ($totalKeywords - 1)) {
				$sql .= " AND ";
			}
		}
	}
	$sql .= ")";

	return $sql;
}
?>