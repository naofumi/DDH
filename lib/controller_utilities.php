<?php

//////////////////////////////////////////////////////////
// Controller utilities
//////////////////////////////////////////////////////////
function redirect_to($url = null) {
  if (!$url) {
    // The server does not know that the request is coming 
    // from a reverse proxy and hence $_SERVER["REQUEST_URI"]
    // will be the castle104.com path which is not good.
    // Here, we fix it back to the URL before the reverse proxy.
    $server_url = $_SERVER["REQUEST_URI"];
    $client_url = preg_replace("/^\/ddh_[^\/]+/", "/ddh_jp", $server_url);
    $url = $client_url;
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