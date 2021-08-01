<?php
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Helper\Extended;
use Helper\Matchs;
use Helper\Claims;
use Helper\Status;
use Helper\AuthHelper;
use Model\Auth;


$juzging->get('/tools/{participation}', function($participation) use ($dbo, $database){
    /**INITIAL INFO OF PARTICIPATION**/
    $query_participation = <<<SQL
    SELECT id, category_id, created_by, data->>'video_url' video, partipant_id, event_id FROM SASA_MATCH_MODULES_PARTICIPATIONS WHERE ID = '{$participation}'; 
    SQL;
    $findPart = $database->query($query_participation);
    if(is_null($findPart)){
        return new Response(json_encode(["NOT FOUND"]), 404);
    }
    $findPart = $findPart->fetch(PDO::FETCH_ASSOC);

    $query_parameters = <<<SQL
    SELECT
    PAR.id AS parametro_id,
    PAR.title AS parametro_name,
    PAR.description AS parametro_desc,
    PAR.criteria AS parametro_criteria
    FROM SASA_MATCH_CORE_CATEGORIES_PARAMETERS AS CATPAR
    LEFT JOIN SASA_MATCH_CORE_PARAMETERS AS PAR
    ON PAR.id = CATPAR.parametro
    LEFT JOIN SASA_MATCH_CORE_PANELS_CATEGORIES AS PCAT
    ON PCAT.categoryid = CATPAR.categoria
    LEFT JOIN SASA_MATCH_CORE_PANELS_EVENTS AS PEVENT 
    ON PEVENT.PANEL = PCAT.PANELID
    LEFT JOIN SASA_MATCH_CORE_PANELS AS PANEL
    ON PANEL.id = PCAT.panelid
    CROSS JOIN JSON_ARRAY_ELEMENTS(PANEL.generaljudge) AS EACH_GENERALJUDGE
    CROSS JOIN JSON_ARRAY_ELEMENTS(EACH_GENERALJUDGE->'params') AS EACH_PARAMS
    CROSS JOIN JSON_ARRAY_ELEMENTS(PAR.criteria) EACH_CRITERIA
    WHERE CATPAR.CATEGORIA = '{$findPart["category_id"]}' AND PEVENT.EVENT = '{$findPart["event_id"]}'
    GROUP BY PAR.id
    SQL;
    $findParameters = $dbo->setQuery($query_parameters)->loadObjectList();
    
    $query_penalty = <<<SQL
    SELECT
    DISTINCT PENALTIES.id penalty_id,
    PENALTIES.title penalty_name,
    PENALTIES.data->>'desc' penalty_desc,
    PENALTIES.points::int penalty_points
    FROM SASA_MATCH_CORE_PANELS_CATEGORIES PCAT
    LEFT JOIN SASA_MATCH_CORE_PANELS_EVENTS PEVENT ON PEVENT.PANEL = PCAT.PANELID
    LEFT JOIN SASA_MATCH_CORE_PANELS PANEL ON PANEL.id = PCAT.panelid
    CROSS JOIN JSON_ARRAY_ELEMENTS(PANEL.penaltyjudge) AS EACH_PENALTYJUDGE
    CROSS JOIN JSON_ARRAY_ELEMENTS(EACH_PENALTYJUDGE->'params') EACH_PENALS
    LEFT JOIN SASA_MATCH_CORE_PARAMETERS_PENALTY AS PENALTIES ON PENALTIES.id = (EACH_PENALS->>'id')::int
    WHERE PCAT.CATEGORYID = '{$findPart["category_id"]}'
    AND PEVENT.EVENT = '{$findPart["event_id"]}'
    SQL;

    $findPenalties = $dbo->setQuery($query_penalty)->loadObjectList();

    //var_dump($findPenalties);

    /*********FIND STATUS PARAMS*********/
    $statusToParams = Status::getStatus(1, $findPart['id']);
    /*********FIND STATUS PARAMS*********/
    
    /*********FIND STATUS PENALTY*********/
    $statusToPenal = Status::getStatus(2, $findPart['id']);
    /*********FIND STATUS PENALTY*********/


    /**VARIABLES DE RETORNO**/
    $parametros = array();
    $penalties  = array();
    /**VARIABLES DE RETORNO**/

    foreach($findParameters as $i => $par){
        $crit = json_decode($par->parametro_criteria);
        $total = 0;
        foreach($crit as $e){
            $total = $total + $e->points;
        }
        $crit = json_decode($par->parametro_criteria);
        $add = new stdClass();
        $add->id = $par->parametro_id;
        $add->name = $par->parametro_name;
        $add->criteria = json_decode($par->parametro_criteria);
        $add->total = $total;
        #Sino existe un registro... sera 0
        if($statusToParams == false){
            $add->flag  = 0;
        } else {
            #Si existe debe proveerse...
            $jsonStd = json_decode($statusToParams['data']);
            
            foreach($jsonStd as $st){
                if($st->id == $add->id){
                    $add->flag = $st->flag;
                }
            }
        }
        array_push($parametros, $add);
    }
    foreach($findPenalties as $i => $par){
        $add = new stdClass();
        $add->id = $par->penalty_id;
        $add->name = $par->penalty_name;
        $add->points = $par->penalty_points;
        if($statusToPenal == false){
            $add->flag = 2;
        } else {
            #Si existe debe proveerse...
            $jsonStd = json_decode($statusToPenal['data']);

            

            forEach($jsonStd as $k => $penalSt){
                if($penalSt->id == $add->id){
                    if(!$add->flag){
                        $add->flag = $penalSt->flag;
                    }
                }
            }
            
        }
        
        array_push($penalties, $add);
    }
    $findexistintQ = "select gc.data from sasa_match_core_score_general gc where entry = '{$participation}';";

    $findExistingQP = "select gc.data from sasa_match_core_score_penalty gc where entry = '{$participation}';";

    $result = $database->query($findexistintQ);
    $result->setFetchMode(PDO::FETCH_ASSOC);
    $fetch = $result->fetch();
    
    if($fetch == false){
        $generalParams = $parametros;
        $toGeneralPoints = 0;
    } else {
        $generalParams = array();
        $fetchParams = json_decode($fetch['data']);
        $toGeneralPoints = 0;
        foreach($parametros as $per){
            $nf = $per;
            foreach($fetchParams as $ft){
                if($nf->name == $ft->name){
                    $nf->comment = $ft->comment;

                    foreach($ft->criteria as $crf){
                        $indexTarget = array_search($crf->title, array_column($nf->criteria, 'title'));
                        $nf->criteria[$indexTarget]->qualpoints = $crf->qualpoints;
                    }
                }   
            }
            array_push($generalParams, $nf);
        }
        for($i=0; $i<count($generalParams); $i++){
            for($j=0; $j<count($generalParams[$i]->criteria); $j++){
                $toGeneralPoints = $toGeneralPoints + $generalParams[$i]->criteria[$j]->qualpoints;
            }
        }
    }

    $rps = $database->query($findExistingQP);
    $rps->setFetchMode(PDO::FETCH_ASSOC);
    $fetchPenal = $rps->fetch();

    if($fetchPenal == false){
        $generalPenalties = $penalties;
        $toPenaltyPoinst = 0;
        for($i=0; $i<count($generalPenalties); $i++){
            if(!$generalPenalties[$i]->judgements){
                $generalPenalties[$i]->judgements = [];
            }
        }
    } else {
        $generalPenalties = array();
        $fetchPenals = json_decode($fetchPenal['data']);
        $toPenaltyPoinst = 0;
        $pnl = array();
        foreach($penalties as $per){
            $nf = $per;
            $ixpen = array();
            foreach($fetchPenals as $ft){
                $in = array_search($ft->id, array_column($penalties, 'id'));
                if(!$penalties[$in]->judgements){
                    $penalties[$in]->judgements = [];
                }
                if(count($penalties[$in]->judgements) == 0){
                    $penalties[$in]->judgements = $ft->judgements;
                }
            }
        }
        $generalPenalties = $penalties;
        //var_dump($generalPenalties);
        for($i=0; $i<count($generalPenalties); $i++){
            if(!is_array($generalPenalties[$i]->judgements)){
                $generalPenalties[$i]->judgements = [];
            }
            $cantPen = count($generalPenalties[$i]->judgements);
            $valPen  = doubleval($generalPenalties[$i]->points);
            $toPenaltyPoinst = $toPenaltyPoinst + ($cantPen * $valPen);
        }
    }
 
    $return = [
        "participation" => [
            "id" => $participation,
            "video" => $findPart['video'],
            "category" => Matchs::getCategoriesById($findPart['category_id']),
            "event_id" => $findPart['event_id'],
            "participant_id" => $findPart['partipant_id'],
            "statusData" => Status::getStatusParticipation($participation),
        ],
        "judging" => $generalParams,
        "penalty" => $generalPenalties,
        "generalTotal" => $toGeneralPoints,
        "penaltyTotal" => $toPenaltyPoinst,
    ];

    return new Response(json_encode($return), 200);
});

