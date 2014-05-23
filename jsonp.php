<?php
$bench_start = microtime(true);

require_once(dirname(__FILE__).'/../Cache/Lite.php');
require_once(dirname(__FILE__).'/data_source.php');
require_once(dirname(__FILE__).'/queried_data_source.php');
require_once(dirname(__FILE__).'/../config.php');

///////////////////////////////////////////////
// Restrict access to proxies
//
// All PHP files which require jsonp.php will
// not be served unless they come through a reverse
// proxy that matches $original_host_regex (set in config.php).
//
// This ensures that a direct request to the implementation
// directory will be hidden. This provides
//
// If you want direct access (typically in admin pages), then set
// $suppress_reverse_proxy_requirement = true;
// After including jsonp.php. We typically only do this inside `basic_auth()`
// so that only the restricted pages can be accessed directly.
///////////////////////////////////////////////

If (!isset($original_host_ip_address) || !$original_host_ip_address) {
  header("HTTP/1.0 404 Not Found");
  error_log('$original_host_ip_address was not set in config.php');
  exit();
}
if (!(isset($suppress_reverse_proxy_requirement) && $suppress_reverse_proxy_requirement)) {
  // If the request came directly and not via a registered reverse proxy,
  // then return a 404 file not found error.
  if (!preg_match($original_host_ip_address, $_SERVER["REMOTE_ADDR"])) {
    header("HTTP/1.0 404 Not Found");
    include(dirname(__FILE__)."/404.html");
    exit();
  }  
}

////////////////////////////////////////////////
// Configuration & initialization
////////////////////////////////////////////////
$cache_directory = dirname(__FILE__).'/../tmp/cache/';
if (ini_get('magic_quotes_gpc')) {
  error_log ("Configuration error: magic_quotes_gpc is not disabled!. Disable in .htaccess");
  die(ini_get('magic_quotes_gpc'));
}

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
  if (!isset($_GET['html_only'])) {
    header('Content-Type: application/javascript');  
  }
	
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
 
  if (isset($_GET['html_only'])){
    $output = $html;
  } else {
    # json_encode supported on PHP 5 >= 5.2.0
    # PECL json >= 1.2.0
    $json_html = json_encode($html);
    $insert_location = insert_location();
    $output = <<<JS
var insertTo = document.getElementById('$insert_location');
insertTo.innerHTML = $json_html;
JS;
  }

	echo $output;
	cache_end($output);
}

function cache_start($data_source) {
  global $use_cache;
  if (!$use_cache) {
    return;
  }
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
  global $use_cache;
  if (!$use_cache) {
    return;
  }
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
	global $source_parameters;
  // 'cp -p' preserves timestamps
	system('cp -p '.$current_directory.'* '.$previous_directory);
	foreach($source_parameters as $key => $value) {
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
	system('cp -p '.$current_directory.'* '.$preview_directory);
	system('mv '.$previous_directory.'* '.$current_directory);
}

function all_filenames() {
  global $source_parameters;
  $result = array();
  foreach($source_parameters as $key => $value){
    array_push($result, $value['filename']);
  }
  return $result;
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
	$original_query_string = array_lookup(parse_url($url), 'query');
	$original_path = array_lookup(parse_url($url), 'path');
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
  // We suppress reverse_proxy_requirement only for pages behind 
  // authentication.
  global $suppress_reverse_proxy_requirement;
  $suppress_reverse_proxy_requirement = true;

	global $user;
	global $hashed_password;
	global $salt;
  if (isset($_SERVER['PHP_AUTH_USER']) and isset($_SERVER['PHP_AUTH_PW'])){
    if (($_SERVER['PHP_AUTH_USER'] == $user) && 
        (crypt($_SERVER['PHP_AUTH_PW'], $salt) == $hashed_password)){
      return;
    }
  }
  header('WWW-Authenticate: Basic realm="DDH Admin Area"');
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
	          " Session:".var_export((defined('_SESSION') ? $_SESSION : null), true) );
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

////////////////////////////////////////////
// Utility functions
///////////////////////////////////////////

// PHP 5.3 cannot dereference arrays.
// That is, it can't do
//   function_that_returns_array()[3]
//
// This feature has been added in PHP 5.4, but to 
// support PHP 5.3 use this function as follows;
//   array_lookup(function_that_returns_array(), 3)
function array_lookup ($array, $key) {
  return isset($array[$key]) ? $array[$key] : null;
}

// Shim for `str_getcsv` which isn't available
// for PHP 5.2 and lower.
// http://stackoverflow.com/questions/13430120/str-getcsv-alternative-for-older-php-version-gives-me-an-empty-array-at-the-e
if (!function_exists('str_getcsv')) {
  function str_getcsv ($string) {
    $fh = fopen('php://temp', 'r+');
    fwrite($fh, $string);
    rewind($fh);

    $row = fgetcsv($fh);

    fclose($fh);
    return $row;
  }
}

// These are required last because they depend on the above functions.
require_once(dirname(__FILE__).'/view_helpers.php');
require_once(dirname(__FILE__).'/data_augmenters.php');

////////////////////////////////////////////////////////////
// Initialize
////////////////////////////////////////////////////////////
log_request();
session_start();
verify_csrf_token();
renew_csrf_token();
setup_cache();

  

