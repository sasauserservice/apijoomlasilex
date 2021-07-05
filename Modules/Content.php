<?php
use Symfony\Component\HttpFoundation\Response;

global $dbo;

$Silex->mount('/content', function($content) use ($dbo){
  $content->get('/', function() use ($dbo){
    var_dump($dbo);
    return 1;
  });

  $content->post('/update/{id}', function($id) use ($dbo){
    $request_body = file_get_contents('php://input');
    $data = json_decode($request_body);
    $articulo = new stdClass;
    $articulo->id = $id;
    $articulo->fulltext = $data;
    $update = $dbo->updateObject('#__content', $articulo, 'id', true);

    if( !$update ){
      return new Response(json_encode(["Error" => true, "Message" => "User no found", "Status" => 400]), 400);
    }

    return new Response(json_encode(["Error" => false, "Message" => "Content update", "Status" => 200]), 200);
  });
});
