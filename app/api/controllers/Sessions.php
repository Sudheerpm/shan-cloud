<?php
  class Sessions{
    private static $api_key = null;

    // check headers and request for an API token.
    // if it exists, return it.
    public static function api_key(){
      // return a previously stored token if exists
      if(isset(self::$api_key)) return self::$api_key;
      $h = apache_request_headers();
      $reqKey = isset($_REQUEST["api-key"]) ? $_REQUEST["api-key"] : "";
      $api_key = isset($h["api-key"]) ? $h["api-key"] : $reqKey;
      // set the token for later use
      self::$api_key = $api_key;
      // return the token
      return self::$api_key; 
    }

    // Validate an API key.
    public static function validate(){
      $data = array(":api_key" => self::api_key());

      validate_input($data, array(
        ':api_key' => 'required|alpha_numeric|exact_len,32'
      ));

      $sql = "select case when expiry_utc > utc_timestamp() then 0 else 1 end is_expired from sessions where api_key = :api_key";
      $result = dbquery($sql, $data);
      $sessions = $result->fetchAll(PDO::FETCH_ASSOC);
      
      // should be exactly one result
      if(count($sessions)!=1) json_error("Invalid API key provided", 401);
      
      // check if the session has expired
      $s = $sessions[0];
      if((int)$s["is_expired"]) json_error("This API key is no longer valid", 403);

      // update the session's last activity
      $sql = "update sessions set last_activity_utc = utc_timestamp() where api_key = :api_key";
      dbquery($sql, $data);

      // session looks good, return all clear
      return 0;

    }


  }
?>