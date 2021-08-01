<?php

use Symfony\Component\HttpFoundation\Response;
use Helper\Matchs;
use Helper\Extended;

global $dbo;

$Silex->mount('/matchpanel', function($matchpanel) use($dbo) {

    require_once('./Modules/MatchPanelsExtend.php');

    /**Listing events**/
    $matchpanel->get('/events', function() use($dbo){
        $categoria = Matchs::getCatId();
        $query = "SELECT id, title as text FROM #__content WHERE catid='{$categoria}'";
        return new Response(json_encode($dbo->setQuery($query)->loadObjectList()), 200);
    });
    /**Listing judgets**/
    $matchpanel->get('/judges', function() use($dbo){
        $judges = Matchs::getJudges();
        return new Response(json_encode($judges), 200);
    });
    /**Listing cats**/
    $matchpanel->get('/categories', function() use($dbo){
        $categories = Matchs::getCategories();
        return new Response(json_encode($categories), 200);
    });
    /**Listing params by cats**/
    $matchpanel->get('/params/{group}', function($group) use($dbo){
        $categories = Matchs::getParams(json_decode($group));
        return new Response(json_encode($categories), 200);
    });
    /**Listing penalties**/
    $matchpanel->get('/penalties', function() use($dbo){
        $categories = Matchs::getPenalties();
        return new Response(json_encode($categories), 200);
    });
    /**Listing events**/
    $matchpanel->get('/events', function() use($dbo){
        $categoria = Matchs::getCatId();
        $query = "SELECT id, title as text FROM #__content WHERE catid='{$categoria}'";
        return new Response(json_encode($dbo->setQuery($query)->loadObjectList()), 200);
    });

    /**Update panel**/
    $matchpanel->post('/update', function() use ($dbo){
        $data = json_decode($_REQUEST['form']);

        $mainjudge = [
            ["user" => $data->mainjudge]
        ];

        $generalJugde = array();
        $penaltyJugde = array();

        foreach($data->generaljudge as $i => $gen){
            array_push($generalJugde, [
                "id" => $gen->id,
                "general" => $gen->user,
                "params" => $gen->params
            ]);
        }
        
        foreach($data->penaltyjudge as $i => $gen){
            array_push($penaltyJugde, [
                "id" => $gen->id,
                "penalty" => $gen->user,
                "params" => $gen->penalties
            ]);
        }
        
        
        $update = new \stdClass;
        $update->id = $data->objectEdit->id;
        $update->title = $data->objectEdit->name;
        $update->mainjudge = json_encode($mainjudge);
        $update->generaljudge = json_encode($generalJugde);
        $update->penaltyjudge = json_encode($penaltyJugde);

        $result = $dbo->updateObject('sasa_match_core_panels', $update, 'id', true);

        if(!$result){
            return new Response(json_encode(["Message" => "Error on update panel"]), 400);
        }

        if($data->objectEdit->event_id){

            $categoriesOfEvents = Matchs::getCatsByEventAndPanel($data->objectEdit->event_id, $update->id);
            
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
        }

        $deleteBefore = $dbo->setQuery("DELETE FROM sasa_match_core_panels_categories WHERE panelid='{$data->objectEdit->id}'")->execute();

        $error = 0;
        $panel = $data->objectEdit->id;
        foreach($data->categories as $cat){
            $new = new \stdClass;
            $new->panelid = $panel;
            $new->categoryid = intval($cat);
            $new->category_serial = Helper\get_serial('sasa_match_core_categories', $cat);
            $new->panel_serial = Helper\get_serial('sasa_match_core_panels', $panel);
            $er = $dbo->insertObject('sasa_match_core_panels_categories', $new);
            if(!$er){
                $error++;
            }
        }

        if($data->objectEdit->event_id){
            $new = new \stdClass;
            $new->event = $data->objectEdit->event_id;
            $new->panel = $panel;
            $record = Matchs::savePanelWithEvent($new->event, $new->panel);
            
            if(!$record){
                return new Response(json_encode(["Message" => "Error on set to event"]), 402);
            }
        } else {
            $record = Matchs::deletePanelWithEvent($panel);
            if(!$record){
                return new Response(json_encode(["Message" => "Error on designed"]), 402);
            }
        }

        return new Response(json_encode(["Message" => "Panel update"]), 200);
    });

    /**Listing data from paness**/
    $matchpanel->get('/list', function() use($dbo) {
        $panels = $dbo->setQuery("SELECT p.*, TO_CHAR(p.created::DATE, 'Mon dd, yyyy') AS createdDate FROM sasa_match_core_panels as p")->loadObjectList();
        $newResult = array();
        foreach($panels as $i => $panel){

            
            $parametersTotals = Matchs::parameterFromPanel($panel->id);
            $parametersAssign = Matchs::parameterAssinedFromPanel($panel->id);
            $diference        = array_diff($parametersTotals, $parametersAssign);
            $status = false;

            if( count($parametersTotals) > 0 ){
                if( count($diference) > 0){
                    $status = false;
                } else {
                    $status = true;
                }
            }

            $append = new stdClass();
            $append->id = $panel->id;

            $append->statusA = $status;
            $jsonmain = json_decode($panel->mainjudge);
            $arraymain = array();
            foreach($jsonmain as $k => $gen){
                $genA   = (array) $gen;
                $useridserial = $genA['user'];
                $userid = (int) $genA['user'];
                $user = Extended::getUserById($userid);
                array_push($arraymain, [
                    "slot" => $k,
                    "user" => [
                        "iduser"   => $useridserial,
                        "email" => $user['email'],
                    ]
                ]);
            }

            $jsongeneral = json_decode($panel->generaljudge);
            $arraygeneral = array();
            $paramSel = array();
            $generalToEdit = array();
            foreach($jsongeneral as $j => $gen){
                $genA   = (array) $gen;
                $useridserial = $genA['general'];
                $userid = (int) $genA['general'];
                $user = Extended::getUserById($userid);
                array_push($arraygeneral, [
                    "id" => $genA['id'],
                    "slot" => $j,
                    "user" => [
                        "iduser"   => $useridserial,
                        "email" => $user['email'],
                        "params" => $genA['params']
                    ]
                ]);

                array_push($generalToEdit, [
                    "id" => $genA['id'],
                    "user" => $useridserial,
                    "email" => $user['email'],
                    "params" => $genA['params']
                ]);

                foreach($genA['params'] as $pen){
                    array_push($paramSel, $pen->id);
                }
            }

            $jsonpenalty = json_decode($panel->penaltyjudge);
            //var_dump($jsonpenalty);
            $arraypenalty = array();
            $penaltiesSel = array();

            $penaltiesToEdit = array();

            foreach($jsonpenalty as $j => $gen){
                $genA   = (array) $gen;
                $useridserial =  $genA['penalty'];
                //var_dump($useridserial);
                
                $userid = (int) $genA['penalty']; 
                $user = Extended::getUserById($userid);
                array_push($arraypenalty, [
                    "id" => $genA['id'],
                    "slot" => $j,
                    "user" => [
                        "iduser"   => $useridserial,
                        "email" =>  $user['email'],
                        "params" => $genA['params']
                    ]
                ]);

                array_push($penaltiesToEdit, [
                    "id" => $genA['id'],
                    "user" => $useridserial,
                    "email" => $user['email'],
                    "penalties" => $genA['params']
                ]);

                foreach($genA['params'] as $pen){
                    array_push($penaltiesSel, $pen->id);
                }
            }
            
            $queryParams = <<<SQL
            SELECT DISTINCT EACH_PARAMS ->> 'id' paramenterid, EACH_PARAMS ->> 'title' parametertitle FROM SASA_MATCH_CORE_PANELS PAN CROSS JOIN JSON_ARRAY_ELEMENTS(GENERALJUDGE) EACH_SECTION CROSS JOIN JSON_ARRAY_ELEMENTS( EACH_SECTION->'params' ) EACH_PARAMS LEFT JOIN SASA_USERS USR ON USR.ID = (EACH_SECTION ->> 'general')::int LEFT JOIN SASA_MATCH_CORE_PANELS_EVENTS AS PANEVENT ON PANEVENT.panel = PAN.id WHERE PAN.id = '{$panel->id}'
            SQL;
            $parametersByPanel = $dbo->setQuery($queryParams)->loadObjectList();
            
            $queryPenaltis = <<<SQL
            SELECT DISTINCT (EACH_PENALTY->>'id') penaltyid, (EACH_PENALTY->>'text') penaltytitle FROM SASA_MATCH_CORE_PANELS PAN CROSS JOIN JSON_ARRAY_ELEMENTS(PENALTYJUDGE) EACH_SECTION CROSS JOIN JSON_ARRAY_ELEMENTS(EACH_SECTION->'params') EACH_PENALTY LEFT JOIN SASA_USERS USR ON USR.ID = (EACH_SECTION ->> 'penalty')::int LEFT JOIN SASA_MATCH_CORE_PANELS_EVENTS AS PANEVENT ON PANEVENT.panel = PAN.id WHERE PAN.id = '{$panel->id}'
            SQL;
            $penaltiesByPanel = $dbo->setQuery($queryPenaltis)->loadObjectList();

            $queryEvents = <<<SQL
            SELECT ev.title,ev.id FROM  sasa_content as ev LEFT JOIN sasa_match_core_panels_events as panev ON panev.event = ev.id WHERE panev.panel = {$panel->id}
            SQL;
            
            $eventsByPanel = $dbo->setQuery($queryEvents)->loadObjectList();
            $append->events =$eventsByPanel;
            

            $append->name = $panel->title;
            $append->serial = $panel->serialid;
            $append->created = $panel->createddate;
            $append->categories = $dbo->setQuery("SELECT cat.id, cat.title FROM sasa_match_core_panels_categories as pacat left join sasa_match_core_categories as cat on cat.id = pacat.categoryid where panelid = '".$panel->id."'")->loadObjectList();
            $append->judges = [
                "main" => $arraymain,
                "general" => $arraygeneral,
                "penalty" => $arraypenalty
            ];            

            $append->params = $parametersByPanel;
            $append->penalties = $penaltiesByPanel;

            $append->event_id = $dbo->setQuery("SELECT event from #__match_core_panels_events where panel = '{$panel->id}'")->loadResult();

            $append->toedit = new stdClass();

            $append->toedit->selectedCategories = [];
            foreach($append->categories as $cat){
                array_push($append->toedit->selectedCategories, $cat->id);
            }

            $parameetes = Matchs::getParams($append->toedit->selectedCategories);
            $append->toedit->allparameters = [];
            foreach($parameetes as $par){
                array_push($append->toedit->allparameters, $par->id);
            }

            if((is_array($append->toedit->selectedCategories) == false) || (count($append->toedit->selectedCategories) == 0)){
                return new Response(json_encode([]));
            }
    
            $select = "SELECT mpar.id, mpar.title FROM #__match_core_categories_parameters as mcatpar Left Join #__match_core_parameters as mpar on mpar.id = mcatpar.parametro WHERE ";
    
            for($i=0; $i<count($append->toedit->selectedCategories); $i++){
                $select .= " categoria = '".$append->toedit->selectedCategories[$i]."' OR ";
            }
    
            $sqlRepair = substr($select, 0,  -3);
    
            $sqlRepair .= " group by mpar.id";
    
            $allparemetersToedit = $dbo->setQuery($sqlRepair)->loadObjectList();
          
            $append->toedit->allparameters = $allparemetersToedit;
            $append->toedit->allpenalties = $dbo->setQuery("SELECT id, title as text FROM #__match_core_parameters_penalty")->loadObjectList();
            $append->toedit->selectedPenalties = $penaltiesSel;
            $append->toedit->selectedParameters = $paramSel;
            $append->toedit->selectedJudgePenalties = $penaltiesToEdit;
            $append->toedit->selectedJudgeGeneral = $generalToEdit;
            
            array_push($newResult, $append);
        }
        
        return json_encode($newResult);
    });

    $matchpanel->post('/delete/{panel}', function($panel) use ($dbo){
        $record = Matchs::deleteChildsFromPanel($panel);
        if(!$record){
            $delete = $dbo->setQuery("DELETE FROM sasa_match_core_panels WHERE id = '{$panel}'")->execute();
            if($delete){
                return new Response(json_encode(["Message" => "Delete successful"]), 200);
            }
        }

        return new Response(json_encode(["Message" => "Delete Error"]), 400);
    });

    /**ASIGNATOR EVENTS PANELS VS**/
    $matchpanel->get('/eventslist', function() use($dbo){
        $events = Matchs::getEvents();
        return new Response(json_encode($events), 200);
    });

    $matchpanel->get('/panels/not/assigned/{event}', function($event) use ($dbo){
        $todos = Matchs::getPanelsAll();
        $panels = Matchs::getPanelsNotAssigned($event);
        $panelsA = Matchs::getPanelsAssigned($event);
        $paneslAb = array();
        foreach ($panelsA as $p){
            array_push($paneslAb, $p->id);
        }
        return new Response(json_encode(["noasignados" => $panels, "asignados" => $panelsA, "asignadosId" => $paneslAb, "all" => $todos]), 200);
    });

    $matchpanel->post('/panels/settoevent', function() use($dbo){
        $data = json_decode($_POST['form']);
        $response = Matchs::sendPanelToEvent($data->event, $data->panels);
        if(!$response){
            return new Response(json_encode(["Error"]), 400);
        }

        return new Response(json_encode(["Saved"]), 200);
    });
});


?>