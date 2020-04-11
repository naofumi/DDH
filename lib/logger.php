<?php

///////////////////////////////////////////////////////////
// Logging
//////////////////////////////////////////////////////////
function log_request() {
  error_log($_SERVER['REQUEST_METHOD']." ".$_SERVER['REQUEST_URI']." Accept:".
            $_SERVER['HTTP_ACCEPT']." IP:".$_SERVER['REMOTE_ADDR']." Request:".var_export($_REQUEST, true).
            " Session:".var_export((defined('_SESSION') ? $_SESSION : null), true) );
}

function benchtime_log($message, $time_duration) {
  error_log("BENCHTIME ".sprintf('%0.3fs', $time_duration)." $message ");
}

