<?php

/* ARC2 static class inclusion */ 
include_once('arc/ARC2.php');

function getStore($storename){
  /* configuration */ 
  $config = array(
    /* db */
      'db_host' => DB_HOST, /* optional, default is localhost */
      'db_name' => DB_NAME,
      'db_user' => DB_USER,
      'db_pwd' =>  DB_PWD,
      /* store name (= table prefix) */
      'store_name' => $storename,
    );
    /* instantiation */
  $store = ARC2::getStore($config);
  if (!$store->isSetUp()) {
    $store->setUp();
  }
    return $store;
}

?>
