<?php 

//////////////////////////////////
// Configuration
//////////////////////////////////

// Set the URL for the DDH server location.
// $ddh_server_path = "http://5349-jsonp.castle104.com/iwai-chem_[key]_ddh/";
$ddh_server_path = "http://localhost:8890/iwai-chem_[key]_ddh/";

// Set the HTTP request timeout.
// Default: "2.0"
$timeout = "2.0";

//////////////////////////////////
// Configuration end
//////////////////////////////////

// TODO:
// We could add stuff from https://github.com/s-gv/php-reverse-proxy
// or just look at more robust reverse proxy implementations on Github
// https://github.com/keichan34/HelloPHP/blob/master/index.php


function server_side_embed($endpoint, $params, $source_file_encoding) {
  global $ddh_server_path;
  global $timeout;
  mb_convert_variables('utf-8', $source_file_encoding, $params);
  $query_string = http_build_query($params);
  
  // Create a stream
  // Options: http://www.php.net/manual/en/context.http.php
  $opts = array(
    'http'=>array(
      'header'=>"Accept-language: ja, en;q=0.7\r\n".
                "Accept: text/html\r\n" ,
      'timeout' => $timeout
    )
  );
  $context = stream_context_create($opts);

  $url = $ddh_server_path.$endpoint.($query_string ? "?".$query_string : "");

  // Open the file using the HTTP headers set above
  if ($file = file_get_contents($url, false, $context)) {
    //
    // TODO: We also want to do conversions here and extraction of content based on comment tags.
    //
  error_log($body);
  if (preg_match("/<\!-- DDH Embed start -->(.*)<\!-- DDH Embed end -->/uism", $file, $matches)) {
    $body = $matches[1];
  } else {
    $body = $file;
  }
  return mb_convert_encoding($body, $source_file_encoding, 'utf-8');
  } else {
    return "Failed to get product information from database";
  }
}