<?php

App::uses('CoHttpClient', 'Lib');

class DataverseHttpClient extends CoHttpClient {
  protected $thisConfig;
  protected $apiToken;
  protected $adminToken;

  public function __construct($config, $apiToken, $adminToken) {
    parent::__construct();

    $this->thisConfig = $config;
    $this->apiToken = $apiToken;
    $this->adminToken = $adminToken;

    $this->setConfig($config);

    $this->setRequestOptions(array(
        'header' => array(
          'Accept'          => 'application/json',
          'Content-Type'    => 'application/json; charset=UTF-8',
          'X-Dataverse-key' => $apiToken
        )
      ));
  }

  public function get($uri=null, $query=array(), $request=array()) {
    if(str_contains($uri, "api/admin")) {
      $token = $this->adminToken;
    } else {
      $token = $this->apiToken;
    }

    $query['unblock-key'] = $token;

    return parent::get($uri, $query, $request);
  }

  public function post($uri=null, $data=array(), $request=array()) {
    if(str_contains($uri, "api/admin")) {
      $token = $this->adminToken;
    } else {
      $token = $this->apiToken;
    }

    $url = $uri . "?unblock-key=" . $token;

    return parent::post($url, $data, $request);
  }

  public function put($uri=null, $query=array(), $request=array()) {
    if(str_contains($uri, "api/admin")) {
      $token = $this->adminToken;
    } else {
      $token = $this->apiToken;
    }

    $query['unblock-key'] = $token;

    return parent::put($uri, $query, $request);
  }

  public function delete($uri=null, $query=array(), $request=array()) {
    if(str_contains($uri, "api/admin")) {
      $token = $this->adminToken;
    } else {
      $token = $this->apiToken;
    }

    $query['unblock-key'] = $token;

    return parent::delete($uri, $query, $request);
  }
}
