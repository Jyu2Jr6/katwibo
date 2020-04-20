<?php

  function dbConnect() {
    define('DSN', 'mysql:host=mysql8050.xserver.jp;dbname=jyu2_katwibo;charset=utf8');
    define('USER', 'jyu2_katwibo');
    define('PASSWORD', 'Jr633062511');
    try {
      $dbh = new PDO(DSN, USER, PASSWORD);
      $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
      echo 'データベース接続失敗：' . $e->getMessage() . '<br>';
    }
  
    return $dbh;    
  }

  function dbClose($dbh) {
    $dbh = null;
  }

?>