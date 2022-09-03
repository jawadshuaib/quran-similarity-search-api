<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");

if ($_SERVER['HTTP_HOST']=="localhost") {
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);	
}

/* Make sure to properly add your own vendor autoload path */
require_once '../vendor/autoload.php';

include_once ("../common.php");

if (!isset ($db))
	connect_to_database();
?>