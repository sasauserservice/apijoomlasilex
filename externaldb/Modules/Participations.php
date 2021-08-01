<?php

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Helper\Status;
use Helper\AuthHelper;
use Helper\Extended;
use Model\Auth AS AuthModel;


$extdb->get('/', function() use ($database) {
    $result = $database->select('match_modules_participations', '*');
    return json_encode($result);
});

$extdb->get('/eventstongselect', function() use($database, $dbo){
    $articles = $dbo->setQuery("SELECT id, title as text FROM #__content WHERE catid = '9'")->loadObjectList();
    return new Response(json_encode($articles));
});

/*****ARCHIVO DE SOL PARA PRUEBA DE ALGORITMO*****/
require_once('./externaldb/Modules/sol_participations.php');
/*****ARCHIVO DE SOL PARA PRUEBA DE ALGORITMO*****/

$extdb->get('/participations', function(Request $req) use($database, $dbo){
    $token = $req->headers->get('Authorization');
    $validate = AuthHelper::verify($token);
    $datos_usuario = Extended::getUserByEmail($validate->data->email);
    $juezid = (int) $datos_usuario['id'];

    $authmodel = new AuthModel;
    $isManager = $authmodel->userJoomlaManager($juezid);

    if($isManager == true){
        $todasQ = <<<SQL
        SELECT TO_CHAR(PARTI.created::DATE, 'Mon dd, yyyy') CREATED, PARTI.id, PARTI.event_id, PARTI.category_id, PARTI.data->>'video_url' video, COMP.type,COMP.data->>'name' as team_name, COMP.id team_id FROM sasa_match_modules_participations PARTI LEFT JOIN sasa_match_competitor COMP ON COMP.id = PARTI.partipant_id;
        SQL;

        $result = $database->query($todasQ)->fetchAll(PDO::FETCH_ASSOC);

        $rs = array();
        foreach($result as $i => $row) {
            $fila = $row;
            $status = Status::getStatusParticipation($fila['id']);
            $fila['event_id'] = strval($fila['event_id']);
            $fila['category_id'] = strval($fila['category_id']);
            $fila['category_title'] = $dbo->setQuery("SELECT title from #__match_core_categories where id = '{$row['category_id']}'")->loadResult();
            $fila['event_title'] = $dbo->setQuery("SELECT title from #__content where id = '{$row['event_id']}'")->loadResult();
            $fila['status'] = ($status == false) ? ["entryid" => $fila['id'], "status" => 0] : $status;

            array_push($rs, $fila);
        }
        return new Response(json_encode($rs));
    } else {

        header("Content-Type: text/html; charset=UTF-8");

        $entry = \Helper\EntryTo($datos_usuario['serial'], $datos_usuario['id']);

        var_dump($entry);
        
        return '';
    }
});

$extdb->get('/participationsbyuser', function() use($database, $dbo){
    $todasQ = <<<SQL
    SELECT TO_CHAR(PARTI.created::DATE, 'Mon dd, yyyy') CREATED, PARTI.id, PARTI.event_id, PARTI.category_id, PARTI.data->>'video_url' video, COMP.type,COMP.data->>'name' as team_name, COMP.id team_id FROM sasa_match_modules_participations PARTI LEFT JOIN sasa_match_competitor COMP ON COMP.id = PARTI.partipant_id;
    SQL;

    $result = $database->query($todasQ)->fetchAll(PDO::FETCH_ASSOC);

    $rs = array();
    foreach($result as $i => $row) {
        $fila = $row;
        $status = Status::getStatusParticipation($fila['id']);
        $fila['event_id'] = strval($fila['event_id']);
        $fila['category_id'] = strval($fila['category_id']);
        $fila['category_title'] = $dbo->setQuery("SELECT title from #__match_core_categories where id = '{$row['category_id']}'")->loadResult();
        $fila['event_title'] = $dbo->setQuery("SELECT title from #__content where id = '{$row['event_id']}'")->loadResult();
        $fila['status'] = ($status == false) ? ["entryid" => $fila['id'], "status" => 0] : $status;

        array_push($rs, $fila);
    }

    return new Response(json_encode($rs));
});

$extdb->get('/list/{event}', function($event) use ($database, $dbo) {
    $queryMain = <<<SQL
    SELECT distinct PARTI.ID ID, PARTI.partipant_id TEAM_ID, PARTI.category_id, COMP.DATA->>'name' TEAM_NAME,
    PARTI.DATA->>'video_url' video_url FROM SASA_MATCH_MODULES_PARTICIPATIONS AS PARTI LEFT JOIN SASA_MATCH_COMPETITOR COMP ON COMP.ID = PARTI.partipant_id WHERE PARTI.event_id = '{$event}'
    SQL;

    $result = $database->query($queryMain)->fetchAll(PDO::FETCH_ASSOC);

    $rs = array();
    foreach($result as $i => $row) {
        $fila = $row;
        $fila['category_id'] = strval($fila['category_id']);
        $fila['category_title'] = $dbo->setQuery("SELECT title from #__match_core_categories where id = '{$row['category_id']}'")->loadResult();

        array_push($rs, $fila);
    }

    return json_encode($rs);

    //return json_encode($result);
});

$extdb->get('/categories/event/{event}', function($event) use ($dbo){
    $query = <<<SQL
    SELECT DISTINCT MCAT.id, MCAT.title as text FROM #__MATCH_CORE_CATEGORIES AS MCAT LEFT JOIN #__MATCH_CORE_PANELS_CATEGORIES AS MPANCAT ON MPANCAT.categoryid = MCAT.id LEFT JOIN #__match_core_panels_events AS MPANEVENT ON MPANEVENT.panel = MPANCAT.panelid WHERE MPANEVENT."event" = '{$event}'
    SQL;
    
    $result = $dbo->setQuery($query)->loadObjectList();

    return new Response(json_encode($result), 200);
});

