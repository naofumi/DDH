<?php
///////////////////////////////////////////////////////////
// CSFR token management
//
// For every request, we call verify_csrf_token() and renew_csrf_token().
//
// verify_csrf_token() checks the token for every non-GET request.
//
// Tokens are included in requests by adding the following to any form.
//
// <input type="hidden" name="csrf_token" value="[csrf_token]"> 
//
// where [csrf_token] can be retrieved by `echo $_SESSION["csrf_token"]`
//
///////////////////////////////////////////////////////////
function renew_csrf_token(){
  $_SESSION["csrf_token"] = md5(session_id());
}

function verify_csrf_token(){
  if ($_SERVER['REQUEST_METHOD'] !== "GET") {
    if ($_SESSION["csrf_token"] !== $_REQUEST["csrf_token"]) {
      raise_csrf_error();
    }
  }
}

function raise_csrf_error(){
  die("csrf_token does not match with SESSION:".$_SESSION["csrf_token"]." and REQUEST: ".$_REQUEST["csrf_token"]);
}
