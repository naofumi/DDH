<?php 

//////////////////////////////////
// Configuration
//////////////////////////////////

// Get the URL for the DDH server location.
// We need the full URL to use the `file_get_contents()` method.
// Use the reverse proxy
function ddh_server_path(){
  return "http://".$_SERVER['SERVER_NAME'].":".$_SERVER['SERVER_PORT']."/ddh_jp/";
}

// Encoding is a very tricky subject. I found that "®" was not being correctly
// converted from UTF-8 into SJIS when I was using `mb_convert_encoding`, even
// with "sjis-win". After checking up character code sets (http://charset.uic.jp/show/shiftjis2004/),
// I found that "®" is not defined in most of the SJIS variants other
// than "SHIFT_JIS-2004" and "SHIFT_JISX0213". `mb_convert_encoding` doesn't
// handle this.
// I don't know what the situation is with `.aspx`.
// A better solution would obviously be to persuade the client to use utf8 encoding
// throughout their website.
//
// Read README-ENCODING.md for more information
$encoding_aliases = array("iconv" => array("sjis" => "MS_KANJI", 'utf8' => 'utf8'),
                          "mb" => array("sjis" => "sjis-win", 'utf8' => 'utf8'));
$encoding_method = "iconv";
// $encoding_method = "mb";

// Set the HTTP request timeout.
// Default: "2.0"
$timeout = "2.0";

//////////////////////////////////
// Configuration end
//////////////////////////////////

function server_side_embed($endpoint, $params, $source_file_encoding) {
  global $timeout;
  mb_convert_variables('utf-8', $source_file_encoding, $params);
  $params['html_only'] = 1; // Tell the output_jsonp() method to not pad with Javascript
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

  $url = ddh_server_path().$endpoint.($query_string ? "?".$query_string : "");

  // Open the file using the HTTP headers set above
  if ($file = file_get_contents($url, false, $context)) {
    //
    // TODO: We also want to do conversions here and extraction of content based on comment tags.
    //
    if (preg_match("/<\!-- DDH Embed start -->(.*)<\!-- DDH Embed end -->/uism", $file, $matches)) {
      $body = $matches[1];
    } else {
      $body = $file;
    }
    return encode($body, $source_file_encoding);
    // return iconv("UTF-8", "$source_file_encoding//TRANSLIT", $body);
    // return mb_convert_encoding($body, $source_file_encoding, 'utf-8');
  } else {
    return "Failed to get product information from database";
  }
}

function encode($string, $encoding_alias) {
  global $encoding_aliases;
  global $encoding_method;
  $encoding = $encoding_aliases[$encoding_method][$encoding_alias];
  if (!$encoding) {
    die("encoding method $encoding_method, encoding_alias $encoding_alias are not valid in server_side_embed.php");
  } else if ($encoding == 'utf8') {
    return $string; // no conversion necessary
  } else {
    if ($encoding_method == "iconv") {
      return iconv("UTF-8", "$encoding//IGNORE//TRANSLIT", $string);
    } else if ($encoding_method == "mb") {
      return mb_convert_encoding($string, $encoding, 'utf-8');
    } else {
      die("encoding must be either 'iconv' or 'mb' but $encoding_method specified in server_side_embed.php");
    }    
  }
}