$juzging->get('/toolsm/{participation}', function($participation) use ($dbo, $database){
    /**INITIAL INFO OF PARTICIPATION**/
    $query_participation = <<<SQL
    SELECT id, category_id, created_by, data->>'video_url' video, partipant_id, event_id FROM SASA_MATCH_MODULES_PARTICIPATIONS WHERE ID = '{$participation}'; 
    SQL;
    $findPart = $database->query($query_participation);
    if(is_null($findPart)){
        return new Response(json_encode(["NOT FOUND"]), 404);
    }
    $findPart = $findPart->fetch(PDO::FETCH_ASSOC);

    $query_parameters = <<<SQL
    SELECT
    PAR.id AS parametro_id,
    PAR.title AS parametro_name,
    PAR.description AS parametro_desc,
    PAR.criteria AS parametro_criteria
    FROM SASA_MATCH_CORE_CATEGORIES_PARAMETERS AS CATPAR
    LEFT JOIN SASA_MATCH_CORE_PARAMETERS AS PAR
    ON PAR.id = CATPAR.parametro
    LEFT JOIN SASA_MATCH_CORE_PANELS_CATEGORIES AS PCAT
    ON PCAT.categoryid = CATPAR.categoria
    LEFT JOIN SASA_MATCH_CORE_PANELS_EVENTS AS PEVENT 
    ON PEVENT.PANEL = PCAT.PANELID
    LEFT JOIN SASA_MATCH_CORE_PANELS AS PANEL
    ON PANEL.id = PCAT.panelid
    CROSS JOIN JSON_ARRAY_ELEMENTS(PANEL.generaljudge) AS EACH_GENERALJUDGE
    CROSS JOIN JSON_ARRAY_ELEMENTS(EACH_GENERALJUDGE->'params') AS EACH_PARAMS
    CROSS JOIN JSON_ARRAY_ELEMENTS(PAR.criteria) EACH_CRITERIA
    WHERE CATPAR.CATEGORIA = '{$findPart["category_id"]}' AND PEVENT.EVENT = '{$findPart["event_id"]}'
    GROUP BY PAR.id
    SQL;
    $findParameters = $dbo->setQuery($query_parameters)->loadObjectList();
    
    $query_penalty = <<<SQL
    SELECT
    DISTINCT PENALTIES.id penalty_id,
    PENALTIES.title penalty_name,
    PENALTIES.data->>'desc' penalty_desc,
    PENALTIES.points::int penalty_points
    FROM SASA_MATCH_CORE_PANELS_CATEGORIES PCAT
    LEFT JOIN SASA_MATCH_CORE_PANELS_EVENTS PEVENT ON PEVENT.PANEL = PCAT.PANELID
    LEFT JOIN SASA_MATCH_CORE_PANELS PANEL ON PANEL.id = PCAT.panelid
    CROSS JOIN JSON_ARRAY_ELEMENTS(PANEL.penaltyjudge) AS EACH_PENALTYJUDGE
    CROSS JOIN JSON_ARRAY_ELEMENTS(EACH_PENALTYJUDGE->'params') EACH_PENALS
    LEFT JOIN SASA_MATCH_CORE_PARAMETERS_PENALTY AS PENALTIES ON PENALTIES.id = (EACH_PENALS->>'id')::int
    WHERE PCAT.CATEGORYID = '{$findPart["category_id"]}'
    AND PEVENT.EVENT = '{$findPart["event_id"]}'
    SQL;

    $findPenalties = $dbo->setQuery($query_penalty)->loadObjectList();

    //var_dump($findPenalties);

    /*********FIND STATUS PARAMS*********/
    $statusToParams = Status::getStatus(3, $findPart['id']);
    /*********FIND STATUS PARAMS*********/
    
    /*********FIND STATUS PENALTY*********/
    $statusToPenal = Status::getStatus(2, $findPart['id']);
    /*********FIND STATUS PENALTY*********/


    /**VARIABLES DE RETORNO**/
    $parametros = array();
    $penalties  = array();
    /**VARIABLES DE RETORNO**/

    foreach($findParameters as $i => $par){
        $crit = json_decode($par->parametro_criteria);
        $total = 0;
        foreach($crit as $e){
            $total = $total + $e->points;
        }
        $crit = json_decode($par->parametro_criteria);
        $add = new stdClass();
        $add->id = $par->parametro_id;
        $add->name = $par->parametro_name;
        $add->criteria = json_decode($par->parametro_criteria);
        $add->total = $total;
        #Sino existe un registro... sera 0
        if($statusToParams == false){
            $add->flag  = 0;
        } else {
            #Si existe debe proveerse...
            $jsonStd = json_decode($statusToParams['data']);
            
            foreach($jsonStd as $st){
                if($st->id == $add->id){
                    $add->flag = $st->flag;
                }
            }
        }
        array_push($parametros, $add);
    }
    foreach($findPenalties as $i => $par){
        $add = new stdClass();
        $add->id = $par->penalty_id;
        $add->name = $par->penalty_name;
        $add->points = $par->penalty_points;
        if($statusToPenal == false){
            $add->flag = 2;
        } else {
            #Si existe debe proveerse...
            $jsonStd = json_decode($statusToPenal['data']);

            

            forEach($jsonStd as $k => $penalSt){
                if($penalSt->id == $add->id){
                    if(!$add->flag){
                        $add->flag = $penalSt->flag;
                    }
                }
            }
            
        }
        
        array_push($penalties, $add);
    }
    $findexistintQ = "select gc.data from sasa_match_core_score_general gc where entry = '{$participation}';";

    $findExistingQP = "select gc.data from sasa_match_core_score_penalty gc where entry = '{$participation}';";

    $result = $database->query($findexistintQ);
    $result->setFetchMode(PDO::FETCH_ASSOC);
    $fetch = $result->fetch();
    
    if($fetch == false){
        $generalParams = $parametros;
        $toGeneralPoints = 0;
    } else {
        $generalParams = array();
        $fetchParams = json_decode($fetch['data']);
        $toGeneralPoints = 0;
        foreach($parametros as $per){
            $nf = $per;
            foreach($fetchParams as $ft){
                if($nf->name == $ft->name){
                    $nf->comment = $ft->comment;

                    foreach($ft->criteria as $crf){
                        $indexTarget = array_search($crf->title, array_column($nf->criteria, 'title'));
                        $nf->criteria[$indexTarget]->qualpoints = $crf->qualpoints;
                    }
                }   
            }
            array_push($generalParams, $nf);
        }
        for($i=0; $i<count($generalParams); $i++){
            for($j=0; $j<count($generalParams[$i]->criteria); $j++){
                $toGeneralPoints = $toGeneralPoints + $generalParams[$i]->criteria[$j]->qualpoints;
            }
        }
    }

    $rps = $database->query($findExistingQP);
    $rps->setFetchMode(PDO::FETCH_ASSOC);
    $fetchPenal = $rps->fetch();

    if($fetchPenal == false){
        $generalPenalties = $penalties;
        $toPenaltyPoinst = 0;
        for($i=0; $i<count($generalPenalties); $i++){
            if(!$generalPenalties[$i]->judgements){
                $generalPenalties[$i]->judgements = [];
            }
        }
    } else {
        $generalPenalties = array();
        $fetchPenals = json_decode($fetchPenal['data']);
        $toPenaltyPoinst = 0;
        $pnl = array();
        foreach($penalties as $per){
            $nf = $per;
            $ixpen = array();
            foreach($fetchPenals as $ft){
                $in = array_search($ft->id, array_column($penalties, 'id'));
                if(!$penalties[$in]->judgements){
                    $penalties[$in]->judgements = [];
                }
                if(count($penalties[$in]->judgements) == 0){
                    $penalties[$in]->judgements = $ft->judgements;
                }
            }
        }
        $generalPenalties = $penalties;
        //var_dump($generalPenalties);
        for($i=0; $i<count($generalPenalties); $i++){
            if(!is_array($generalPenalties[$i]->judgements)){
                $generalPenalties[$i]->judgements = [];
            }
            $cantPen = count($generalPenalties[$i]->judgements);
            $valPen  = doubleval($generalPenalties[$i]->points);
            $toPenaltyPoinst = $toPenaltyPoinst + ($cantPen * $valPen);
        }
    }
 
    $return = [
        "participation" => [
            "id" => $participation,
            "video" => $findPart['video'],
            "category" => Matchs::getCategoriesById($findPart['category_id']),
            "event_id" => $findPart['event_id'],
            "participant_id" => $findPart['partipant_id'],
            "statusData" => Status::getStatusParticipation($participation),
        ],
        "judging" => $generalParams,
        "penalty" => $generalPenalties,
        "generalTotal" => $toGeneralPoints,
        "penaltyTotal" => $toPenaltyPoinst,
    ];

    return new Response(json_encode($return), 200);
});

