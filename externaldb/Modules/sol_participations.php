<?php

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Helper\Status;
use Helper\AuthHelper;
use Helper\Extended;
use Model\Auth AS AuthModel;

$extdb->get('/solparticipations', function(Request $req) use($database, $dbo){
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

        /* FUNCION DEL BUSCADOR GLOBAL */
        /* FUNCION DEL BUSCADOR GLOBAL */        
        /* FUNCION DEL BUSCADOR GLOBAL */
        /* FUNCION DEL BUSCADOR GLOBAL */     


        header("Content-Type: text/html; charset=UTF-8");

        /**EJECUCION DE FUNCION**/
        $firstciclo = \Helper\loopGadgeSol($datos_usuario["serial"], 'sasa_match_modules_participations', 1);
        /*foreach($firstciclo as $cicle){
            echo "TABLA {$cicle['tabla']} \n";
            echo "CAMPO {$cicle['campo']} \n";
            echo "VALUE {$cicle['value']} \n\n";
        }*/
        /**EJECUCION DE FUNCION**/
        
        return '';
    }
});