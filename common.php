<?php
define("DEFAULT_TRANSLATION_ID", 456);

function total_verses_above_cut_off ($translationId=0, $surahNumber=0, $ayaNumber=0, $method, $cutOff=0) {
	$query = "SELECT * FROM `tbl_similarity_score` WHERE `translation_id` = $translationId AND `surah_number` = $surahNumber AND `aya_number` = $ayaNumber AND `$method` > $cutOff";
	return count_rows ($query);
}

function get_quranic_text ($surahNumber=0, $ayaNumber=0) {
	list ($text) = r (get_properties ("SELECT `text` FROM `tbl_quran` WHERE `surah_number` = $surahNumber AND `aya_number` = $ayaNumber"));	
	return $text;
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
		$totalResults=mysqli_num_rows ($q);
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