$juzging->get('/tools2/{participation}', function($participation) use ($dbo, $database){
    /**INITIAL INFO OF PARTICIPATION**/
    $query_participation = <<<SQL
    SELECT id, category_id, created_by, data->>'video_url' video, partipant_id, event_id FROM SASA_MATCH_MODULES_PARTICIPATIONS WHERE ID = '{$participation}'; 
    SQL;
    $findPart = $database->query($query_participation);
    if(is_null($findPart)){
        return new Response(json_encode(["NOT FOUND"]), 404);
    }
    $findPart = $findPart->fetch(PDO::FETCH_ASSOC);

    /**REMEMBER**/
    /***
     * Here I must change the query below to result could know which is the params by judge connected
     * Aquí debo cambiar la consulta a continuación para que el resultado pueda saber cuál es el parámetro por juez conectado.
     */
    /**REMEMBER**/

    $query_parameters = <<<SQL
    SELECT
    PARA.TITLE parametro_name,
    PARA.DESCRIPTION parametro_desc,
    (EACH_PARAMS->>'id')::int parametro_id,
    PARA.CRITERIA parametro_criteria
    FROM SASA_MATCH_CORE_PANELS_CATEGORIES PCAT
    LEFT JOIN SASA_MATCH_CORE_PANELS_EVENTS PEVENT ON PEVENT.PANEL = PCAT.PANELID
    LEFT JOIN SASA_MATCH_CORE_PANELS PANEL ON PANEL.id = PCAT.panelid
    CROSS JOIN JSON_ARRAY_ELEMENTS(PANEL.generaljudge) AS EACH_GENERALJUDGE
    CROSS JOIN JSON_ARRAY_ELEMENTS(EACH_GENERALJUDGE->'params') AS EACH_PARAMS
    LEFT JOIN SASA_MATCH_CORE_PARAMETERS AS PARA ON PARA.id = (EACH_PARAMS->>'id')::int
    WHERE PCAT.CATEGORYID = '{$findPart["category_id"]}'
    AND PEVENT.EVENT = '{$findPart["event_id"]}'
    SQL;
    $findParameters = $dbo->setQuery($query_parameters)->loadObjectList();
    
    $query_penalty = <<<SQL
    SELECT
    PENALTIES.id penalty_id,
    PENALTIES.title penalty_name,
    PENALTIES.data->>'desc' penalty_desc,
    PENALTIES.points::int penalty_points
    FROM SASA_MATCH_CORE_PANELS_CATEGORIES PCAT
    LEFT JOIN SASA_MATCH_CORE_PANELS_EVENTS PEVENT ON PEVENT.PANEL = PCAT.PANELID
    LEFT JOIN SASA_MATCH_CORE_PANELS PANEL ON PANEL.id = PCAT.panelid
    CROSS JOIN JSON_ARRAY_ELEMENTS(PANEL.penaltyjudge) AS EACH_PENALTYJUDGE
    CROSS JOIN JSON_ARRAY_ELEMENTS(EACH_PENALTYJUDGE->'params') EACH_PENALS
    LEFT JOIN SASA_MATCH_CORE_PARAMETERS_PENALTY AS PENALTIES ON PENALTIES.id = (EACH_PENALS->>'id')::int
    WHERE PCAT.CATEGORYID = '{$findPart["category_id"]}'
    AND PEVENT.EVENT = '{$findPart["event_id"]}'
    SQL;

    $findPenalties = $dbo->setQuery($query_penalty)->loadObjectList();


    /**VARIABLES DE RETORNO**/
    $parametros = array();
    $penalties  = array();
    /**VARIABLES DE RETORNO**/

    foreach($findParameters as $i => $par){
        $crit = json_decode($par->parametro_criteria);
        $total = 0;
        foreach($crit as $e){
            $total = $total + $e->points;
        }
        $crit = json_decode($par->parametro_criteria);
        $add = new stdClass();
        $add->name = $par->parametro_name;
        $add->criteria = json_decode($par->parametro_criteria);
        $add->total = $total;
        array_push($parametros, $add);
    }
    
    foreach($findPenalties as $i => $par){
        $add = new stdClass();
        $add->id = $par->penalty_id;
        $add->name = $par->penalty_name;
        $add->points = $par->penalty_points;
        $add->desc = $par->penalty_desc;
        array_push($penalties, $add);
    }

    $return = [
        "participation" => [
            "id" => $participation,
            "video" => $findPart['video'],
            "category" => Matchs::getCategoriesById($findPart['category_id']),
        ],
        "judging" => $parametros,
        "penalty" => $penalties
    ];

    return new Response(json_encode($return), 200);
});

$juzging->get('/toolsmain/{participation}', function($participation) use ($dbo, $database){

    /**INITIAL INFO OF PARTICIPATION**/
    $findexistintQ = "select judge, gc.data from sasa_match_core_score_general gc where entry = '{$participation}';";
    $findExistingQP = "select judge, gc.data from sasa_match_core_score_penalty gc where entry = '{$participation}';";
    $result = $database->query($findexistintQ);
    $result->setFetchMode(PDO::FETCH_ASSOC);
    $fetch = $result->fetch();

    if($fetch == false){
        $generalParams = [];
    } else {
        $generalParams = json_decode($fetch['data']);
    }

    /*********FIND STATUS PARAMS*********/
    $statusToParams = Status::getStatus(3, $participation);
    /*********FIND STATUS PARAMS*********/

     /*********FIND STATUS PARAMS*********/
     $statusToPenalty = Status::getStatus(4, $participation);
     /*********FIND STATUS PARAMS*********/

    //sumatoria...
    $newsPar = array();
    foreach($generalParams as $i => $params){
        $pan = $params;
        $sum = 0;

        #Sino existe un registro... sera 0
        if($statusToParams == false){
            $pan->flag  = 0;
        } else {
            #Si existe debe proveerse...
            $jsonStd = json_decode($statusToParams['data']);
            
            foreach($jsonStd as $st){
                if($st->id == $pan->id){
                    $pan->flag = $st->flag;
                }
            }
        }

        foreach($params->criteria as $cri){
            $sum += doubleval($cri->qualpoints);
        }

        $pan->qualtotal = $sum;
        $pan->judge = $fetch['judge'];

        $pan->comment = $params->comment;
        array_push($newsPar, $pan);
    }

    $rps = $database->query($findExistingQP);
    $rps->setFetchMode(PDO::FETCH_ASSOC);
    $fetchPenal = $rps->fetch();

    if($fetchPenal == false){
        $generalPenalties = [];
    } else {
        $generalPenalties = json_decode($fetchPenal['data']);
    }

    //sumatoria...
    $newsPen = array();
    foreach($generalPenalties as $i => $penalty):
        
        $pen = $penalty;
        if( !is_null($penalty->judgements) ){
            $sumatoria = doubleval($penalty->points) * count($penalty->judgements);
            $pen->ammount = $sumatoria;
        } else {
            $pen->ammount = 0;
        }
        $pen->judge = $fetchPenal['judge'];
        if($statusToPenalty==false){
            $pen->flag = 0;
        } else {
            $jsonToEach = json_decode($statusToPenalty['data']);
            foreach($jsonToEach as $st){
                if($pen->id == $st->id){
                    $pen->flag = $st->flag;
                }
            }

        }

        array_push($newsPen, $pen);
    endforeach;
    
    return new Response(json_encode([
        "parameters" => $newsPar,
        "penalties" =>  $newsPen,
    ]), 200);

});

$juzging->get('/toolsathlete/{participation}', function($participation) use ($dbo, $database){

    /**INITIAL INFO OF PARTICIPATION**/
    $findexistintQ = "select judge, gc.data from sasa_match_core_score_general_finally gc where entry = '{$participation}';";
    $findExistingQP = "select judge, gc.data from sasa_match_core_score_penalty_finally gc where entry = '{$participation}';";
    $result = $database->query($findexistintQ);
    $result->setFetchMode(PDO::FETCH_ASSOC);
    $fetch = $result->fetch();

    if($fetch == false){
        $generalParams = [];
    } else {
        $generalParams = json_decode($fetch['data']);
    }

    /*********FIND STATUS PARAMS*********/
    $statusToParams = Status::getStatus(3, $participation);
    /*********FIND STATUS PARAMS*********/

     /*********FIND STATUS PARAMS*********/
     $statusToPenalty = Status::getStatus(4, $participation);
     /*********FIND STATUS PARAMS*********/

    //sumatoria...
    $newsPar = array();
    if(is_string($generalParams)){
        $generalParams = json_decode($generalParams);
    }
    foreach($generalParams as $i => $params){
        $pan = $params;
        $sum = 0;

        #Sino existe un registro... sera 0
        if($statusToParams == false){
            $pan->flag  = 0;
        } else {
            #Si existe debe proveerse...
            $jsonStd = json_decode($statusToParams['data']);
            
            foreach($jsonStd as $st){
                if($st->id == $pan->id){
                    $pan->flag = $st->flag;
                }
            }
        }

        foreach($params->criteria as $cri){
            $sum += doubleval($cri->qualpoints);
        }

        $pan->qualtotal = $sum;
        $pan->judge = $fetch['judge'];

        $pan->comment = $params->comment;
        array_push($newsPar, $pan);
    }

    $rps = $database->query($findExistingQP);
    $rps->setFetchMode(PDO::FETCH_ASSOC);
    $fetchPenal = $rps->fetch();

    if($fetchPenal == false){
        $generalPenalties = [];
    } else {
        $generalPenalties = json_decode($fetchPenal['data']);
    }

    if(is_string($generalPenalties)){
        $generalPenalties = json_decode($generalPenalties);
    }

    //sumatoria...
    $newsPen = array();
    if(is_array($generalPenalties)){
        foreach($generalPenalties as $i => $penalty):
        
            $pen = $penalty;
            if( !is_null($penalty->judgements) ){
                $sumatoria = doubleval($penalty->points) * count($penalty->judgements);
                $pen->ammount = $sumatoria;
            } else {
                $pen->ammount = 0;
            }
            $pen->judge = $fetchPenal['judge'];
            if($statusToPenalty==false){
                $pen->flag = 0;
            } else {
                $jsonToEach = json_decode($statusToPenalty['data']);
                foreach($jsonToEach as $st){
                    if($pen->id == $st->id){
                        $pen->flag = $st->flag;
                    }
                }
    
            }
    
            array_push($newsPen, $pen);
        endforeach;
    }
    

    $response = Status::validateAndUpdateAthleteStatus($participation);
    $totalpuntos = 0;
    foreach($newsPar as $pop){
        $totalpuntos = $totalpuntos + $pop->qualtotal;
    }
    $totalpenals = 0;
    foreach($newsPen as $pop){
        $totalpenals = $totalpenals + (floatval($pop->points) * count($pop->judgements));
    }

    $sumatoriasppp = $totalpuntos - $totalpenals;
    return new Response(json_encode([
        "parameters" => $newsPar,
        "penalties" =>  $newsPen,
        "times"     =>  $response,
        "totalpuntos" => $totalpuntos,
        "totalpenal" => $totalpenals,
        "totalrest" => ($sumatoriasppp < 1) ? 0 : $sumatoriasppp
    ]), 200);

});

