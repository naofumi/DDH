<?php
$bench_start = microtime(true);
require(dirname(__FILE__).'/vendor/autoload.php');

require_once(dirname(__FILE__).'/Cache/Lite.php');
require_once(dirname(__FILE__).'/lib/mongodb_data_source.php');
require_once(dirname(__FILE__).'/../config.php');

///////////////////////////////////////////////
//
// Validate configuration
//
///////////////////////////////////////////////
// Test $iconv_path
if (exec("which iconv") != $iconv_path) {
  die("iconv was not found in ".$iconv_path);
}

// Test gnugrep_path
if (exec("which grep") != $gnugrep_path) {
  die("grep was not found in ".$gnugrep_path);
}

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
// After including jsonp.php. We typically only do this inside `authenticate()`
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
/// Functions to retrieve data from the CSV files for preview
/////////////////////////////////////////////////

// function get_row_count($source, $encoding) {
//   $iconv_path = $GLOBALS["iconv_path"];
//   if (!$iconv_path) {
//     die ('$iconv_path is not set in config.php');
//   }

//   return exec("$iconv_path --from-code $encoding --to-code UTF-8 $source | wc -l");
// }

// function get_rows($start = 0, $limit = 100, $source, $encoding, $delimiter) {
//   $iconv_path = $GLOBALS["iconv_path"];
//   if (!$iconv_path) {
//     die ('$iconv_path is not set in config.php');
//   }

//   $start = $start + 1; // sed counts the first line as 1
//   $end = $start + $limit - 1;
//   $result = array();
//   $lines = array();
//   exec("$iconv_path --from-code $encoding --to-code UTF-8 $source | sed -n '$start,${end} p'", $lines);
//   foreach ($lines as $line) {
//     $row = str_getcsv($line, $delimiter);
//     array_push($result, $row);
//   }
//   return $result;
// }

// Convert each cell in the row from $encoding to 'UTF-8'
function row_convert_encoding($row, $encoding) {
	for($i = 0; $i < count($row); $i++) {
		$row[$i] = mb_convert_encoding($row[$i], 'UTF-8', $encoding);
	}
	return $row;
}


//////////////////////////////////////////////////
// Function for preview
/////////////////////////////////////////////////

function is_preview() {
  return !!(isset($_SESSION['preview']) && $_SESSION['preview']);
	// return isset($_GET['pv']);
}

// For the current version, returns 'current'
// For the preview version, returns 'preview'
// Otherwise, returns the version timestamp
function preview_version(){
  if (isset($_SESSION['preview'])) {
    if ($_SESSION['preview'] == 'preview') {
      return 'preview';
    } else {
      return (int)$_SESSION['preview'];
    }
  } else {
    return 'current';
  }
}

// Human readable preview_version_name
function preview_version_name() {
  if (preview_version() == 'preview') {
    return "準備中";
  } else if (preview_version() == 'current') {
    return "公開中";
  } else {
    return date("Y-m-d H:i:s", preview_version());
  }
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
	if ($use_cache && !is_preview()) {
		if (!file_exists ($cache_directory)) {
			mkdir($cache_directory, 0755, true);
		}
	} else {
		if (file_exists ($cache_directory)) {
			system("rm -rf ".escapeshellarg($cache_directory));			
		}
	}
}

// Cache key generated based on the last snapshot.
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

function output_jsonp($options = array()) {
	if (isset($_GET['test_jsonp']))
		return;

  $additional_javascript = "";
  if ($options["scripts"]) {
    $scripts_as_js = json_encode($options["scripts"]);
    $additional_javascript .= <<<SCRIPT_JS
scripts = $scripts_as_js;
(function(){
  var fjs = document.getElementsByTagName('script')[0];
  for (var i=0; i < scripts.length; i++) { 
    var js = document.createElement('script');
    js.src = "/ddh_jp/javascripts/" + scripts[i];
    js.setAttribute('async', 'true');
    fjs.parentNode.insertBefore(js, fjs);
  }
})();
SCRIPT_JS;
  }

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
$additional_javascript;
JS;
  }

	echo $output;
	cache_end($output);
}

function cache_start($data_source) {
  global $use_cache;
  if (!$use_cache || is_preview()) {
    return;
  }
	if (isset($_GET['no_cache']))
		return;

  // Serve from cache if available
	if ($cache = cache_obj()->get(cache_key($data_source))) {
    error_log("Served from cache.");
	  echo $cache;
    global $bench_start;
    $bench_time = microtime(true) - $bench_start;
    error_log("Total time $bench_time secs.");
	  exit;
	}	
  // Else continue and collect contents to buffer
  ob_start();
}

function cache_end() {
  global $use_cache;
  if (!$use_cache || is_preview()) {
    return;
  }
  global $bench_start;
  // Get the contents from the buffer and save in cache,
  // and then echo the results.
  $output = ob_get_contents();
  ob_end_clean();
	cache_obj()->save($output);	
  echo $output;
  $bench_time = microtime(true) - $bench_start;
  error_log("Total time $bench_time secs.");
}

//////////////////////////////////////////////////////////
// Functions to change publish status
// No longer used. Prepare to delete.
/////////////////////////////////////////////////////////
// function publish_preview_files(){
// 	global $current_directory;
// 	global $preview_directory;
// 	global $previous_directory;
// 	global $source_parameters;
//   // 'cp -p' preserves timestamps
// 	system('cp -p '.$current_directory.'* '.$previous_directory);
// 	foreach($source_parameters as $key => $value) {
// 		$path = $preview_directory.$value['filename'];
// 		$destination = $current_directory.$value['filename'];
// 		if (file_exists($path)) {
//   		rename($path, $destination);
// 		}
// 		// If file exists in $path, then move it to
// 		// $destination. (removing it from "preview")
// 	}
// }

// function rollback_files(){
// 	// CP all in "current" to "preview" with overwrite.
// 	// MV all in "previous" to "current".
// 	global $current_directory;
// 	global $preview_directory;
// 	global $previous_directory;
// 	system('cp -p '.$current_directory.'* '.$preview_directory);
// 	system('mv '.$previous_directory.'* '.$current_directory);
// }

// function all_filenames() {
//   global $source_parameters;
//   $result = array();
//   foreach($source_parameters as $key => $value){
//     array_push($result, $value['filename']);
//   }
//   return $result;
// }


require_once('lib/controller_utilities.php');

require_once('lib/url.php');
require_once('lib/authentication.php');
require_once('lib/logger.php');
require_once('lib/csrf.php');
require_once('lib/debug.php');
require_once('lib/sorting.php');


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
  function str_getcsv ($string, $delimiter = ",") {
    $fh = fopen('php://temp', 'r+');
    fwrite($fh, $string);
    rewind($fh);

    $row = fgetcsv($fh, $delimiter);

    fclose($fh);
    return $row;
  }
}

// These are required last because they depend on the above functions.
require_once(dirname(__FILE__).'/lib/view_helpers.php');
require_once(dirname(__FILE__).'/lib/high_level_view_helpers.php');

////////////////////////////////////////////////////////////
// Initialize
////////////////////////////////////////////////////////////
log_request();
session_start();
verify_csrf_token();
renew_csrf_token();
setup_cache();

  

