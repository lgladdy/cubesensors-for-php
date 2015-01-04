<?php

date_default_timezone_set("Europe/London");
require('config.php');
require('cubesensors/cubesensors.php');
session_start();

if ((!isset($_SESSION['oauth_complete']) || !$_SESSION['oauth_complete']) && !defined('AUTHED_KEY')) {
  header("Location: /connect.php?unauthenticated=true");
  exit();
}

if (defined('AUTHED_KEY')) {
  $cube = new CubeSensors(CS_CONSUMER_KEY, CS_CONSUMER_SECRET, AUTHED_KEY, AUTHED_SECRET);
} else {
  $cube = new CubeSensors(CS_CONSUMER_KEY, CS_CONSUMER_SECRET, $_SESSION['oauth_token'],$_SESSION['oauth_token_secret']);
}

$devices = $cube->get('devices/');

foreach($devices['devices'] as $device) {
  $uid = $device['uid'];
  echo "<section style='width: 300px; float:left; padding: 5px; border: 1px solid #ccc; border-radius: 5px; margin-right: 20px; margin-bottom: 20px;' class='device' id='cube_".$uid."'>";
  //echo "<h3>Current data for cube: ".$device['name']." (".$uid.")</h3>";
  echo "<h3 style='margin-top:0;padding-top:0'>Current data for cube: ".$device['name']."</h3>";
  $current = $cube->get('devices/'.$uid.'/current');
  if ($current['ok']) {
    if (empty($current['results'])) {
      echo "No Data from Cube";
    } else {
      foreach($current['results'][0] as $key => $value) {
        if ($key == 0) {
          echo "Time: ".date('r',strtotime($value))."<br />";
        } else {
          if (is_bool($value)) {
            $value = $value ? 'true' : 'false';
          } else if (is_null($value)) {
            $value = 'null';
          }
          echo ucwords($current['field_list'][$key-1]).": ".$value."<br />";
        }
      }
    }
  } else {
    echo "Invalid response from API<br />";
    var_dump($current);
  }
  echo "</section>";
}