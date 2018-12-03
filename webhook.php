<?php

require 'config.php';
require 'bot.php';

function exec_curl_request($handle) {
    $response = curl_exec($handle);
  
    if ($response === false) {
      $errno = curl_errno($handle);
      $error = curl_error($handle);
      error_log("CURL returned error $errno: $error\n");
      curl_close($handle);
      return false;
    }
  
    $http_code = intval(curl_getinfo($handle, CURLINFO_HTTP_CODE));
    curl_close($handle);
  
    if ($http_code >= 500) {
      // do not wat to DDOS server if something goes wrong
      sleep(5);
      return false;
    } else if ($http_code != 200) {
      $response = json_decode($response, true);
      error_log("Request has failed with error {$response['error_code']}: {$response['description']}\n");
      if ($http_code == 401) {
        throw new Exception('Invalid access token provided');
      }
      return false;
    } else {
      $response = json_decode($response, true);
      if (isset($response['description'])) {
        error_log("Request was successful: {$response['description']}\n");
      }
      $response = $response['result'];
    }
  
    return $response;
  } 
  
  function apiRequestJson($method, $parameters) {
      if (!is_string($method)) {
          error_log("Method name must be a string\n");
          return false;
      }
  
      if (!$parameters) {
          $parameters = array();
      } else if (!is_array($parameters)) {
          error_log("Parameters must be an array\n");
          return false;
      }
  
      $parameters["method"] = $method;
  
      $handle = curl_init(API_URL);
      curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 10);
      curl_setopt($handle, CURLOPT_TIMEOUT, 60);
      curl_setopt($handle, CURLOPT_POST, true);
      curl_setopt($handle, CURLOPT_POSTFIELDS, json_encode($parameters));
      curl_setopt($handle, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
  
      return exec_curl_request($handle);
  }
  
$content = file_get_contents("php://input");
$update = json_decode($content, true);

if (!$update) {
  // receive wrong update, must not happen
  exit;
}

if (isset($update["message"])) {
  processMessage($update["message"]);
} elseif(isset($update["callback_query"])){
	callbackMessage($update["callback_query"]);
}
?>