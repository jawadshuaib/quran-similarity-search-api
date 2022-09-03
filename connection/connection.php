<?php
$pathToPwd= "../connection/pwd/pwd.php";
if (file_exists ($pathToPwd)) {	
	include_once ($pathToPwd);
}

$hostName = $_SERVER['HTTP_HOST'];


if ($hostName == "localhost") {	
	$database = defined ('LOCAL_DB') ? LOCAL_DB : "---LOCAL DATABASE NAME---";
	$host = defined ('LOCAL_HOST') ? LOCAL_HOST : "---LOCAL DATABASE HOST---";
	$user = defined ('LOCAL_USER') ? LOCAL_USER : "---LOCAL DATABASE USERNAME---";
	$password = defined ('LOCAL_PWD') ? LOCAL_PWD : "---LOCAL DATABASE PASSWORD---";
}
else {			
	// TEST: https://quran-similarity-score.000webhostapp.com/api/verse.php?surah_number=10&aya_number=49
	$database = defined ('REMOTE_DB') ? REMOTE_DB : "---REMOTE DATABASE NAME---";
	$host = defined ('REMOTE_HOST') ? REMOTE_HOST : "---REMOTE DATABASE HOST---";
	$user = defined ('REMOTE_USER') ? REMOTE_USER : "---REMOTE DATABASE USERNAME---";
	$password = defined ('REMOTE_PWD') ? REMOTE_PWD : "---REMOTE DATABASE PASSWORD---";
}
?>