$juzging->post('/score/general/save', function() use ($dbo, $database){
    $data = json_decode($_POST['form']);
    if(!is_object($data)){
        return new Response(json_encode(["BAD DATA"]), 500);
    }
    #comprobe is no exist
    $queryfind = "SELECT id FROM sasa_match_core_score_general WHERE entry = '".$data->participation->id."'";
    ///return json_encode($queryfind);
    $exist = $database->select('match_core_score_general', '*', [
        "entry" => $data->participation->id
    ]);
    #Save data on database:
    $insertD = array(
        "judge" => $data->judge,
        "data"  => json_encode($data->judging),
        "entry" => $data->participation->id,
    );
    $generalParams = $data->judging;
    /***************APPLY STATUS************/
    $resultApplyStatusMain = Status::setStatusMain($data->participation->id, $generalParams);
    $resultApplyStatus = Status::setStatus(1, $data->participation->id, $generalParams, $data->judge);
    $resultApplyStatusEntry = Status::setStatusParticipation($data->participation->id, 1, null, 0);
    $eventofParticipation = Helper\get_event_by_entry($data->participation->id);
    $resullUpdateEventStatus = Status::updateStateEvent($eventofParticipation['event_id']);
    if($resultApplyStatus['error'] == false){
        return new Response(json_encode(["ERROR ON SEND STATUS", $resultApplyStatus]), 405);
    }
    if($resultApplyStatusEntry['error'] == false){
        return new Response(json_encode(["ERROR ON SEND STATUS", $resultApplyStatus]), 405);
    }
    if($resultApplyStatusMain['error'] == false){
        return new Response(json_encode(["ERROR ON SEND STATUS", $resultApplyStatus]), 405);
    }
    /***************APPLY STATUS************/

    if(count($exist) == 0){
        $insert = $database->insert('match_core_score_general', $insertD);
        if($database->error){
            return new Response(json_encode(["ERROR ON REGISTER", $database->error]), 400);
        }

        return new Response(json_encode(["SAVED"]), 200);
    } else {

        //var_dump($data->judging);
        $ar = json_decode($exist[0]['data']);
        $arr = array_map(function($r){
            return (array) $r;
        }, $ar);
        $nuevoObjecto = $arr;
        foreach($data->judging as $k => $ele){            
            $index = array_search($ele->id, array_column($nuevoObjecto, 'id'));
            
            if(is_bool($index) == true){
                array_push($nuevoObjecto, $ele);
            } else if(is_bool($index) == false) {
                $nuevoObjecto[$index] = $ele;
            }            
        }

        $updateD = array(
            "data"  => json_encode($nuevoObjecto),
        );

        $database->update('match_core_score_general', $updateD, ["entry" => $data->participation->id]);

        if($database->error){
            return new Response(json_encode(["ERROR ON UPDATE", $database->error]), 400);
        }

        return new Response(json_encode(["UPDATE DATA"]), 201);

    }
    
});

$juzging->post('/score/penalty/save', function() use ($dbo, $database){ 
    $data = json_decode($_POST['form']);
    if(!is_object($data)){
        return new Response(json_encode(["BAD DATA"]), 500);
    }

    
    #comprobe is no exist
    $queryfind = "SELECT id FROM match_core_score_penalty WHERE entry = '{$data->participation->id}'";
    ///return json_encode($queryfind);
    $exist = $database->select('match_core_score_penalty', '*', [
        "entry" => $data->participation->id
    ]);
    #Save data on database:
    $insertD = array(
        "judge" => $data->judge,
        "data"  => json_encode($data->penalty),
        "entry" => $data->participation->id,
    );

    /***************APPLY STATUS************/
    $penalties = $data->penalty;
    
    $resultApplyStatusMain = Status::setStatusMainPanel($data->participation->id, $penalties);
    $resultApplyStatus = Status::setStatus(2, $data->participation->id, $penalties, $data->judge);
    $resultApplyStatusEntry = Status::setStatusParticipation($data->participation->id, null, 1, 0);

    $eventofParticipation = Helper\get_event_by_entry($data->participation->id);
    $resullUpdateEventStatus = Status::updateStateEvent($eventofParticipation['event_id']);

    if($resultApplyStatus['error'] == false){
        return new Response(json_encode(["ERROR ON SEND STATUS1", $resultApplyStatus]), 405);
    }
    if($resultApplyStatusEntry['error'] == false){
        return new Response(json_encode(["ERROR ON SEND STATUS2", $resultApplyStatusEntry['error']]), 405);
    }
    if($resultApplyStatusMain['error'] == false){
        return new Response(json_encode(["ERROR ON SEND STATUS3", $resultApplyStatusMain]), 405);
    }
    /***************APPLY STATUS************/

    if(count($exist) == 0){
        $insert = $database->insert('match_core_score_penalty', $insertD);
        if($database->error){
            return new Response(json_encode(["ERROR ON REGISTER", $database->error]), 400);
        }

        return new Response(json_encode(["SAVED"]), 200);
    } else {
        /***UPDATE */

        $updateD = array(
            "data"  => json_encode($data->penalty),
        );

        $database->update('match_core_score_penalty', $updateD, ["entry" => $data->participation->id]);

        if($database->error){
            return new Response(json_encode(["ERROR ON UPDATE", $database->error]), 400);
        }

        return new Response(json_encode(["UPDATE DATA"]), 201);
    }
});

$juzging->post('/toolsmain/save', function() use ($dbo, $database){
    $data = json_decode($_POST['form']);
    if(!is_object($data)){
        return new Response(json_encode(["BAD DATA"]), 500);
    }

    #comprobe is no exist
    $findGeneralFinal = $database->select('match_core_score_general_finally', '*', [
        "entry" => $data->participation->id
    ]);
    
    $findPenaltyFinal = $database->select('match_core_score_penalty_finally', '*', [
        "entry" => $data->participation->id
    ]);

    $datagen = json_encode($data->judging);
    $datapen = json_encode($data->penalty);

    $applyToParamsStatus = Status::setStatusMain($data->participation->id, $data->judging, true);
    $applyToPenalStatus = Status::setStatusMainPenal($data->participation->id, $data->penalty, true);
    $resultApplyStatusEntry = Status::setStatusParticipation($data->participation->id, null, null, 1);

    /******************************************/
    $eventidByParticipation = Helper\get_event_by_entry($data->participation->id);
    $applyStatusEvent = Status::setStatusEvent($eventidByParticipation['event_id']);

    if($applyToParamsStatus['error'] == false){
        return new Response(json_encode(["ERROR ON SEND STATUS1", $applyToParamsStatus]), 405);
    }
    
    if($applyToPenalStatus['error'] == false){
        return new Response(json_encode(["ERROR ON SEND STATUS2", $applyToPenalStatus]), 405);
    }
    
    if($resultApplyStatusEntry['error'] == false){
        return new Response(json_encode(["ERROR ON SEND STATUS3", $resultApplyStatusEntry]), 405);
    }

    if( count($findGeneralFinal) == 0 && count($findPenaltyFinal) == 0 ):
        $exe = <<<SQL
        SELECT to_json(submitjudgemain(1, '{$data->participation->id}', '{$datagen}', '{$datapen}', '{$data->judge}')) todofunco;
        SQL;

        $result = $database->query($exe);
        $result->setFetchMode(PDO::FETCH_ASSOC);
        $rs = $result->fetch();

        if($database->error){
            return new Response(json_encode(["ERROR ON SEND TO RANK"]), 400);
        }

        return new Response(json_encode(["OK", $applyStatusEvent]), 200);

    else :
        #comprobe is no exist
        $findGeneral = $database->select('match_core_score_general', '*', [
            "entry" => $data->participation->id
        ]);

        $datagen = json_encode($findGeneral[0]["data"]);
        
        $findPenalty = $database->select('match_core_score_penalty', '*', [
            "entry" => $data->participation->id
        ]);
        
        $datapen = json_encode($findPenalty[0]["data"]);

        $exe2 = <<<SQL
        UPDATE SASA_MATCH_CORE_SCORE_GENERAL_FINALLY SET data = '{$datagen}' WHERE entry = '{$data->participation->id}';
        SQL;

        $result2 = $database->query($exe2)->execute();
        
        $exe3 = <<<SQL
        UPDATE SASA_MATCH_CORE_SCORE_PENALTY_FINALLY SET data = '{$datapen}' WHERE entry = '{$data->participation->id}';
        SQL;

        $result3 = $database->query($exe3)->execute();

        if($database->error){
            return new Response(json_encode(["ERROR ON SEND TO RANK"]), 400);
        }

        return new Response(json_encode(["OK", $applyStatusEvent]), 201);
    endif;
});

$juzging->post('/toolsmain/savemasive', function() use ($dbo, $database){
    try {
        $data = json_decode($_POST['form']);
    if(!is_object($data)){
        return new Response(json_encode(["BAD DATA"]), 500);
    }

    #OBTENER ENTRIES DEL EVENTO:
    #BUSCANDO
    $queryEntries = <<<SQL
    SELECT id from SASA_MATCH_MODULES_PARTICIPATIONS WHERE event_id = '{$data->event}';
    SQL;
    
    $rsq = $database->query($queryEntries);
    //rsq->setFetchMode();
    $rse = $rsq->fetchAll(PDO::FETCH_COLUMN);

    $rse = array_map(function($ele){
        return "'".$ele."'";
    }, $rse);
    
    $implode = implode(", ", $rse);

    $QUERYUPDATESTATUS = <<<SQL
    UPDATE sasa_match_status_entries SET statusmain = '1' WHERE entryid IN ({$implode})
    SQL;
    $STM1 = $database->pdo->prepare($QUERYUPDATESTATUS)->execute();

    $QUERYUPDATEEVENT = <<<SQL
    UPDATE sasa_match_status_event SET status = :status WHERE eventid = :event;
    SQL;

    $STM = $database->pdo->prepare($QUERYUPDATEEVENT)->execute(["status" => 1, "event" => $data->event]);

    $queryDelete = <<<SQL
    DELETE FROM SASA_MATCH_CORE_SCORE_GENERAL_FINALLY WHERE entry IN (SELECT id from SASA_MATCH_MODULES_PARTICIPATIONS WHERE event_id = '{$data->event}');
    SQL;

    $deleteAction1 = $database->pdo->prepare($queryDelete)->execute();
    
    $queryDelete2 = <<<SQL
    DELETE FROM SASA_MATCH_CORE_SCORE_PENALTY_FINALLY WHERE entry IN (SELECT id from SASA_MATCH_MODULES_PARTICIPATIONS WHERE event_id = '{$data->event}');
    SQL;

    $deleteAction = $database->pdo->prepare($queryDelete2)->execute();

    $insertFinalGen = <<<SQL
        INSERT 
        INTO 
        SASA_MATCH_CORE_SCORE_GENERAL_FINALLY(
            ENTRY,
            DATA, 
            JUDGE
        ) (SELECT
        SG.ENTRY,
        SG.DATA,
        SG.JUDGE
        FROM SASA_MATCH_CORE_SCORE_GENERAL AS SG
        LEFT JOIN SASA_MATCH_MODULES_PARTICIPATIONS AS PT ON PT.ID = SG.ENTRY
        WHERE PT.EVENT_ID = '{$data->event}')
        SQL;

        $database->pdo->prepare($insertFinalGen)->execute();

        $insertFinalPen = <<<SQL
        INSERT 
        INTO 
        SASA_MATCH_CORE_SCORE_PENALTY_FINALLY(
            ENTRY,
            DATA, 
            JUDGE
        ) (SELECT
        SG.ENTRY,
        SG.DATA,
        SG.JUDGE
        FROM SASA_MATCH_CORE_SCORE_PENALTY AS SG
        LEFT JOIN SASA_MATCH_MODULES_PARTICIPATIONS AS PT ON PT.ID = SG.ENTRY
        WHERE PT.EVENT_ID = '{$data->event}');
        SQL;

        $database->pdo->prepare($insertFinalPen)->execute();
        
        if($database->error) {
            return new Response(json_encode(["ERROR", $database->error]), 400);
        }

        return new Response(json_encode(["Ok"]), 200);
    } catch(\Exception $e){
        var_dump($e->getMessage());
    }
});

