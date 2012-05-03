<?php

/* ARC2 static class inclusion */ 
include_once('arc/ARC2.php');
define('rdf_ns',  'http://www.w3.org/1999/02/22-rdf-syntax-ns#');
define('cs_ns', 'http://purl.org/vocab/changeset/schema#');
define('BAD_REQUEST', "HTTP/1.1 400 Bad Request");
define('STATUS_OK', 'HTTP/1.1 200 OK');
define('OK_NO_CONTENT', 'HTTP/1.1 204 No Content');
define('INTERNAL_ERROR', "HTTP/1.1 500 Internal Server Error");

function checkStoreName($storename){
  @include 'settings.php';

}

function getGraphName(){
  if(isset($_REQUEST['graph'])){
    $graphName = $_REQUEST['graph'];
  } else {
    $graphName = 'urn:default';
  }
}

function getStore($storename, $writable=false){

  StoreList::add_store($storename);

  /* configuration */ 
  $endpoint_features = array('select', 'construct', 'ask', 'describe');
  if($writable){
    $endpoint_features = array_merge($endpoint_features, array('load', 'insert', 'delete'));
  }
  $config = array(
    /* db */
      'db_host' => DB_HOST, /* optional, default is localhost */
      'db_name' => DB_NAME,
      'db_user' => DB_USER,
      'db_pwd' =>  DB_PWD,
      /* store name (= table prefix) */
      'store_name' => $storename,
    /* endpoint */
    'endpoint_features' =>  $endpoint_features ,
    'endpoint_timeout' => 60, /* not implemented in ARC2 preview */
    'endpoint_read_key' => '', /* optional */
//    'endpoint_write_key' => 'somekey', /* optional */
    'endpoint_max_limit' => 250, /* optional */
      
    );
    /* instantiation */
  $store = ARC2::getStoreEndpoint($config);

  return $store;
}

function dereifyStatementToNtriples($statement_properties){
  $rdf_index = array();
  $subject = $statement_properties[rdf_ns.'subject'][0]['value'];
  $predicate = $statement_properties[rdf_ns.'predicate'][0]['value'];
  $object = $statement_properties[rdf_ns.'object'][0];
  $rdf_index[$subject][$predicate] = array($object);
  $serialiser = ARC2::getNTriplesSerializer();
  return $serialiser->getSerializedIndex($rdf_index);
}

function process_changeset_request(){
  $storename = F3::get('PARAMS["store"]');
  $store = getStore($storename,'writable');
  if (!$store->isSetUp()) {
    $store->setUp();
    StoreList::add_store($storename);
  }
  
  $rdf_index = $store->toIndex(file_get_contents('php://input'),1);

  $triplesToInsert = '';
  $triplesToDelete = '';

  foreach($rdf_index as $s => $props){
    if(isset($props[cs_ns.'addition'])){
      foreach($props[cs_ns.'addition'] as $addition){
          $statement = $rdf_index[$addition['value']];
          $triplesToInsert.= dereifyStatementToNtriples($statement);
        }
    }
    if(isset($props[cs_ns.'removal'])){
      foreach($props[cs_ns.'removal'] as $removal){
          $statement = $rdf_index[$removal['value']];
          $triplesToDelete.= dereifyStatementToNtriples($statement);
      }
    }
  }

  $graphName = getGraphName();
  $result = $store->replace($triplesToDelete, $graphName, $triplesToInsert);
  $errors = $store->getErrors();
  if(empty($errors)){
    header(STATUS_OK);
    echo json_encode($result);
    exit;
  } else {
    header(INTERNAL_ERROR);
    echo json_encode($store->getErrors());
    exit;
  }
}


function update_graph($action){
  $storename = F3::get('PARAMS["store"]');
  $store = getStore($storename);
  if (!$store->isSetUp()) {
    $store->setUp();
  }

  $graphName = getGraphName();
    $rdf = file_get_contents('php://input');
    switch($action){
      case 'insert':
       $result = $store->insert($rdf, $graphName);
        break;
      case 'replace' :
        $result = array( 
          'deletingGraph' => $store->query('DELETE FROM <'.$graphName.'>'),
          'insertingGraph' => $store->insert($rdf, $graphName)
       );
        break;
      case 'delete' :
        $result = $store->delete($rdf, $graphName);
        break;
    }
    
    $errors = $store->getErrors();
    
    if(empty($errors)){
      header(STATUS_OK);
      echo json_encode($result);
    } else {
      header(BAD_REQUEST);
      echo json_encode(array($result, $store->getErrors()));
    }


}

function serveSparql() {
  /* initiates ARC's built in sparql endpoint */
  getStore(F3::get('PARAMS["store"]'))->go(); 
}


?>
