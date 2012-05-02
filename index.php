<?php
require 'fatfree/lib/base.php';
require 'rdfstore.php';
F3::route('GET /@store/services/sparql',
  function() {
    /* initiates ARC's built in sparql endpoint */
    getStore(F3::get('PARAMS["store"]'))->go(); 
  }
);

F3::route('GET /@store/meta', function(){
  if(isset($_GET['about'])){
    $_SERVER['QUERY_STRING'] = 'query='.urlencode("DESCRIBE <{$_GET['about']}>");
    getStore(F3::get('PARAMS["store"]'))->go(); 
  }
});

F3::route('POST /@store/meta/changesets', 'process_changeset_request');

F3::route('POST /@store/graphs', function(){
 update_graph('insert'); 
});
F3::route('PUT /@store/graphs', function(){
 update_graph('replace'); 
});
F3::route('DELETE /@store/graphs', function(){
 update_graph('delete'); 
});



F3::run();

?>