$juzging->post('/toolsmain/updateeventstatus', function()use ($dbo, $database) {
    $data = json_decode($_POST['form']);
    if(!is_object($data)){
        return new Response(json_encode(["BAD DATA"]), 500);
    }

    

    return 1;

});

/**
 * RETORNA ASIGNACIONES PARA JUECES
 */ 

$juzging->get('/judgeasigmentsbyentry/{entry}', function(Request $req, $entry) use ($dbo, $database){
    $token = $req->headers->get('Authorization');
    if(empty($token)){
        return new Response(json_encode(['No authorized']), 401);
    }

    $validate = AuthHelper::verify($token);
    
    if($validate == false){
        return new Response(json_encode("Unauthorized"), 401);
    }

    $datos_usuario = Extended::getUserByEmail($validate->data->email);
    $juezid = (int) $datos_usuario['id'];
    $juezserial = $datos_usuario['serial'];

    $authmodel = new Auth;
    $isManager = $authmodel->userJoomlaManager($juezid);

    $datos_entry_sql = <<<SQL
    SELECT par.id, par.category_id, par.created_by, par.data->>'video_url' as video,  par.partipant_id, par.event_id FROM sasa_match_modules_participations as par where par.id = '{$entry}';
    SQL;

    $datos_entry_query = $database->query($datos_entry_sql);
    if(is_null($datos_entry_query)){
        return new Response(json_encode(['Entry no found']), 404);
    }

    $datos_entry_query->setFetchMode(PDO::FETCH_OBJ);
    $datos_entry_result = $datos_entry_query->fetch();

    if($isManager == false){
        #datos para jueces tipo generales.
        $datos_panel_sql = <<<SQL
        select
        each_general->>'id' as contenedor,
        parameterofi.id as parametro_id,
        parameterofi.title as parametro_name,
        parameterofi.description as parametro_desc,
        parameterofi.criteria as parametro_criteria
        from sasa_match_core_panels_categories as panelcat 
        left join sasa_match_core_panels as panel on panel.id = panelcat.panelid
            cross join json_array_elements(panel.generaljudge) as each_general
            cross join json_array_elements(each_general->'params') as each_param
        left join sasa_match_core_parameters as parameterofi on parameterofi.id = (each_param->>'id')::int
        left join sasa_match_core_panels_events as pevent on pevent.panel = panelcat.panelid
        where 
            panelcat.categoryid = '{$datos_entry_result->category_id}' 
            and pevent.event = '{$datos_entry_result->event_id}'
            and (each_general->>'general') like '%{$juezserial}'
        group by parameterofi.id, each_general->>'id';
        SQL;

        $datos_panel_result = $dbo->setQuery($datos_panel_sql)->loadObjectList();
        if(is_null($datos_panel_result)){
            return new Response(json_encode(['ERROR INTERNO1']), 500);
        }
    
        #datos para jueces tipo penalties.
        $datos_panel_penal_sql = <<<SQL
        select
        DISTINCT penalties.id penalty_id,
        each_penal->>'id' as contenedor,
        penalties.title penalty_name,
        penalties.data->>'desc' penalty_desc,
        penalties.points::int penalty_points
        from sasa_match_core_panels_categories as panelcat 
        left join sasa_match_core_panels as panel on panel.id = panelcat.panelid
        cross join json_array_elements(panel.penaltyjudge) as each_penal
        cross join json_array_elements(each_penal->'params') as each_penalty
        left join sasa_match_core_parameters_penalty as penalties on penalties.id = (each_penalty->>'id')::int
        where panelcat.categoryid = '{$datos_entry_result->category_id}' and (each_penal->>'penalty') like '%{$juezserial}'
        SQL;
        
        $datos_panel_penal_result = $dbo->setQuery($datos_panel_penal_sql)->loadObjectList();
        if(is_null($datos_panel_penal_result)){
            return new Response(json_encode(['ERROR INTERNO2']), 500);
        }
    
        #datos para juezmain.
        $datos_panel_main_sql = <<<SQL
        select 
        panel.*
        from sasa_match_core_panels_categories as panelcat 
        left join sasa_match_core_panels as panel on panel.id = panelcat.panelid
        cross join json_array_elements(panel.mainjudge) as each_main
        where panelcat.categoryid = '{$datos_entry_result->category_id}' and (each_main->>'user') like '%{$juezserial}'
        SQL;
        $datos_panel_main_result = $dbo->setQuery($datos_panel_main_sql)->loadObjectList();
        if(is_null($datos_panel_main_result)){
            return new Response(json_encode(['ERROR INTERNO3']), 500);
        }

        #datos para el atleta        
        $datos_panel_athlete = <<<SQL
        select parti.* from sasa_match_competitor as comp left join sasa_match_users as usr 
        on usr.serial = any(comp.manager::uuid[]) 
        left join sasa_match_modules_participations as parti 
        on parti.partipant_id = comp.id 
        where usr.id = '{$juezid}' and parti.id = '{$entry}';
        SQL;

        $datos_panel_athlete_query = $database->query($datos_panel_athlete);
        if(is_null($datos_panel_athlete_query)){
            return new Response(json_encode(['ERROR INTERNO4']), 500); 
        }

        $datos_panel_athlete_query->setFetchMode(PDO::FETCH_ASSOC);
        $datos_panel_athlete_result = $datos_panel_athlete_query->fetch();
    

        $response = array(
            "general" => [
                "container" => isset($datos_panel_result[0]) ? $datos_panel_result[0]->contenedor : false,
                "show" => (count($datos_panel_result) > 0),
            ],
            "penalty" => [
                "container" => isset($datos_panel_penal_result[0]) ? $datos_panel_penal_result[0]->contenedor : false,
                "show" => (count($datos_panel_penal_result) > 0),
            ],
            "main" => [
                "show" => (count($datos_panel_main_result) > 0),
            ],
            "athlete" => [
                "show" => ($datos_panel_athlete_result != false)
            ]
        );
    } else {
        $response = array(
            "general" => [
                "container" => false,
                "show" => true,
            ],
            "penalty" => [
                "container" => false,
                "show" => true,
            ],
            "main" => [
                "show" => true,
            ],
            "athlete" => [
                "show" => true
            ]
        );
    }
    
    
    return new Response(json_encode($response));
});

/**
 * RETORNA DATA PARA EVALUAR ENTRY SEGUN JUEZ CONECTADO PARA SLOTS DE GENERAL
 */ 

