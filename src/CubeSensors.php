<?php

namespace lgladdy\CubeSensors;
use lgladdy\CubeSensors\OAuth;

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
  public $api_base = "v1/";
  public $user_agent = 'CubeSensors for PHP v0.0.1';
  
  public $request_token_url = 'http://api.cubesensors.com/auth/request_token';
  public $authorize_url = 'http://api.cubesensors.com/auth/authorize';
  public $access_token_url = 'http://api.cubesensors.com/auth/access_token';
  
  
  function __construct($consumer_key, $consumer_secret, $oauth_token = NULL, $oauth_token_secret = NULL) {
    $this->sha1_method = new OAuth\OAuthSignatureMethod_HMAC_SHA1();
    $this->consumer = new OAuth\OAuthConsumer($consumer_key, $consumer_secret);
    if (!empty($oauth_token) && !empty($oauth_token_secret)) {
      $this->token = new OAuth\OAuthConsumer($oauth_token, $oauth_token_secret);
    } else {
      $this->token = NULL;
    }
  }
  
  
  function getOAuthRequestToken($callback) {
    $params = array();
    $params['oauth_callback'] = $callback; 
    $url = $this->signOAuthRequest($this->request_token_url, $params, 'GET');
    $call = $this->call($url);
    if (json_decode($call,true)) {
      //If we're here, it means we got a JSON failure object back from CubeSensors, and that's bad.
      return json_decode($call,true);
    } else {
      $token = OAuth\OAuthUtil::parse_parameters($call);
      return $token;
    }
  }
  
  
  function getOAuthAuthorizeURL($token) {
    if (is_array($token)) $token = $token['oauth_token'];
    return $this->authorize_url."?oauth_token={$token}";
  }
  
  
  function getOAuthAccessToken($oauth_verifier) {
    $params = array();
    $params['oauth_verifier'] = $oauth_verifier;
    $url = $this->signOAuthRequest($this->access_token_url, $params, 'GET');
    $call = $this->call($url);
    if (json_decode($call,true)) {
      //If we're here, it means we got a JSON failure object back from CubeSensors, and that's bad.
      return json_decode($call,true);
    } else {
      $token = OAuth\OAuthUtil::parse_parameters($call);
      return $token;
    }
  }
  
  function get($url, $parameters = array()) {
    $url = $this->base_url.$this->api_base.$url;
    $url = $this->signOAuthRequest($url, $parameters);
    //echo "[".time()."] Making a GET request to ".$url."<br />";
    $call = $this->call($url);
    return json_decode($call,true);
  }
  
  
  function call($url) {
    if (is_array($url) && isset($url['url']) && isset($url['data'])) {
      //We've got an array containing url and data...
      $params = $url['data'];
      $url = $url['url'];
    }
    
    $this->last_response = array();
    $curl_object = curl_init();
    
    curl_setopt($curl_object, CURLOPT_USERAGENT, $this->user_agent);
    curl_setopt($curl_object, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($curl_object, CURLOPT_HEADERFUNCTION, array($this, 'processHeader'));
    curl_setopt($curl_object, CURLOPT_HEADER, FALSE);
    curl_setopt($curl_object, CURLOPT_HTTPHEADER, array('Accept: application/json'));
    curl_setopt($curl_object, CURLOPT_URL, $url);
    
    if (isset($params)) {
      //Just incase we ever need POST
      curl_setopt($curl_object, CURLOPT_POST, TRUE);
      curl_setopt($curl_object, CURLOPT_POSTFIELDS, $params);
    }
    
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
  
  function signOAuthRequest($url, $parameters, $method = 'GET') {
    $request = OAuth\OAuthRequest::from_consumer_and_token($this->consumer, $this->token, $method, $url, $parameters);
    $request->sign_request($this->sha1_method, $this->consumer, $this->token);
    if ($method == "POST") {
      $r['url'] = $request->get_normalized_http_url();
      $r['data'] = $request->to_postdata();
      return $r;
    } else {
      return $request->to_url();
    }
  }
  
}

?>
