<?php

  class Common{
    public static function encrypt($data){
      //openssl req -x509  -days 365 -newkey rsa:2048 -keyout private.pem -out public.pem -nodes
      $fp = fopen("public.pem", "r");
      $cert = fread($fp, 8192);
      fclose($fp);
      $pk1 = openssl_get_publickey($cert);
      //json_error($pk1);
      //$data_handle = fopen($filename,"r");
      //$data = fread($data_handle, filesize($filename));
      //fclose($data_handle);
      //$d = fread($data);

      openssl_seal($data, $sealed, $ekeys, array($pk1));
      openssl_free_key($pk1);

      // return encrypted data and envelope key
      return array(
        "data" => $sealed,
        "ekey" => $ekeys[0]
      );

      //$data_handle = fopen($filename,"w");
      //fwrite($data_handle, $sealed);
      //fclose($data_handle);
    }

    public static function decrypt($encrypted_data, $ekey){
      
      $fp = fopen("private.pem", "r");
      $cert = fread($fp, 8192);
      fclose($fp);
      
      $pk1 = openssl_get_privatekey($cert);
      if (openssl_open($encrypted_data, $decrypted_data, $ekey, $pk1)) {
        return $decrypted_data;
        //$data_handle = fopen("$filename.dec","w");
        //fwrite($data_handle, $decrypted_data);
        //fclose($data_handle);
        //$open;
      } else {
        throw new Exception("Failed to decrypt data");
      }
      
    }
  }

?>