$juzging->get('/asigmentsToJudge/general/{entry}', function(Request $request, $entry) use ($dbo, $database){
    
    $token = $request->headers->get('Authorization');
    if(empty($token)){
        return new Response(json_encode(['No authorized']), 401);
    }

    $validate = AuthHelper::verify($token);
    
    if($validate == false){
        return new Response(json_encode("Unauthorized"), 401);
    }

    $datos_usuario = Extended::getUserByEmail($validate->data->email);
    $juezid = (int) $datos_usuario['id'];
    $juezserial = $datos_usuario['serial'];

    $datos_entry_sql = <<<SQL
    SELECT par.id, par.category_id, par.created_by, par.data->>'video_url' as video,  par.partipant_id, par.event_id FROM sasa_match_modules_participations as par where par.id = '{$entry}';
    SQL;

    $datos_entry_query = $database->query($datos_entry_sql);
    if(is_null($datos_entry_query)){
        return new Response(json_encode(['Entry no found']), 404);
    }

    $datos_entry_query->setFetchMode(PDO::FETCH_OBJ);
    $datos_entry_result = $datos_entry_query->fetch();
    
    $authmodel = new Auth;
    $isManager = $authmodel->userJoomlaManager($juezid);

    #datos para jueces tipo generales.
    if($isManager == false){
        $datos_panel_sql = <<<SQL
        select
        each_general->>'id' as contenedor,
        parameterofi.id as parametro_id,
        parameterofi.title as parametro_name,
        parameterofi.description as parametro_desc,
        parameterofi.criteria as parametro_criteria
        from sasa_match_core_panels_categories as panelcat 
        left join sasa_match_core_panels as panel on panel.id = panelcat.panelid
            cross join json_array_elements(panel.generaljudge) as each_general
            cross join json_array_elements(each_general->'params') as each_param
        left join sasa_match_core_parameters as parameterofi on parameterofi.id = (each_param->>'id')::int
        left join sasa_match_core_panels_events as pevent on pevent.panel = panelcat.panelid
        where 
            panelcat.categoryid = '{$datos_entry_result->category_id}' 
            and pevent.event = '{$datos_entry_result->event_id}'
            and (each_general->>'general') = '{$juezserial}'
        group by parameterofi.id, each_general->>'id';
        SQL;
    } else {
        $datos_panel_sql = <<<SQL
        SELECT
        PAR.id AS parametro_id,
        PAR.title AS parametro_name,
        PAR.description AS parametro_desc,
        PAR.criteria AS parametro_criteria
        FROM SASA_MATCH_CORE_CATEGORIES_PARAMETERS AS CATPAR
        LEFT JOIN SASA_MATCH_CORE_PARAMETERS AS PAR
        ON PAR.id = CATPAR.parametro
        LEFT JOIN SASA_MATCH_CORE_PANELS_CATEGORIES AS PCAT
        ON PCAT.categoryid = CATPAR.categoria
        LEFT JOIN SASA_MATCH_CORE_PANELS_EVENTS AS PEVENT 
        ON PEVENT.PANEL = PCAT.PANELID
        LEFT JOIN SASA_MATCH_CORE_PANELS AS PANEL
        ON PANEL.id = PCAT.panelid
        CROSS JOIN JSON_ARRAY_ELEMENTS(PANEL.generaljudge) AS EACH_GENERALJUDGE
        CROSS JOIN JSON_ARRAY_ELEMENTS(EACH_GENERALJUDGE->'params') AS EACH_PARAMS
        CROSS JOIN JSON_ARRAY_ELEMENTS(PAR.criteria) EACH_CRITERIA
        WHERE CATPAR.CATEGORIA = '{$datos_entry_result->category_id}' AND PEVENT.EVENT = '{$datos_entry_result->event_id}'
        GROUP BY PAR.id
        SQL;
    }
    

    $datos_panel_result = $dbo->setQuery($datos_panel_sql)->loadObjectList();
    if(is_null($datos_panel_result)){
        return new Response(json_encode(['ERROR INTERNO']), 500);
    }

    /*********FIND STATUS PARAMS*********/
    $statusToParams = Status::getStatus(1, $entry);
    /*********FIND STATUS PARAMS*********/

    $parametros = array();

    $contenedor = null;

    foreach($datos_panel_result as $i => $par){
        
        if(is_null($contenedor)){
            $contenedor = $par->contenedor;
        }

        $crit = json_decode($par->parametro_criteria);
        $total = 0;
        foreach($crit as $e){
            $total = $total + $e->points;
        }
        $crit = json_decode($par->parametro_criteria);
        $add = new stdClass();
        $add->container = $par->contenedor;
        $add->id = $par->parametro_id;
        $add->name = $par->parametro_name;
        $add->criteria = json_decode($par->parametro_criteria);
        $add->total = $total;
        #Sino existe un registro... sera 0
        if($statusToParams == false){
            $add->flag  = 0;
        } else {
            #Si existe debe proveerse...
            $jsonStd = json_decode($statusToParams['data']);
            
            foreach($jsonStd as $st){
                if($st->id == $add->id){
                    $add->flag = $st->flag;
                }
            }
        }
        array_push($parametros, $add);
    }

    #Consultar existentes...
    $scores_general_existing_sql = "select gc.data from sasa_match_core_score_general gc where entry = '{$entry}';";
    $scores_general_existing_query = $database->query($scores_general_existing_sql);
    $scores_general_existing_query->setFetchMode(PDO::FETCH_ASSOC);
    $scores_general_existing_result = $scores_general_existing_query->fetch();

    if($scores_general_existing_result == false){
        $generalParams = $parametros;
        $toGeneralPoints = 0;
    } else {
        $generalParams = array();
        $fetchParams = json_decode($scores_general_existing_result['data']);
        $toGeneralPoints = 0;
        foreach($parametros as $per){
            $nf = $per;
            foreach($fetchParams as $ft){
                if($nf->name == $ft->name){
                    $nf->comment = $ft->comment;

                    foreach($ft->criteria as $crf){
                        $indexTarget = array_search($crf->title, array_column($nf->criteria, 'title'));
                        $nf->criteria[$indexTarget]->qualpoints = $crf->qualpoints;
                    }
                }   
            }
            array_push($generalParams, $nf);
        }
        for($i=0; $i<count($generalParams); $i++){
            for($j=0; $j<count($generalParams[$i]->criteria); $j++){
                $toGeneralPoints = $toGeneralPoints + $generalParams[$i]->criteria[$j]->qualpoints;
            }
        }
    }
    #Consultar existentes...

    $return = array(
        "participation" => array(
            "id" => $entry,
        ),
        "judging" => $generalParams,
        "generalTotal" => $toGeneralPoints,
    );

    return new Response(json_encode($return));
});

/**
 * RETORNA DATA PARA EVALUAR ENTRY SEGUN JUEZ CONECTADO PARA SLOTS DE PENALTY
 */ 

$juzging->get('/asigmentsToJudge/penalty/{entry}', function(Request $request, $entry) use ($dbo, $database){
    $token = $request->headers->get('Authorization');
    if(empty($token)){
        return new Response(json_encode(['No authorized']), 401);
    }

    $validate = AuthHelper::verify($token);
    
    if($validate == false){
        return new Response(json_encode("Unauthorized"), 401);
    }

    $datos_usuario = Extended::getUserByEmail($validate->data->email);
    $juezid = (int) $datos_usuario['id'];
    $juezserial = $datos_usuario['serial'];

    $authmodel = new Auth;
    $isManager = $authmodel->userJoomlaManager($juezid);

    $participation=$entry;

    $datos_entry_sql = <<<SQL
    SELECT par.id, par.category_id, par.created_by, par.data->>'video_url' as video,  par.partipant_id, par.event_id FROM sasa_match_modules_participations as par where par.id = '{$entry}';
    SQL;

    $datos_entry_query = $database->query($datos_entry_sql);
    if(is_null($datos_entry_query)){
        return new Response(json_encode(['Entry no found']), 404);
    }

    $datos_entry_query->setFetchMode(PDO::FETCH_OBJ);
    $datos_entry_result = $datos_entry_query->fetch();
    
    #datos para jueces tipo penalties.
    if($isManager==false):
        $datos_panel_penal_sql = <<<SQL
        select
        DISTINCT penalties.id penalty_id,
        each_penal->>'id' as contenedor,
        penalties.title penalty_name,
        penalties.data->>'desc' penalty_desc,
        penalties.points::int penalty_points
        from sasa_match_core_panels_categories as panelcat 
        left join sasa_match_core_panels as panel on panel.id = panelcat.panelid
        cross join json_array_elements(panel.penaltyjudge) as each_penal
        cross join json_array_elements(each_penal->'params') as each_penalty
        left join sasa_match_core_parameters_penalty as penalties on penalties.id = (each_penalty->>'id')::int
        where panelcat.categoryid = '{$datos_entry_result->category_id}' and (each_penal->>'penalty') = '{$juezserial}'
        SQL;
    else:
        $datos_panel_penal_sql = <<<SQL
        select
        DISTINCT penalties.id penalty_id,
        each_penal->>'id' as contenedor,
        penalties.title penalty_name,
        penalties.data->>'desc' penalty_desc,
        penalties.points::int penalty_points
        from sasa_match_core_panels_categories as panelcat 
        left join sasa_match_core_panels as panel on panel.id = panelcat.panelid
        cross join json_array_elements(panel.penaltyjudge) as each_penal
        cross join json_array_elements(each_penal->'params') as each_penalty
        left join sasa_match_core_parameters_penalty as penalties on penalties.id = (each_penalty->>'id')::int
        where panelcat.categoryid = '{$datos_entry_result->category_id}'
        SQL;
    endif;

    $datos_panel_penal_result = $dbo->setQuery($datos_panel_penal_sql)->loadObjectList();

    if(is_null($datos_panel_penal_result)){
        return new Response(json_encode(['ERROR INTERNO']), 500);
    }

    /*********FIND STATUS PENALTY*********/
    $statusToPenal = Status::getStatus(2, $entry);
    /*********FIND STATUS PENALTY*********/

    $findExistingQP = "select gc.data from sasa_match_core_score_penalty gc where entry = '{$participation}';";

    $rps = $database->query($findExistingQP);
    if(is_null($rps)){
        return new Response(json_encode(['ERROR INTERNO']), 500);
    }
    $rps->setFetchMode(PDO::FETCH_ASSOC);
    $fetchPenal = $rps->fetch();
    
    $penalties  = array();

    foreach($datos_panel_penal_result as $i => $par){
        $add = new stdClass();
        $add->container = $par->contenedor;
        $add->id = $par->penalty_id;
        $add->name = $par->penalty_name;
        $add->points = $par->penalty_points;
        if($statusToPenal == false){
            $add->flag = 2;
        } else {
            #Si existe debe proveerse...
            $jsonStd = json_decode($statusToPenal['data']);           

            forEach($jsonStd as $k => $penalSt){
                if($penalSt->id == $add->id){
                    if(!$add->flag){
                        $add->flag = $penalSt->flag;
                    }
                }
            }
            
        }
        
        array_push($penalties, $add);
    }

    if($fetchPenal == false){
        $generalPenalties = $penalties;
        $toPenaltyPoinst = 0;
        for($i=0; $i<count($generalPenalties); $i++){
            if(!$generalPenalties[$i]->judgements){
                $generalPenalties[$i]->judgements = [];
            }
        }
    } else {
        $generalPenalties = array();
        $fetchPenals = json_decode($fetchPenal['data']);
        $toPenaltyPoinst = 0;
        $pnl = array();
        foreach($penalties as $per){
            $nf = $per;
            $ixpen = array();
            foreach($fetchPenals as $ft){
                $in = array_search($ft->id, array_column($penalties, 'id'));
                if(!$penalties[$in]->judgements){
                    $penalties[$in]->judgements = [];
                }
                if(count($penalties[$in]->judgements) == 0){
                    $penalties[$in]->judgements = $ft->judgements;
                }
            }
        }
        $generalPenalties = $penalties;
        for($i=0; $i<count($generalPenalties); $i++){
            if(!is_array($generalPenalties[$i]->judgements)){
                $generalPenalties[$i]->judgements = [];
            }
            $cantPen = count($generalPenalties[$i]->judgements);
            $valPen  = doubleval($generalPenalties[$i]->points);
            $toPenaltyPoinst = $toPenaltyPoinst + ($cantPen * $valPen);
        }
    }

    $return = array(
        "participation" => array(
            "id" => $entry,
        ),
        "penalty" => $generalPenalties,
        "penaltyTotal" => $toPenaltyPoinst,
    );

    return new Response(json_encode($return));
});

