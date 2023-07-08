<?php
  //
  require("lib/common.php");  
  $f3 = require("f3/lib/base.php");
  $f3->set('DEBUG',3);
  $f3->set('ONERROR', function($f3){
    $e = $f3->get("ERROR");
    if(isset($_SERVER["HTTP_WARNING"]) && $_SERVER["HTTP_WARNING"] == "NO_TRACE") $e["trace"] = array();
    json_error($e["text"], $e["code"], $e["trace"]);
  });

  $f3->set('AUTOLOAD','models/; controllers/');
  $f3->set('UPLOADS', 'uploads/');
  
  
  if(in_array($_SERVER["REQUEST_METHOD"] , array("POST", "PUT"))){
    if(!isset($_SERVER["CONTENT_TYPE"]) || strpos(strtolower(trim($_SERVER["CONTENT_TYPE"])),"application/json") ===0){ 
      //json_error("Content type must be application/json");
      //}
      $json = file_get_contents('php://input');
      $obj = json_decode($json, true);
      if($obj){
        foreach($obj as $k => $v){
          $f3->set("REQUEST.$k", $v);
          $_REQUEST[$k] = $v;
        }
      }
    }
  }

  /**
   * @apiDefine Auth
   * @apiHeader {String} api-key Authorisation token for API access
   */
  
  //json_error($f3->get("REQUEST"));
  require_once("vendor/gump.php");
  require_once("controllers/Requests.php"); 
  require_once("controllers/Sessions.php"); 
  require_once("controllers/Clickthroughs.php"); 


  $f3->route("GET /test",function($f3){
    json_error("I'm a teapot", 418);
    //echo "Rewrite is working"; exit;
  });

  $f3->route("GET /version", function(){
    global $_SETTINGS;
    // get version from file
    $version = null;
    if(file_exists("../version.txt")){
      $version = trim(file_get_contents("../version.txt"));
    };

    json_ok(array("version" => $version, "env" => $_SETTINGS["environment"]));
  });

  // Run fat free framework
  $f3->run();

?>
