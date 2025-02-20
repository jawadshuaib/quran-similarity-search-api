<?php
define("DEFAULT_TRANSLATION_ID", 456);
define ("DEFAULT_SIMILARITY_METHOD", "cosine_similarity_translation");
define("DEFAULT_CUT_OFF", 0.5);
define("TRANSLATION_ID_ARABIC_SIMPLE", 789);

define("CACHE_SECURITY_KEY_SIMILAR", "similar_surahsofthekitaab");
define("CACHE_SECURITY_KEY_SIMILAR_ARABIC", "similar_arabic_surahsofthekitaab");
define("CACHE_SECURITY_KEY_VERSE", "verse_surahsofthekitaab");
define("CACHE_SECURITY_KEY_SURAH_INFO", "surah_info_surahsofthekitaab");
define("CACHE_SECURITY_KEY_KEYWORDS", "keywords_surahsofthekitaab");
define("CACHE_SECURITY_LEMMA_KEYWORDS", "lemma_surahsofthekitaab");
define("CACHE_SECURITY_LEMMA_RELATIVES_KEYWORDS", "lemma_relatives_surahsofthekitaab");
define("CACHE_SECURITY_GOOGLE_TRANSLATE", "google_translate");
define("CACHE_EXPIRY", 157788000); // Cache expires in 5 years

function does_lemma_exist ($lemma) {
	$query = "SELECT * FROM `tbl_translated_words` WHERE lemmatized_word = '$lemma'";	
	return count_rows ($query) > 0 ? true : false;
}

function get_quranic_words_for_lemma ($lemma) {
	$query = "SELECT quranic_word FROM `tbl_translated_words` WHERE lemmatized_word = '$lemma'";
	list ($quranicWords) = get_properties ($query);
	return $quranicWords;
}

function has_lemma_for_quranic_word ($quranicWord) {
	$query = "SELECT * FROM `tbl_translated_words` WHERE quranic_word = '$quranicWord'";
	return count_rows ($query) > 0 ? true : false;	
}

function get_lemma_for_quranic_word ($quranicWord) {
	$query = "SELECT lemmatized_word FROM `tbl_translated_words` WHERE quranic_word = '$quranicWord'";
	list ($lemmatizedWord) = r (get_properties ($query));
	return $lemmatizedWord;
}

function is_translation_id_arabic_simple ($translationId=0) {
	return $translationId === TRANSLATION_ID_ARABIC_SIMPLE ? true : false;
}

function total_verses_above_cut_off ($translationId=0, $surahNumber=0, $ayaNumber=0, $method, $cutOff=0) {
	$query = "SELECT * FROM `tbl_similarity_score` WHERE `translation_id` = $translationId AND `surah_number` = $surahNumber AND `aya_number` = $ayaNumber AND `$method` > $cutOff";
	return count_rows ($query);
}

function get_quranic_text ($surahNumber=0, $ayaNumber=0) {
	return list ($text, $textMinimal, $textArabicLemmatized, $textArabicLemmatizedWithoutStopWords) = r (get_properties ("SELECT `text`, `text_minimal`, `text_arabic_lemmatized`, `text_arabic_lemmatized_without_stop_words` FROM `tbl_quran` WHERE `surah_number` = $surahNumber AND `aya_number` = $ayaNumber"));		
}

function get_translation ($translationId=0, $surahNumber=0, $ayaNumber=0) {
	list ($translation) = r (get_properties ("SELECT `translation` FROM `tbl_formatted_translation` WHERE `translation_id` = $translationId AND `surah_number` = $surahNumber AND `aya_number` = $ayaNumber"));	
	return $translation;
}

function does_aya_exist ($surahNumber=0, $ayaNumber=0) {
	$query = "SELECT * FROM `tbl_formatted_translation` WHERE `surah_number` = $surahNumber AND `aya_number` = $ayaNumber";
	return count_rows ($query) > 0 ? true : false;
}

function does_surah_exist ($surahNumber=0) {
	$query = "SELECT * FROM `tbl_surah_info` WHERE `surah_number` = $surahNumber";
	return count_rows ($query) > 0 ? true : false;
}

// If user requests Arabic translation id, but cosine_similarity_translation_without_stop_words, then that would
// be an invalid request as this request does not have a corresponding cosine similarity for English translation.
// We want to make sure the translation id and method exist.
function does_translation_for_this_method_exist ($translationId=0, $method=DEFAULT_SIMILARITY_METHOD) {

	$query = "SELECT `$method` AS methodVal FROM `tbl_similarity_score` WHERE `translation_id` = $translationId LIMIT 1";		
	list ($methodVal) = r (get_properties ($query));
	
	return $methodVal != NULL ? true : false;
}

// We set a lower cut off for Arabic verses since root word comparison is more stingent.
function get_cut_off ($method) {
	if ($method == "cosine_similarity_arabic_lemmatized")
		return 0.3;	
	elseif ($method == "cosine_similarity_arabic_lemmatized_without_stop_words")
		return 0.1;	
	else
		return DEFAULT_CUT_OFF;
}