/**
 * RETORNA DATA PARA EVALUAR ENTRY SEGUN JUEZ CONECTADO PARA SLOTS DE MAIN
 */ 

$juzging->get('/asigmentsToJudge/main/{entry}', function(Request $request, $entry) use ($dbo, $database){
    $token = $request->headers->get('Authorization');
    if(empty($token)){
        return new Response(json_encode(['No authorized']), 401);
    }

    $validate = AuthHelper::verify($token);
    
    if($validate == false){
        return new Response(json_encode("Unauthorized"), 401);
    }

    $datos_usuario = Extended::getUserByEmail($validate->data->email);
    $juezid = (int) $datos_usuario['id'];
    $juezserial = $datos_usuario['serial'];

    $participation = $entry;

    /**INITIAL INFO OF PARTICIPATION**/
    $query_participation = <<<SQL
    SELECT id, category_id, created_by, data->>'video_url' video, partipant_id, event_id FROM SASA_MATCH_MODULES_PARTICIPATIONS WHERE ID = '{$participation}'; 
    SQL;
    $findPart = $database->query($query_participation);
    if(is_null($findPart)){
        return new Response(json_encode(["NOT FOUND"]), 404);
    }
    $findPart = $findPart->fetch(PDO::FETCH_ASSOC);

    $query_parameters = <<<SQL
    SELECT
    PAR.id AS parametro_id,
    PAR.title AS parametro_name,
    PAR.description AS parametro_desc,
    PAR.criteria AS parametro_criteria
    FROM SASA_MATCH_CORE_CATEGORIES_PARAMETERS AS CATPAR
    LEFT JOIN SASA_MATCH_CORE_PARAMETERS AS PAR
    ON PAR.id = CATPAR.parametro
    LEFT JOIN SASA_MATCH_CORE_PANELS_CATEGORIES AS PCAT
    ON PCAT.categoryid = CATPAR.categoria
    LEFT JOIN SASA_MATCH_CORE_PANELS_EVENTS AS PEVENT 
    ON PEVENT.PANEL = PCAT.PANELID
    LEFT JOIN SASA_MATCH_CORE_PANELS AS PANEL
    ON PANEL.id = PCAT.panelid
    CROSS JOIN JSON_ARRAY_ELEMENTS(PANEL.generaljudge) AS EACH_GENERALJUDGE
    CROSS JOIN JSON_ARRAY_ELEMENTS(EACH_GENERALJUDGE->'params') AS EACH_PARAMS
    CROSS JOIN JSON_ARRAY_ELEMENTS(PAR.criteria) EACH_CRITERIA
    WHERE CATPAR.CATEGORIA = '{$findPart["category_id"]}' AND PEVENT.EVENT = '{$findPart["event_id"]}'
    GROUP BY PAR.id
    SQL;
    $findParameters = $dbo->setQuery($query_parameters)->loadObjectList();
    
    $query_penalty = <<<SQL
    SELECT
    DISTINCT PENALTIES.id penalty_id,
    PENALTIES.title penalty_name,
    PENALTIES.data->>'desc' penalty_desc,
    PENALTIES.points::int penalty_points
    FROM SASA_MATCH_CORE_PANELS_CATEGORIES PCAT
    LEFT JOIN SASA_MATCH_CORE_PANELS_EVENTS PEVENT ON PEVENT.PANEL = PCAT.PANELID
    LEFT JOIN SASA_MATCH_CORE_PANELS PANEL ON PANEL.id = PCAT.panelid
    CROSS JOIN JSON_ARRAY_ELEMENTS(PANEL.penaltyjudge) AS EACH_PENALTYJUDGE
    CROSS JOIN JSON_ARRAY_ELEMENTS(EACH_PENALTYJUDGE->'params') EACH_PENALS
    LEFT JOIN SASA_MATCH_CORE_PARAMETERS_PENALTY AS PENALTIES ON PENALTIES.id = (EACH_PENALS->>'id')::int
    WHERE PCAT.CATEGORYID = '{$findPart["category_id"]}'
    AND PEVENT.EVENT = '{$findPart["event_id"]}'
    SQL;

    $findPenalties = $dbo->setQuery($query_penalty)->loadObjectList();

    //var_dump($findPenalties);

    /*********FIND STATUS PARAMS*********/
    $statusToParams = Status::getStatus(3, $findPart['id']);
    /*********FIND STATUS PARAMS*********/
    
    /*********FIND STATUS PENALTY*********/
    $statusToPenal = Status::getStatus(2, $findPart['id']);
    /*********FIND STATUS PENALTY*********/


    /**VARIABLES DE RETORNO**/
    $parametros = array();
    $penalties  = array();
    /**VARIABLES DE RETORNO**/

    foreach($findParameters as $i => $par){
        $crit = json_decode($par->parametro_criteria);
        $total = 0;
        foreach($crit as $e){
            $total = $total + $e->points;
        }
        $crit = json_decode($par->parametro_criteria);
        $add = new stdClass();
        $add->id = $par->parametro_id;
        $add->name = $par->parametro_name;
        $add->criteria = json_decode($par->parametro_criteria);
        $add->total = $total;
        #Sino existe un registro... sera 0
        if($statusToParams == false){
            $add->flag  = 0;
        } else {
            #Si existe debe proveerse...
            $jsonStd = json_decode($statusToParams['data']);
            
            foreach($jsonStd as $st){
                if($st->id == $add->id){
                    $add->flag = $st->flag;
                }
            }
        }
        array_push($parametros, $add);
    }
    foreach($findPenalties as $i => $par){
        $add = new stdClass();
        $add->id = $par->penalty_id;
        $add->name = $par->penalty_name;
        $add->points = $par->penalty_points;
        if($statusToPenal == false){
            $add->flag = 2;
        } else {
            #Si existe debe proveerse...
            $jsonStd = json_decode($statusToPenal['data']);

            

            forEach($jsonStd as $k => $penalSt){
                if($penalSt->id == $add->id){
                    if(!$add->flag){
                        $add->flag = $penalSt->flag;
                    }
                }
            }
            
        }
        
        array_push($penalties, $add);
    }
    $findexistintQ = "select gc.data from sasa_match_core_score_general gc where entry = '{$participation}';";

    $findExistingQP = "select gc.data from sasa_match_core_score_penalty gc where entry = '{$participation}';";

    $result = $database->query($findexistintQ);
    if(is_null($result)){
        return new Response(json_encode('Hello'), 500);
    }
    $result->setFetchMode(PDO::FETCH_ASSOC);
    $fetch = $result->fetch();
    
    if($fetch == false){
        $generalParams = $parametros;
        $toGeneralPoints = 0;
    } else {
        $generalParams = array();
        $fetchParams = json_decode($fetch['data']);
        $toGeneralPoints = 0;
        foreach($parametros as $per){
            $nf = $per;
            foreach($fetchParams as $ft){
                if($nf->name == $ft->name){
                    $nf->comment = $ft->comment;

                    foreach($ft->criteria as $crf){
                        $indexTarget = array_search($crf->title, array_column($nf->criteria, 'title'));
                        $nf->criteria[$indexTarget]->qualpoints = $crf->qualpoints;
                    }
                }   
            }
            array_push($generalParams, $nf);
        }
        for($i=0; $i<count($generalParams); $i++){
            for($j=0; $j<count($generalParams[$i]->criteria); $j++){
                $toGeneralPoints = $toGeneralPoints + $generalParams[$i]->criteria[$j]->qualpoints;
            }
        }
    }

    $rps = $database->query($findExistingQP);
    if(is_null($rps)){
        return new Response(json_encode(['Error']), 500);
    }
    $rps->setFetchMode(PDO::FETCH_ASSOC);
    $fetchPenal = $rps->fetch();

    if($fetchPenal == false){
        $generalPenalties = $penalties;
        $toPenaltyPoinst = 0;
        for($i=0; $i<count($generalPenalties); $i++){
            if(!$generalPenalties[$i]->judgements){
                $generalPenalties[$i]->judgements = [];
            }
        }
    } else {
        $generalPenalties = array();
        $fetchPenals = json_decode($fetchPenal['data']);
        $toPenaltyPoinst = 0;
        $pnl = array();
        foreach($penalties as $per){
            $nf = $per;
            $ixpen = array();
            foreach($fetchPenals as $ft){
                $in = array_search($ft->id, array_column($penalties, 'id'));
                if(!$penalties[$in]->judgements){
                    $penalties[$in]->judgements = [];
                }
                if(count($penalties[$in]->judgements) == 0){
                    $penalties[$in]->judgements = $ft->judgements;
                }
            }
        }
        $generalPenalties = $penalties;
        //var_dump($generalPenalties);
        for($i=0; $i<count($generalPenalties); $i++){
            if(!is_array($generalPenalties[$i]->judgements)){
                $generalPenalties[$i]->judgements = [];
            }
            $cantPen = count($generalPenalties[$i]->judgements);
            $valPen  = doubleval($generalPenalties[$i]->points);
            $toPenaltyPoinst = $toPenaltyPoinst + ($cantPen * $valPen);
        }
    }
 
    $return = [
        "participation" => [
            "id" => $participation,
            "video" => $findPart['video'],
            "category" => Matchs::getCategoriesById($findPart['category_id']),
            "event_id" => $findPart['event_id'],
            "participant_id" => $findPart['partipant_id'],
            "statusData" => Status::getStatusParticipation($participation),
        ],
        "judging" => $generalParams,
        "penalty" => $generalPenalties,
        "generalTotal" => $toGeneralPoints,
        "penaltyTotal" => $toPenaltyPoinst,
    ];

    return new Response(json_encode($return), 200);
});


