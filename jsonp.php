<?php
$bench_start = microtime(true);

require_once(__DIR__.'/../Cache/Lite.php');
require_once(__DIR__.'/data_source.php');
require_once(__DIR__.'/queried_data_source.php');
require_once(__DIR__.'/../config.php');

////////////////////////////////////////////////
// Configuration & initialization
////////////////////////////////////////////////
$cache_directory = __DIR__.'/../tmp/cache/';

/////////////////////////////////////////////////
/// Functions to retrieve data from the CSV files
/////////////////////////////////////////////////

function get_row_count($source) {
  return exec("wc -l < $source");
}

function get_rows($start = 0, $limit = 100, $source, $encoding) {
  $start = $start + 1; // sed counts the first line as 1
  $end = $start + $limit - 1;
  $result = array();
  $lines = array();
  exec("sed -n '$start,${end} p' $source", $lines);
  foreach ($lines as $line) {
    $row = str_getcsv($line);
    $row = row_convert_encoding($row, $encoding);
    $result[$row[0]] = $row;
  }
  return $result;
}

// Convert each cell in the row from $encoding to 'UTF-8'
function row_convert_encoding($row, $encoding) {
	for($i = 0; $i < count($row); $i++) {
		$row[$i] = mb_convert_encoding($row[$i], 'UTF-8', $encoding);
	}
	return $row;
}


function is_preview() {
	return isset($_GET['pv']);
}

/////////////////////////////////////////////////
// Functions to process the request
////////////////////////////////////////////////
function get_ids($ids_param = null){
	if (!$ids_param)
		$ids_param = isset($_GET["ids"]) ? $_GET["ids"] : "";
	return ($ids_param ? explode(',', $ids_param) : array());
}

function insert_location(){
	return $_GET["loc"];
}

////////////////////////////////////////////////
// Cache object
////////////////////////////////////////////////

// Creates or deletes the cache folder.
// This will automatically turn on/off caching.
function setup_cache(){
	global $use_cache;
	global $cache_directory;
	if ($use_cache) {
		if (!file_exists ($cache_directory)) {
			mkdir($cache_directory, 0755, true);
		}
	} else {
		if (file_exists ($cache_directory)) {
			system("rm -rf ".escapeshellarg($cache_directory));			
		}
	}
}

// Cache key generated based on the last update file
// in the current directory or preview directory, based
// on is_preview().
function cache_key($data_source){
	return $_SERVER["REQUEST_URI"]."-".$data_source->last_updated_at();
}

$cache_obj;
function cache_obj(){
	global $cache_obj;
	global $cache_expire;
	global $cache_directory;
	if (!$cache_obj) {
		$options = array(
		  'cacheDir' => $cache_directory,
		  'lifeTime' => $cache_expire // seconds
		);
		$cache_obj = new Cache_Lite($options);		
	}
	return $cache_obj;
}

//////////////////////////////////////////////////
// Functions to return JSON
/////////////////////////////////////////////////
function start_jsonp($data_source) {
	header('Content-Type: application/javascript');

	cache_start($data_source);
	ob_start();
}

function output_jsonp() {
	if (isset($_GET['test_jsonp']))
		return;

  $html = ob_get_contents();
  ob_end_clean();

  if (isset($_GET['preview']))
  	$html = "<div style='border: 1px dotted red'>".$html."</div>";
 
  # json_encode supported on PHP 5 >= 5.2.0
  # PECL json >= 1.2.0
  $json_html = json_encode($html);
  $insert_location = insert_location();
  $output = <<<JS
var insertTo = document.getElementById('$insert_location');
insertTo.innerHTML = $json_html;
JS;
	echo $output;
	cache_end($output);
}

function cache_start($data_source) {
	if (isset($_GET['no_cache']))
		return;

	if ($cache = cache_obj()->get(cache_key($data_source))) {
    error_log("Served from cache.");
	  echo $cache;
    global $bench_start;
    $bench_time = microtime(true) - $bench_start;
    error_log("Total time $bench_time secs.");
	  exit;
	}	
  ob_start();
}

function cache_end() {
  global $bench_start;
  $output = ob_get_contents();
  ob_end_clean();
	cache_obj()->save($output);	
  echo $output;
  $bench_time = microtime(true) - $bench_start;
  error_log("Total time $bench_time secs.");
}