$extdb->get('/categories/all', function() use ($dbo){
    $query = <<<SQL
    SELECT MCAT.id, MCAT.title as text,MPANEVENT.id as event FROM #__MATCH_CORE_CATEGORIES AS MCAT LEFT JOIN #__MATCH_CORE_PANELS_CATEGORIES AS MPANCAT ON MPANCAT.categoryid = MCAT.id LEFT JOIN #__match_core_panels_events AS MPANEVENT ON MPANEVENT.panel = MPANCAT.panelid
    SQL;
    
    $result = $dbo->setQuery($query)->loadObjectList();

    return new Response(json_encode($result), 200);
});

$extdb->get('/categoriesAll', function() use ($dbo){
    $query = <<<SQL
    SELECT DISTINCT MCAT.id, MCAT.title as text FROM #__MATCH_CORE_CATEGORIES AS MCAT LEFT JOIN #__MATCH_CORE_PANELS_CATEGORIES AS MPANCAT ON MPANCAT.categoryid = MCAT.id LEFT JOIN #__match_core_panels_events AS MPANEVENT ON MPANEVENT.panel = MPANCAT.panelid
    SQL;
    
    $result = $dbo->setQuery($query)->loadObjectList();

    return new Response(json_encode($result), 200);
});

$extdb->post('/testinsert', function() use ($database){
    $data = json_decode($_POST['form']);
    $error = 0;
    if(!is_object($data) ){
        return new Response(json_encode(['Error']), 500);
    }

    for($i=0;$i<5;$i++){
        $inQuery = <<<SQL
        INSERT INTO sasa_test(data) VALUES ('PEPE') RETURNING id;
        SQL;
        $result = $database->query($inQuery);
        $result->setFetchMode(PDO::FETCH_ASSOC);
        $fetchPenal = $result->fetch();

        var_dump($fetchPenal);
    }
});

$extdb->post('/create', function() use ($database){
    $data = json_decode($_POST['form']);
    $error = 0;
    if(!is_object($data) ){
        return new Response(json_encode(['Error']), 500);
    }

    $error = 0;
    
    if(count($data->data) > 0){
        for($i=0;$i<count($data->data);$i++){
            $is = [
                "data" => json_encode(["video_url" => $data->data[$i]->video_url]),
                "category_id" => $data->data[$i]->categoryId,
                "partipant_id" => $data->data[$i]->teamId,
                "created_by" => $data->conected_user,
                "event_id" => $data->event_id,
                "event_serial" => \Helper\get_serial('sasa_content', $data->event_id),
                "category_serial" => \Helper\get_serial('sasa_match_core_categories', $data->data[$i]->categoryId),
                "participant_serial" => \Helper\get_serialExtender('sasa_match_competitor', $data->data[$i]->teamId),
            ];

            $inQuery = <<<SQL
            INSERT INTO sasa_match_modules_participations(data, category_id, event_id, partipant_id, created_by, event_serial, category_serial, participant_serial) VALUES ('{$is["data"]}', '{$is["category_id"]}', '{$is["event_id"]}','{$is["partipant_id"]}', '{$is["created_by"]}', '{$is["event_serial"]}', '{$is["category_serial"]}', '{$is["participant_serial"]}') RETURNING id;
            SQL;
            $result = $database->query($inQuery);
            $result->setFetchMode(PDO::FETCH_ASSOC);
            $fetchPenal = $result->fetch();

            if($database->error != null){
                $error++;
            }

            if(is_array($fetchPenal)){
                Status::setStatusParticipation($fetchPenal['id'], 0, 2,0);
            }
        }

        if($error > 0){
            return new Response(json_encode(['Error']), 400);
        }

        return new Response(json_encode(['Saved']), 200);
    }
});

$extdb->post('/update', function() use ($database){
    $data = json_decode($_POST['form']);
    $error = 0;
    if(!is_object($data) ){
        return new Response(json_encode(['Data is bad']), 500);
    }

    $update = array();    
    
    if(count($data->data) > 0){
        for($i=0;$i<count($data->data);$i++){
            $update = [
                "data" => json_encode(["video_url" => $data->data[$i]->video_url]),
                "category_id" => intval($data->data[$i]->categoryId),
                "partipant_id" => $data->data[$i]->teamId,
                "created_by" => $data->conected_user,
                "event_id" => $data->event_id, 
                "event_serial" => \Helper\get_serial('sasa_content', $data->event_id),
                "category_serial" => \Helper\get_serial('sasa_match_core_categories', $data->data[$i]->categoryId),
                "participant_serial" => \Helper\get_serialExtender('sasa_match_competitor', $data->data[$i]->teamId),
            ];



            $where = [
                "id" => $data->data[$i]->id,
            ];

            $result = $database->update('match_modules_participations', $update, $where);

            if( $result->rowCount() == 0 ){
                $error++;
            }
        }

        if($error > 0){
            return new Response(json_encode(['Error']), 400);
        }

        return new Response(json_encode(['Update']), 200);
    }
});

$extdb->post('/deleteparticipation/{idpart}', function($idpart) use ($database){
    $result = $database->delete('match_modules_participations', [
        "id" => $idpart
    ]);



    if($database->error){
        return new Response(json_encode(['Error', $database->error]), 400);
    }

    return new Response(json_encode(['Delete ok', $database->error]), 200);
});

?>