<?php
//////////////////////////////
// URL manipulation utility functions
//////////////////////////////
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
