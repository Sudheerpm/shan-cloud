<?php
  if(!isset($DBCONNECTED) || $DBCONNECTED != 'YES'){
     // Connect to the database
     $GLOBALS['dbh'] = new PDO("mysql:host={$_SETTINGS["dbhost"]};dbname={$_SETTINGS["dbschema"]}",
        $_SETTINGS["dbuser"],
        $_SETTINGS["dbpassword"]);
     $dbh->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true); 
     $query = "set character set utf8";
     $result = $dbh->query($query);
     $DBCONNECTED='YES';
  } 

  
  function dbquery($sql, $bvs = array()){
    global $dbh;
    $sth = $dbh->prepare($sql);
    if(!$sth) throw new Exception("DB parse error: ".dberror($dbh)); 
    $result = $sth->execute($bvs);
    if(!$result) throw new Exception("DB execute error: ".dberror($sth));
    return $sth;
  }

  function dberror($pdo){
    $err = $pdo->errorInfo();
    if(!$err) return null;
    return $err[2];
  }

  function dblastinsertid(){
    global $dbh;
    return $dbh->lastInsertId();
  }

?>