<?php

/* ARC2 static class inclusion */ 
include_once('arc/ARC2.php');
define('rdf_ns',  'http://www.w3.org/1999/02/22-rdf-syntax-ns#');
define('cs_ns', 'http://purl.org/vocab/changeset/schema#');

function getStore($storename, $writable=false){
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
    'endpoint_features' =>  $endpoint_features ),
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
  $subject = $statement_properties[rdf_ns.'subject'][0];['value']
  $predicate = $statement_properties[rdf_ns.'predicate'][0]['value'];
  $object = $statement_properties[rdf_ns.'object'][0];
  $rdf_index[$subject][$predicate] = array($object);
  $serialiser = ARC2::getNTriplesSerializer();
  return $serialiser->getSerializedIndex($rdf_index);
}

function process_changeset_request(){
  $store = getStore(F3::get('PARAMS["store"]'),'writable');
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

  $store->replace($triplesToDelete, 'urn:defaultGraph', $triplesToInsert);
  
  if(empty($store->getErrors())){
    header("HTTP/1.1 204 No Content");
    exit;
  } else {
    header("HTTP/1.1 500 Internal Server Error");
    echo implode("\n", $store->getErrors());
    exit;
  }
}



?>
