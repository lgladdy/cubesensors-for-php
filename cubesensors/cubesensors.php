<?php

/**
 ** CubeSensors for PHP **
 **
 ** A PHP Library to provide CubeSensors API access
 ** 
 ** Liam Gladdy (@lgladdy) liam@gladdy.co.uk
 */

require_once('OAuth.php');


class CubeSensors {
  public $base_url = "http://api.cubesensors.com/";
  public $user_agent = 'CubeSensors for PHP v0.0.1';
  
  public $request_token_url = 'http://api.cubesensors.com/auth/request_token';
  public $authorize_url = 'http://api.cubesensors.com/auth/authorize';
  public $access_token_url = 'http://api.cubesensors.com/auth/access_token';
  
  
  function __construct($consumer_key, $consumer_secret, $oauth_token = NULL, $oauth_token_secret = NULL) {
    $this->sha1_method = new OAuthSignatureMethod_HMAC_SHA1();
    $this->consumer = new OAuthConsumer($consumer_key, $consumer_secret);
    if (!empty($oauth_token) && !empty($oauth_token_secret)) {
      $this->token = new OAuthConsumer($oauth_token, $oauth_token_secret);
    } else {
      $this->token = NULL;
    }
  }
  
  
  function requestOAuthToken($callback) {
    $params = array();
    $params['oauth_callback'] = $callback; 
    $url = $this->signOAuthRequest($this->request_token_url, $params);
    echo($url);
    echo "<br />";
    $call = $this->call($url);
    echo $call;
    echo "<br />";
    $response = json_decode($call,true);
    var_dump($response);
    die();
    $token = OAuthUtil::parse_parameters($response);
    return $token;
  }
  
  
  function call($url) {
    $this->last_response = array();
    $curl_object = curl_init();
    
    curl_setopt($curl_object, CURLOPT_USERAGENT, $this->user_agent);
    curl_setopt($curl_object, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($curl_object, CURLOPT_HEADERFUNCTION, array($this, 'processHeader'));
    curl_setopt($curl_object, CURLOPT_HEADER, FALSE);                                        
    curl_setopt($curl_object, CURLOPT_HTTPHEADER, array('Accept: application/json'));
    curl_setopt($curl_object, CURLOPT_URL, $url);
    $response = curl_exec($curl_object);
    $this->last_response_code = curl_getinfo($curl_object, CURLINFO_HTTP_CODE);
    $this->last_response = array_merge($this->last_response, curl_getinfo($curl_object));
    $this->url = $url;
    curl_close ($curl_object);
    return $response;
  }
  
  function processHeader($ch, $header) {
    $i = strpos($header, ':');
    if (!empty($i)) {
      $key = str_replace('-', '_', strtolower(substr($header, 0, $i)));
      $value = trim(substr($header, $i + 2));
      $this->response_headers[$key] = $value;
    }
    return strlen($header);
  }

  
  
  function signOAuthRequest($url, $parameters) {
    $request = OAuthRequest::from_consumer_and_token($this->consumer, $this->token, 'GET', $url, $parameters);
    $request->sign_request($this->sha1_method, $this->consumer, $this->token);
    return $request->to_url();
  }
  
}

?>
