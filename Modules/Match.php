<?php

use Symfony\Component\HttpFoundation\Response;

global $dbo;

$Silex->get('/match/category/add', function() use ($dbo){
    return 1;
});

$Silex->post('/match/delete/{type}/{id}', function($type, $id) use ($dbo){
    $table = ($type == 'param') ? 'sasa_match_core_parameters' : 'sasa_match_core_parameters_penalty';
    $delete = $dbo->setQuery("DELETE FROM {$table} WHERE id = '{$id}'")->execute();
    if(!$delete){
        return new Response(json_encode(["Error" => true, "Message" => "Error on server"]), 400);
    }
    return new Response(json_encode(["Error" => false, "Message" => "Data saved"]), 200);
});

$Silex->post('/match/update/{type}', function($type) use ($dbo){
    $table = ($type == 'param') ? 'sasa_match_core_parameters' : 'sasa_match_core_parameters_penalty';
    if($type == 'param'){
        
        $data = json_decode($_POST['parametro']);
        $data->criteria = json_encode($data->criteria);
        $update = $dbo->setQuery("UPDATE {$table}  SET title = '".$data->title."', description = '".$data->description."', criteria = '".$data->criteria."' WHERE id = '".$data->id."'")->execute();     
        
        if(!$update){
            return new Response(json_encode(["Error" => true, "Message" => "Error on server"]), 400);
        }
        return new Response(json_encode(["Error" => false, "Message" => "Data saved"]), 200);
    
    } else {
        $data = json_decode($_POST['parametro']);
        $data->data->data = json_encode($data->data);
        $update = $dbo->setQuery("UPDATE {$table}  SET title = '".$data->title."', data = '".$data->data->data."', points = '".$data->points."' WHERE id = '".$data->id."'")->execute();       
        
        if(!$update){
            return new Response(json_encode(["Error" => true, "Message" => "Error on server"]), 400);
        }
        return new Response(json_encode(["Error" => false, "Message" => "Data saved"]), 200);
    }
    
});

$Silex->post('/match/category/add', function() use ($dbo){
    $data = json_decode($_POST['newcategory']);
    $parametros = $data->params;
    $title = $data->title;
    $descr = $data->description;

    $create = new stdClass();
    $create->title = $title;
    $create->description = $descr;

    $insert = $dbo->insertObject('sasa_match_core_categories', $create);
    if(!$insert){
        return new Response(json_encode(["Error" => true, "Message" => "Error on server"]), 400);
    }

    $catid = $dbo->insertid();
    $error = 0;
    foreach($parametros as $para){
        $catpara = new stdClass();
        $catpara->categoria = intval($catid);
        $catpara->parametro = intval($para);
        $insert = $dbo->insertObject('sasa_match_core_categories_parameters', $catpara);
        if(!$insert){
            $error++;
        }
    }

    if($error > 0){
        return new Response(json_encode(["Error" => true, "Message" => "Error on server"]), 400);
    }

    return new Response(json_encode(["Error" => false, "Message" => "Data saved"]), 200);
});


// GRAND CATEGORY
$Silex->post('/match/grandcategory/add', function() use ($dbo){
    $data = json_decode($_POST['newcategory']);
    $categorias = $data->categories;
    $title = $data->title;
    $descr = $data->desc;

    $events = $data->events;

    


    $create = new stdClass();
    $create->title = $title;
    $create->data = json_encode(['description'=> $descr]);

    $insert = $dbo->insertObject('sasa_match_core_grandcategory', $create);
    if(!$insert){
        return new Response(json_encode(["Error" => true, "Message" => "Error on server"]), 400);
    }

    $catid = $dbo->insertid();
    $error = 0;
    foreach($categorias as $para){
        $catpara = new stdClass();
        $catpara->grandcategory_id = intval($catid);
        $catpara->category_id = intval($para);
        $insert = $dbo->insertObject('sasa_match_core_grandcategory_categories', $catpara);
        if(!$insert){
            $error++;
        }
    }

    foreach($events as $ev){
        $eventInsert = new stdClass();
        $eventInsert->grandcategory_id = intval($catid);
        $eventInsert->event_id = intval($ev);
        $insert = $dbo->insertObject('sasa_match_core_grandcategory_events',$eventInsert);
        if(!$insert){
            $error++;
        }

    }

    if($error > 0){
        return new Response(json_encode(["Error" => true, "Message" => "Error on server"]), 400);
    }

    return new Response(json_encode(["Error" => false, "Message" => "Data saved"]), 200);
});

$Silex->post('/match/category/update', function() use ($dbo){
    $data = json_decode($_POST['newcategory']);
    $parametros = $data->params;
    $title = $data->title;
    $descr = $data->description;
    $id    = $data->id;

    $create = new stdClass();
    $create->title = $title;
    $create->description = $descr;
    $create->id = $id;

    $insert = $dbo->updateObject('sasa_match_core_categories', $create, 'id', true);
    if(!$insert){
        return new Response(json_encode(["Error" => true, "Message" => "Error on server"]), 400);
    }

    $deleteToUpdate = $dbo->setQuery("DELETE FROM sasa_match_core_categories_parameters where categoria = '".$id."'")->execute();

    $catid = $id;
    $error = 0;
    foreach($parametros as $para){
        $catpara = new stdClass();
        $catpara->categoria = intval($catid);
        $catpara->parametro = intval($para);
        $insert = $dbo->insertObject('sasa_match_core_categories_parameters', $catpara);
        if(!$insert){
            $error++;
        }
    }

    if($error > 0){
        return new Response(json_encode(["Error" => true, "Message" => "Error on server"]), 400);
    }

    return new Response(json_encode(["Error" => false, "Message" => "Data saved"]), 200);
});