function pick_method ($method) {
	if ($method == "formatted")	
		return "cosine_similarity_translation_formatted";
	elseif ($method == "tokenized")
		return "cosine_similarity_translation_tokenized";
	elseif ($method == "without_stop_words")
		return "cosine_similarity_translation_without_stop_words";
	elseif ($method == "arabic_lemmatized")
		return "cosine_similarity_arabic_lemmatized";		
	elseif ($method == "arabic_lemmatized_without_stop_words")
		return "cosine_similarity_arabic_lemmatized_without_stop_words";			
	else
		return DEFAULT_SIMILARITY_METHOD;
}

function does_this_translation_exist ($translationId=0) {
	$query = "SELECT * FROM `tbl_translation_info` WHERE `translation_id` = $translationId";
	return count_rows ($query) > 0 ? true : false;
}

function pick_default_translation_id () {
	return DEFAULT_TRANSLATION_ID;
}

// returns the arrays as single elements
function return_single_array_objects ($arr) {
	$properties = NULL;
	$total = is_array ($arr) ? count ($arr) : 0;
	for ($i=0;$i<$total;$i++) {
		$properties[] = $arr[$i][0];
	}
return $properties;
}

function r ($arr) {
return return_single_array_objects ($arr);
}

// get the field names and values of any sql table
function get_properties ($query) {
global $db;

	$properties = NULL;
	$q     = $db->query ($query);

	$pos   = strpos($query, " * ");
	if ($pos === false) {		
		$totalResults= $q === false ? 0 : mysqli_num_rows ($q);
	} else {
		$totalResults=count_rows ($query);
	}

    if ($totalResults>0){
        while($row = mysqli_fetch_field($q))
        {
            $fieldNames[] = $row->name;
        }
        while($theQ = mysqli_fetch_array($q))
        {        	
            foreach ($fieldNames as $key=>$fieldName) {
                if (!is_numeric($theQ[$fieldName])) {
                    ${$fieldName}[] = htmlentities($theQ[$fieldName], ENT_QUOTES);                       
                } else {
                    ${$fieldName}[] = $theQ[$fieldName];                          
                }
            }           
        }
        
        for ($i=0;$i<count($fieldNames);$i++) {
            $properties[] = ${$fieldNames[$i]};
        }
    }

return $properties;
}

function make_safe ($str='') {
global $db;
	
	$str = xss_clean ($str);
	if (isset($db)) {		
		$str = mysqli_real_escape_string ($db, $str);		
	}
	else {
		$str = addslashes ($str);
	}
return $str;
}

function xss_clean($data)
{
// Fix &entity\n;
$data = str_replace(array('&amp;','&lt;','&gt;'), array('&amp;amp;','&amp;lt;','&amp;gt;'), $data);
$data = preg_replace('/(&#*\w+)[\x00-\x20]+;/u', '$1;', $data);
$data = preg_replace('/(&#x*[0-9A-F]+);*/iu', '$1;', $data);
$data = html_entity_decode($data, ENT_COMPAT, 'UTF-8');

// Remove any attribute starting with "on" or xmlns
$data = preg_replace('#(<[^>]+?[\x00-\x20"\'])(?:on|xmlns)[^>]*+>#iu', '$1>', $data);

// Remove javascript: and vbscript: protocols
$data = preg_replace('#([a-z]*)[\x00-\x20]*=[\x00-\x20]*([`\'"]*)[\x00-\x20]*j[\x00-\x20]*a[\x00-\x20]*v[\x00-\x20]*a[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2nojavascript...', $data);
$data = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*v[\x00-\x20]*b[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2novbscript...', $data);
$data = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*-moz-binding[\x00-\x20]*:#u', '$1=$2nomozbinding...', $data);

// Only works in IE: <span style="width: expression(alert('Ping!'));"></span>
$data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?expression[\x00-\x20]*\([^>]*+>#i', '$1>', $data);
$data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?behaviour[\x00-\x20]*\([^>]*+>#i', '$1>', $data);
$data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:*[^>]*+>#iu', '$1>', $data);

// Remove namespaced elements (we do not need them)
$data = preg_replace('#</*\w+:\w[^>]*+>#i', '', $data);

do
{
	// Remove really unwanted tags
	$old_data = $data;
	$data = preg_replace('#</*(?:applet|b(?:ase|gsound|link)|embed|frame(?:set)?|i(?:frame|layer)|l(?:ayer|ink)|meta|object|s(?:cript|tyle)|title|xml)[^>]*+>#i', '', $data);
}
while ($old_data !== $data);

// we are done...
return $data;
}

// use this function to count faster than mysqli_num_rows
function count_rows ($query) {
global $db;

	if ($query!=NULL) {
		$query 	= str_replace ("*", "COUNT(id) AS totalResults", $query);
		$q 		= $db->query ($query);
		$theQ 	= mysqli_fetch_array ($q);
		$totalResults = $theQ['totalResults'];
	} else {
		$totalResults = 0;
	}
return $totalResults;
}

function connect_to_database () {
global $db;

	include_once ("../connection/connection.php");
	
	$db = @mysqli_connect($host, $user, $password) or die ("Main Connection Failed");
	
		/* check connection */
		if (!$db) 
		{
		   printf("Connect failed: %s\n", mysqli_connect_error());
		   exit();
		}				
	$db->select_db($database) or die("Error selecting main database");
}
?>