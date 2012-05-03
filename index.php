<?php
require 'fatfree/lib/base.php';
require 'storelist.php';
require 'rdfstore.php';
require 'settings.php';

function getStoreUri(){
  $storename = getStoreName();
  return "http://{$_SERVER['SERVER_NAME']}/stores/{$storename}";  
}

function getStoreName(){
  return $storename = F3::get('PARAMS["store"]');
}


F3::route('GET /', function(){
  if(!$json = file_get_contents('stores-list.json')){
    $json = '[]';
  }
    header('Content-type: application/json');
    echo $json;
});
F3::route('GET /@store', function(){
  $base = getStoreUri();
  header('Content-type: application/json');
    echo json_encode(array(
      'sparqlEndpoint' => $base."/services/sparql",
      'changesetEndpoint' => $base."/meta/changesets",
      'graphsEndpoint' => $base.'/meta',
    ));
});

F3::route('DELETE /@store', function(){
  $storename = getStoreName();
  getStore($storename)->drop();
  header(OK_NO_CONTENT);
});
F3::route('GET /@store/services/sparql','serveSparql');
F3::route('POST /@store/services/sparql','serveSparql');

F3::route('GET /@store/meta', function(){
  if(isset($_GET['about'])){
    $query = "DESCRIBE <{$_GET['about']}>";
  } else {
    $query = "SELECT DISTINCT ?graph WHERE { GRAPH ?graph { ?s ?p ?o }  }";
  }
    $_SERVER['QUERY_STRING'] = '?query='.urlencode($query);
    $_GET['query'] = $query;
    $_POST['query'] = $query;
    $storename = getStoreName();
    getStore($storename)->go(); 
});

F3::route('POST /@store/meta/changesets', 'process_changeset_request');

F3::route('POST /@store/meta', function(){
  if(isset($_SERVER['CONTENT_TYPE']) AND strstr($_SERVER['CONTENT_TYPE'], 'application/vnd.talis.changeset')){
    process_changeset_request();
  } else {
    update_graph('insert'); 
  }
});
F3::route('PUT /@store/meta', function(){
 update_graph('replace'); 
});
F3::route('DELETE /@store/meta', function(){
 update_graph('delete'); 
});


F3::run();
?>