// GRAND CATEGORY
$Silex->post('/match/grandcategory/update', function() use ($dbo){
    $data = json_decode($_POST['newcategory']);
        
    $categories = $data->cats;
    $events = $data->events;
    $title = $data->title;
    $datajs = $data->data;
    $id    = $data->id;

    $create = new \stdClass();
    $create->title = $title;
    $create->data = $datajs;
    $create->id = $id;

    $insert = $dbo->updateObject('sasa_match_core_grandcategory', $create, 'id', true);
    if(!$insert){
        return new Response(json_encode(["Error" => true, "Message" => "Error on server"]), 400);
    }
    $sql = "DELETE FROM sasa_match_core_grandcategory_categories where grandcategory_id = ".$data->id;

    $deleteToUpdate = $dbo->setQuery($sql)->execute();


    $catid = $id;
    $error = 0;
    foreach($categories as $cat){
        $catpara = new stdClass();
        $catpara->grandcategory_id = intval($catid);
        $catpara->category_id = intval($cat);
        $insert = $dbo->insertObject('sasa_match_core_grandcategory_categories', $catpara);
        if(!$insert){
            $error++;
        }
    }

  //borrado de relaciones eventos
  $sqlevents = "DELETE FROM sasa_match_core_grandcategory_events where grandcategory_id = ".$data->id;

  $deleteToUpdateevents = $dbo->setQuery($sqlevents)->execute();

  $error = 0;

  foreach($events as $ev){
    $eventInsert = new stdClass();
    $eventInsert->grandcategory_id = intval($catid);
    $eventInsert->event_id = intval($ev);
    $insert = $dbo->insertObject('sasa_match_core_grandcategory_events',$eventInsert);
    if(!$insert){
        $error++;
    }

}





    if($error > 0){
        return new Response(json_encode(["Error" => true, "Message" => "Error on server"]), 400);
    }

    return new Response(json_encode(["Error" => false, "Message" => "Data saved"]), 200);
});

$Silex->post('/match/category/delete/{id}', function($id) use ($dbo){
    $delete = $dbo->setQuery("DELETE FROM sasa_match_core_categories WHERE id = '{$id}'")->execute();
    if(!$delete){
        return new Response(json_encode(["Error" => true, "Message" => "Error on server"]), 400);
    }

    return new Response(json_encode(["Error" => false, "Message" => "Data delete"]), 200);
});

$Silex->post('/match/grandcategory/delete/{id}', function($id) use ($dbo){
    $delete = $dbo->setQuery("DELETE FROM sasa_match_core_grandcategory WHERE id = '{$id}'")->execute();
    if(!$delete){
        return new Response(json_encode(["Error" => true, "Message" => "Error on server"]), 400);
    }

    return new Response(json_encode(["Error" => false, "Message" => "Data delete"]), 200);
});


$Silex->get('/match/category/list', function() use ($dbo){
   $pop = array();
   $categories = $dbo->setQuery("SELECT * FROM sasa_match_core_categories ORDER BY ID DESC")->loadObjectList();
   foreach($categories as $i => $cat){
       $cat->parameters = [];
       $parameters = $dbo->setQuery("SELECT PARAM.*
       FROM SASA_MATCH_CORE_PARAMETERS AS PARAM
       JOIN SASA_MATCH_CORE_CATEGORIES_PARAMETERS AS CATPAR ON CATPAR.PARAMETRO = PARAM.ID WHERE CATPAR.CATEGORIA = '{$cat->id}'")->loadObjectList();
       $parameterIds = array();
       foreach($parameters as $j => $par){
        $par->id = $par->id;
        $par->criterios = json_decode($par->criteria);
        $par->strcri = [];
        foreach($par->criterios as $cri){
            array_push($par->strcri, $cri->title . " (".$cri->points.")");
        }
        $par->strcri = implode(',', $par->strcri);
        $cat->parameters = $parameters;
        array_push($parameterIds, $par->id);
       }

       $cat->paramsid = $parameterIds;

       array_push($pop, $cat);
   }
    return new Response(json_encode(["Error" => false, "Message" => "Data saved", "Data" => $pop]), 200);
});

$Silex->get('/match/grandcategory/list', function() use ($dbo){
   $pop = array();
   $grandCategories = $dbo->setQuery("SELECT * FROM sasa_match_core_grandcategory ORDER BY ID DESC")->loadObjectList();
   foreach($grandCategories as $i => $grandcat){
       $grandcat->categories = [];
       $desc = json_decode($grandcat->data);
       $grandcat->description = $desc->description;
       
       $categories = $dbo->setQuery("SELECT CATS.*
       FROM SASA_MATCH_CORE_CATEGORIES AS CATS
       JOIN SASA_MATCH_CORE_GRANDCATEGORY_CATEGORIES AS GRANDCAT ON GRANDCAT.CATEGORY_ID = CATS.ID WHERE GRANDCAT.GRANDCATEGORY_ID = '{$grandcat->id}'")->loadObjectList();
       $categoriesIds = array();
       foreach($categories as $j => $cat){
           
        $grandcat->categories = $categories;
        array_push($categoriesIds, $cat->id);
       }
       $grandcat->categoriesIds = $categoriesIds;



       $events = $dbo->setQuery("SELECT art.id,art.title FROM sasa_content as art join sasa_match_core_grandcategory_events as grandcat on grandcat.event_id = art.id WHERE grandcat.grandcategory_id = '{$grandcat->id}'")->loadObjectList();

       $eventsIds = array();
       $grandcat->events = $events;
       foreach ($events as $f => $event){
        
        array_push($eventsIds,$event->id);
       }
       $grandcat->eventsIds = $eventsIds;

       

       array_push($pop, $grandcat);
   }
    return new Response(json_encode(["Error" => false, "Message" => "Data saved", "Data" => $pop]), 200);
});