$juzging->mount('/claims', function($claims) use($database, $dbo) {
    
    $claims->post('newcase', function() use($database, $dbo){
        $data = json_decode($_POST['form']);
        if(!is_object($data)){
            return new Response("BAD DATA", 500);
        }
        $sqlpreview = "SELECT * FROM sasa_match_core_modules_participations_messages  WHERE entryid = '{$data->entryid}' AND parametro = '{$data->parametro}'";
        $findExisting = $database->query($sqlpreview);
        if(is_null($findExisting)){
            return new Response(json_encode(['Error']), 500);
        }
        $findExisting->setFetchMode(PDO::FETCH_ASSOC);
        $findExistingFetch = $findExisting->fetch();
        if($findExistingFetch){
            $newresult = $findExistingFetch;
            $newresult['messages'] = json_decode($newresult['messages']);
            return new Response(json_encode(["claim" => $newresult]), 200);
        }

        /*$result = Claims::createNewCase($data->judge, $data->typecase, $data->eventid, $data->entryid, $data->parametro, $data->competitor);
        if(is_array($result)){
            return new Response(json_encode(["claim" => $result]), 400);
        }

        $information = $database->query("SELECT * FROM sasa_match_core_modules_participations_messages  WHERE ID = '{$result}'");
        $information->setFetchMode(PDO::FETCH_ASSOC);
        $informationFetch = $information->fetch();*/
        $newresult = []; 
        $newresult['messages'] = [];
        $eventsData = Claims::getEvents();
        $newresult['event'] = 0;
        $newresult['firsttime'] = true;
        $competitorsData = Claims::getCompetitors();
        $newresult['competitor_data'] = 0;
        return new Response(json_encode(["claim" => $newresult]), 200);
    });    
    $claims->post('newcasefirsttime', function() use($database, $dbo){
        $data = json_decode($_POST['form']);
        if(!is_object($data)){
            return new Response("BAD DATA", 500);
        }
        $sqlpreview = "SELECT * FROM sasa_match_core_modules_participations_messages  WHERE entryid = '{$data->entryid}' AND parametro = '{$data->parametro}'";
        $findExisting = $database->query($sqlpreview);
        if(is_null($findExisting)){
            return new Response(json_encode(['error']), 500);
        }
        $findExisting->setFetchMode(PDO::FETCH_ASSOC);
        $findExistingFetch = $findExisting->fetch();
        if($findExistingFetch){
            $newresult = $findExistingFetch;
            $newresult['messages'] = json_decode($newresult['messages']);
            return new Response(json_encode(["claim" => $newresult]), 200);
        }

        $result = Claims::createNewCase($data->judge, $data->typecase, $data->eventid, $data->entryid, $data->parametro, $data->competitor);
        if(is_array($result)){
            return new Response(json_encode(["claim" => $result]), 400);
        }

        $information = $database->query("SELECT * FROM sasa_match_core_modules_participations_messages  WHERE ID = '{$result}'");
        $information->setFetchMode(PDO::FETCH_ASSOC);
        $informationFetch = $information->fetch();
        $newresult = $informationFetch;
        $MessagesJSON = json_decode($newresult['messages']);
        $MessagesJSON = array_map(function($e){
            return ( (array) $e );
        }, $MessagesJSON);
        $MessagesOrder = array();
        usort($MessagesJSON, Helper\build_sorter('date', 'desc'));
        foreach($MessagesJSON as $p => $pa){
            array_push($MessagesOrder, $pa);
        }
        $newresult['messages'] = $MessagesOrder;
        $eventsData = Claims::getEvents();
        $newresult['event'] = $eventsData[ $newresult['eventid'] ];
        $competitorsData = Claims::getCompetitors();
        $newresult['competitor_data'] = $competitorsData[ $newresult['competitor'] ];
        return new Response(json_encode(["claim" => $newresult]), 200);
    });

    $claims->post('newmessage', function() use($database, $dbo){
        $data = json_decode($_POST['form']);
        if(!is_object($data)){
            return new Response("BAD DATA", 500);
        }

        if(!$data->id){
            return new Response("BAD DATA", 500);
        }

        $result = Claims::updateMessage($data->id, $data->messages);

        if(is_array($result)){
            return new Response(json_encode(["claim" => $result]), 400);
        }

        $information = $database->query("SELECT * FROM sasa_match_core_modules_participations_messages  WHERE ID = '{$data->id}'");
        $information->setFetchMode(PDO::FETCH_ASSOC);
        $informationFetch = $information->fetch();
        $newresult = $informationFetch;
        $MessagesJSON = json_decode($newresult['messages']);
        $MessagesJSON = array_map(function($e){
            return ( (array) $e );
        }, $MessagesJSON);
        $MessagesOrder = array();
        usort($MessagesJSON, Helper\build_sorter('date', 'desc'));
        foreach($MessagesJSON as $p => $pa){
            array_push($MessagesOrder, $pa);
        }
        $newresult['messages'] = $MessagesOrder;
        $eventsData = Claims::getEvents();
        $competitorsData = Claims::getCompetitors();
        $newresult['event'] = $eventsData[ $newresult['eventid'] ];
        $newresult['competitor_data'] = $competitorsData[ $newresult['competitor'] ];
        return new Response(json_encode(["claim" => $newresult]), 200);
    });
    
    $claims->get('/allcases', function() use ($database, $dbo){
        $query = <<<SQL
        SELECT
        tickets.id as id,
        params.title as parametro_name,
        events.title as evento_name,
        comp.data->>'name' as competitor_name,
        categories.title as category_name,
        CONCAT(events.title, '_', categories.title, '_', params.title, '_', comp.data->>'name') as text
        FROM SASA_MATCH_CORE_MODULES_PARTICIPATIONS_MESSAGES AS tickets
        LEFT JOIN 
        DBLINK('dbname=apijoomla',
            'select id, title from sasa_match_core_parameters AS par') AS params(ID bigint, TITLE text)
        ON params.id = tickets.parametro
        LEFT JOIN 
        DBLINK('dbname=apijoomla',
            'select id, title from sasa_content AS con') AS events(ID bigint, TITLE text)
        ON events.id = tickets.eventid
        LEFT JOIN SASA_MATCH_COMPETITOR AS comp
        ON comp.id = tickets.competitor
        LEFT JOIN SASA_MATCH_MODULES_PARTICIPATIONS AS PART
        ON PART.id = tickets.entryid
        LEFT JOIN 
        DBLINK('dbname=apijoomla',
            'select id, title from sasa_match_core_categories AS par') AS categories(ID bigint, TITLE text)
        ON categories.id = PART.category_id
        WHERE tickets.status = 0;
        SQL;

        $result = $database->query($query);
        if($database->error){
            return new Response(json_encode($database->error), 400);
        }
        $result->setFetchMode(PDO::FETCH_ASSOC);
        $resultFetch = $result->fetchAll();
        return new Response(json_encode($resultFetch), 200);
    });

    $claims->get('/case/{id}', function($id) use ($database, $dbo){
        $information = $database->query("SELECT * FROM sasa_match_core_modules_participations_messages  WHERE ID = '{$id}'");
        $information->setFetchMode(PDO::FETCH_ASSOC);
        $informationFetch = $information->fetch();
        $newresult = $informationFetch;
        $MessagesJSON = json_decode($newresult['messages']);
        $MessagesJSON = array_map(function($e){
            return ( (array) $e );
        }, $MessagesJSON);
        $MessagesOrder = array();
        usort($MessagesJSON, Helper\build_sorter('date', 'desc'));
        foreach($MessagesJSON as $p => $pa){
            array_push($MessagesOrder, $pa);
        }
        $newresult['messages'] = $MessagesOrder;
        $eventsData = Claims::getEvents();
        $competitorsData = Claims::getCompetitors();
        $newresult['event'] = $eventsData[ $newresult['eventid'] ];
        $newresult['competitor_data'] = $competitorsData[ $newresult['competitor'] ];
        return new Response(json_encode(["claim" => $newresult]), 200);
    });
});

#ATLETAS............................................
$juzging->post('/athlete/approve', function() use ($database){

    $data = json_decode($_POST['form']);
    if(!is_object($data)){
        return new Response(json_encode(["BAD DATA"]), 500);
    }

    $update = <<<SQL
    UPDATE sasa_match_core_score_general_finally SET status = :status WHERE entry = :entry;
    SQL;

    $STM1 = $database->pdo->prepare($update)->execute(["status" => 1, "entry" => $data->entryid]);
    
    $update2 = <<<SQL
    UPDATE sasa_match_core_score_penalty_finally SET status = :status WHERE entry = :entry;
    SQL;

    $STM2 = $database->pdo->prepare($update2)->execute(["status" => 1, "entry" => $data->entryid]);

    if($STM1 == false || $STM2 == false){
        return new Response(["Bad request"], 400);
    }

    return new Response(json_encode(["OK request"]), 200);

});

$juzging->post('/athlete/approvewithtime', function() use ($database){

    $data = json_decode($_POST['form']);
    if(!is_object($data)){
        return new Response(json_encode(["BAD DATA"]), 500);
    }

    $response = Status::validateAndUpdateAthleteStatus($data->entry);

    return new Response(json_encode($response), 200);

});



?>