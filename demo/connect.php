<?php

require "vendor/autoload.php";
use lgladdy\CubeSensors\CubeSensors;

require('config.php');
session_start();

if (isset($_GET['clear'])) {
  session_destroy();
  session_start();
  echo "Session restarted: ";
  var_dump($_SESSION);
}

if (isset($_SESSION['oauth_complete']) && $_SESSION['oauth_complete']) {
  header("Location: /");
  exit();
}

if (isset($_GET['go'])) {

  $cube = new CubeSensors(CS_CONSUMER_KEY, CS_CONSUMER_SECRET);
  
  $token = $cube->getOAuthRequestToken(CS_RETURN_PATH);
  
  $_SESSION['oauth_token'] = $token['oauth_token'];
  $_SESSION['oauth_token_secret'] = $token['oauth_token_secret'];
  
  $url = $cube->getOAuthAuthorizeURL($token);
  header("Location: ".$url);
  exit();

} else if (isset($_GET['response'])) {

  if (isset($_GET['oauth_verifier']) && isset($_GET['oauth_token'])) {
  
    if (!isset($_SESSION['oauth_token_secret']) || !isset($_SESSION['oauth_token'])) {
      die("Invalid session.");
    }
  
    $cube = new CubeSensors(CS_CONSUMER_KEY, CS_CONSUMER_SECRET, $_SESSION['oauth_token'], $_SESSION['oauth_token_secret']);
    $final_token = $cube->getOAuthAccessToken($_GET['oauth_verifier']);
    
    $_SESSION['oauth_complete'] = true;
  
    $_SESSION['oauth_token'] = $final_token['oauth_token'];
    $_SESSION['oauth_token_secret'] = $final_token['oauth_token_secret'];
    
    header("Location: /");
    exit();
    
  } else {
    die("Incomplete Response from CubeSensors");
  }
  
  
} else { ?>
  <form method="get">
    <input type="submit" name="go" value="Sign In with CubeSensors" />
  </form>
<?php } ?>