//////////////////////////////////////////////////////////
// Functions to change publish status
/////////////////////////////////////////////////////////
function publish_preview_files(){
	global $current_directory;
	global $preview_directory;
	global $previous_directory;
	global $source_data;
	system('cp '.$current_directory.'* '.$previous_directory);
	foreach($source_data as $key => $value) {
		$path = $preview_directory.$value['filename'];
		$destination = $current_directory.$value['filename'];
		if (file_exists($path)) {
			rename($path, $destination);
		}
		// If file exists in $path, then move it to
		// $destination. (removing it from "preview")
	}
}

function rollback_files(){
	// CP all in "current" to "preview" with overwrite.
	// MV all in "previous" to "current".
	global $current_directory;
	global $preview_directory;
	global $previous_directory;
	system('cp '.$current_directory.'* '.$preview_directory);
	system('mv '.$previous_directory.'* '.$current_directory);
}


//////////////////////////////////////////////////////////
// Controller utilities
//////////////////////////////////////////////////////////
function redirect_to($url = null) {
	if (!$url) {
		$url = $_SERVER["REQUEST_URI"];
	}
	header("Location: ".$url);
	exit();
}

function set_flash($message) {
	$_SESSION["flash"] = $message;
}

function echo_flash() {
	if (isset($_SESSION["flash"]) && $_SESSION["flash"]) {
		echo "<div class='notice'>".$_SESSION["flash"]."</div>";	
		$_SESSION["flash"] = null;
	}
}

function add_query_to_url($url, $params = array()) {
	$original_query_string = parse_url($url)['query'];
	$original_path = parse_url($url)['path'];
	$original_params = array();
	foreach(explode('&', $original_query_string) as $query_set) {
		$single_param = explode('=', $query_set);
		$original_params[urldecode($single_param[0])] = urldecode($single_param[1]);
	}
	return $original_path."?".http_build_query(array_merge($original_params, $params));
}

//////////////////////////////////////////////////////////
// Basic Authentication
//////////////////////////////////////////////////////////

// http://www.webdesignleaves.com/wp/php/228/
function basic_auth(){
	global $user;
	global $hashed_password;
	global $salt;
  if (isset($_SERVER['PHP_AUTH_USER']) and isset($_SERVER['PHP_AUTH_PW'])){
    if (($_SERVER['PHP_AUTH_USER'] == $user) && 
        (crypt($_SERVER['PHP_AUTH_PW'], $salt) == $hashed_password)){
      return;
    }
  }
  header('WWW-Authenticate: Basic realm="Restricted Area"');
  header('HTTP/1.0 401 Unauthorized');
  header('Content-type: text/html; charset='.mb_internal_encoding());
  die("Authorization Failed.");
}

///////////////////////////////////////////////////////////
// Logging
//////////////////////////////////////////////////////////
function log_request() {
	error_log($_SERVER['REQUEST_METHOD']." ".$_SERVER['REQUEST_URI']." Accept:".
	          $_SERVER['HTTP_ACCEPT']." IP:".$_SERVER['REMOTE_ADDR']." Request:".var_export($_REQUEST, true).
	          " Session:".var_export($_SESSION, true) );
}

///////////////////////////////////////////////////////////
// CSFR token management
///////////////////////////////////////////////////////////
function renew_csrf_token(){
	$_SESSION["csrf_token"] = md5(uniqid(mt_rand(), true));
}

function verify_csrf_token(){
	if ($_SERVER['REQUEST_METHOD'] !== "GET") {
		if ($_SESSION["csrf_token"] !== $_REQUEST["csrf_token"]) {
			raise_csrf_error();
		}
	}
}

function raise_csrf_error(){
	die("csrf_token does not match");
}

///////////////////////////////////////////
// Debug
//////////////////////////////////////////
function str_var_dump($variable){
	ob_start();
	var_dump($variable);
  return ob_get_clean();
}

function log_var_dump($variable){
	error_log(str_var_dump($variable));
}

// These are required last because they depend on the above functions.
require_once(__DIR__.'/view_helpers.php');
require_once(__DIR__.'/data_augmenters.php');

////////////////////////////////////////////////////////////
// Initialize
////////////////////////////////////////////////////////////
log_request();
session_start();
verify_csrf_token();
renew_csrf_token();
setup_cache();

  

