<?php
///////////////////////////////////////////
// Debug functions
//////////////////////////////////////////
function str_var_dump($variable){
  ob_start();
  var_dump($variable);
  return ob_get_clean();
}

function log_var_dump($variable){
  error_log(str_var_dump($variable));
}

