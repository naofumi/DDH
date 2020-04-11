<?php

//////////////////////////////////////////////////////////
// Authentication
//////////////////////////////////////////////////////////

// http://www.webdesignleaves.com/wp/php/228/
function authenticate($users = false){
  global $admin_users;
  global $wordpress_home_url;

  if (isset($admin_users)) {
    authenticate_by_basic($users);
  } else if (isset($wordpress_home_url)) {
    authenticate_by_wordpress();
  }
}

////////////////////////////////////////
//
// Basic authentication
//
// Basic authentication uses 
// the global $admin_users
// to get the usernames and passwords
// of authorised users.
//
/////////////////////////////////////////
function authenticate_by_basic($users = false) {
  global $admin_users;
  if ($users == false)
    $users = $admin_users;
  // We suppress reverse_proxy_requirement only for pages behind 
  // authentication.
  global $suppress_reverse_proxy_requirement;
  $suppress_reverse_proxy_requirement = true;

  if (isset($_SERVER['PHP_AUTH_USER']) and isset($_SERVER['PHP_AUTH_PW'])){
    $user = $users[$_SERVER['PHP_AUTH_USER']];
    if ($user) {
      if (crypt($_SERVER['PHP_AUTH_PW'], $user["salt"]) == $user["hashed_password"]) {
        return;
      }
    }
  }
  header('WWW-Authenticate: Basic realm="DDH Restricted Area"');
  header('HTTP/1.0 401 Unauthorized');
  header('Content-type: text/html; charset='.mb_internal_encoding());
  die("Authorization Failed.");      
}

////////////////////
//
// Wordpress authentication
//
// Wordpress authentication uses
// the user who is currently logged into
// Wordpress for authentication.
//
// The browser is redirected to Wordpress
// which should be using the DDH plugin.
// Then the browser will be redirected back
// with the credentials of the current logged in user.
// DDH uses these credentials to authorise users.
//
////////////////////
function authenticate_by_wordpress() {
  global $wordpress_home_url;
  global $secret_key;
  global $wordpress_role;

  // Wordpress authentication
  if (isset($_SESSION['email']) && $_SESSION['email']) {
    // If we already have an active authenticated session,
    // then allow access.

    // We suppress reverse_proxy_requirement only for pages behind 
    // authentication.
    global $suppress_reverse_proxy_requirement;
    $suppress_reverse_proxy_requirement = true;
  } else if (isset($_GET['id']) && 
             isset($_GET['email']) && 
             isset($_GET['roles']) && 
             isset($_GET['time']) && 
             isset($_GET['token'])){
    // If we have an email, time, and token in the request params,
    // this means we have received a redirect from the authentication server.
    // Then initiate an active authenticated session for that user
    // and redirect to the URL without the tokens.
    //
    // Check time for freshness and role for permissions.
    // If successful, then redirect to $_GET['return_to'] to get rid of
    // authentication related parameters.
    $time = $_GET['time'];
    $time_allowance = 60 * 5; //seconds

    $roles = preg_split('/\|/', $_GET['roles']);
    $verify_token = crypt($_GET['email'].$_GET['roles'].$_GET['id'].$_GET['time'], $secret_key);
    if ($time + $time_allowance > time() && 
        in_array($wordpress_role, $roles) &&
        $verify_token == $_GET['token']) {
      // LOGIN
      $_SESSION['id'] = $_GET['id'];
      $_SESSION['email'] = $_GET['email'];
      $_SESSION['roles'] = $_GET['roles'];

      header("location: ".$_GET['return_to']);
      exit();
    } else {
      // Failed login
      // Redirect to Wordpress login and get them to come back to 'return_to'
      // after successful login.
      $params = array('redirect_to' => $_GET['return_to']);
      $query_string = http_build_query($params);      
      header("location: ".$wordpress_home_url."wp-login.php?".$query_string);        
      exit();
    }

  } else {
    // If not authenticated, then we send a redirect to the Wordpress login
    // screen and tell them to come back after a successful login.
    // 
    // TODO: doesn't manage HTTPS
    $params = array('return_to' => "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");
    $query_string = http_build_query($params);      

    header("location: $wordpress_home_url/current_user?$query_string");
    exit();
  }
}
