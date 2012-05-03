<?php

class StoreList {
  
  public static $file = 'stores-list.json';

  static function add_store($storename){
      $stores = json_decode(file_get_contents(self::$file), 1);
      $stores[$storename]=getStoreUri();
      file_put_contents(self::$file,json_encode($stores));
  }
  static function remove_store($storename){
      $stores = json_decode(file_get_contents(self::$file), 1);
      unset($stores[$storename]);
      file_put_contents(self::$file,json_encode($stores));
  }
}

?>
