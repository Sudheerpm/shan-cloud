<?php
  
  require_once("settings.php");
  require_once("db.php");

  function json_ok($data, $rc=200){
    header('Content-Type: application/json');
    http_response_code($rc);
    echo json_encode($data);
    exit;
  }
  
  function json_error($message, $rc=500){
    global $_SETTINGS;
    error_log("Error $rc: ".json_encode($message));
    header('Content-Type: application/json');
    http_response_code($rc);
    if($_SETTINGS["display_errors"]){
      echo json_encode($message);
    }
    exit;
  }

  function validate_input($data, $rules){
    
    $v = GUMP::is_valid($data, $rules);

    if($v !== true) json_error($v, 400);
  }

?>