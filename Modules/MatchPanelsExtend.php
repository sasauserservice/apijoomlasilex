<?php
use Symfony\Component\HttpFoundation\Response;
use Helper\Matchs;
use Helper\Extended;
/**Create panel**/
$matchpanel->post('/create', function() use ($dbo){

    $data = json_decode($_POST['form']);
    
    $mainjudge = [
        [
            "user" => $data->mainjudge //Matchs::getIdFromEmail($data->mainjudge)
        ]
    ];

    $generalJugde = array();
    $penaltyJugde = array(); 

    foreach($data->generaljudge as $i => $gen){
        array_push($generalJugde, [
            "id" => $gen->id,
            "general" => $gen->user, //Matchs::getIdFromEmail($gen->user),
            "params" => $gen->params
        ]);
    }
    
    foreach($data->penaltyjudge as $i => $gen){
        array_push($penaltyJugde, [
            "id" => $gen->id,
            "penalty" => $gen->user, //Matchs::getIdFromEmail($gen->user),
            "params" => $gen->penalties
        ]);
    }

    ////***VERIFICACION DE CATEGORIAS***////
    if($data->event){
        $categoriesOfEvents = Matchs::getCatsEventsSpecified($data->event);
        $cats = array_map(function($cat){
            return $cat->id;
        }, $categoriesOfEvents);

        $result = false;
        
        foreach($cats as $op){
            if($result == false):
                $index = array_search($op, $data->categories);
                if(is_bool($index)== false){
                    $result = true;
                }
            endif;
        }
        
        if($result == true){
            return new Response(json_encode(["Message" => $result]), 403);
        }

        $catscurrents = Matchs::getCatsFromEvent($data->event);

        if( count($catscurrents) > 0 ){
            $categoryDiff = array_diff($catscurrents, $data->categories);
            if(count($categoryDiff) == 0){
                return new Response(json_encode(["Categories exists on event"]), 402);
            }

            $data->categories = $categoryDiff;
        }
    }
    
    ////***VERIFICACION DE CATEGORIAS***////
    
    
    $insert = new stdClass;
    $insert->title = $data->title;
    $insert->mainjudge = json_encode($mainjudge);
    $insert->generaljudge = json_encode($generalJugde);
    $insert->penaltyjudge = json_encode($penaltyJugde);
    $insert->createdby = 1;
    $insert->serialid = generate_string(15);

    $create = $dbo->insertObject('sasa_match_core_panels', $insert);
    if(!$create){
        return new Response(json_encode(["Message" => "Error on create panel"]), 400);
    }
    $error = 0;
    $panel = $dbo->insertid();
    foreach($data->categories as $cat){
        $new = new stdClass;
        $new->panelid = $panel;
        $new->categoryid = intval($cat);
        $new->category_serial = Helper\get_serial('sasa_match_core_categories', $cat);$new->panel_serial = Helper\get_serial('sasa_match_core_panels', $panel);
        $er = $dbo->insertObject('sasa_match_core_panels_categories', $new);
        if(!$er){
            $error++;
        }
    }

    if($error > 0){
        return new Response(json_encode(["Message" => "Error Set categories"]), 401);
    }

    if($data->event){
        $new = new stdClass;
        $new->event = $data->event;
        $new->panel = $panel;
        $new->panel_serial = Helper\get_serial("sasa_match_core_panels", $panel);
        $new->event_serial = Helper\get_serial("sasa_content", $data->event);
        $i = $dbo->insertObject('sasa_match_core_panels_events', $new);
        if(!$i){
            return new Response(json_encode(["Message" => "Error on set to event"]), 402);
        }
    }

    return new Response(json_encode(["Message" => "Panel saved"]), 200);
});

?>