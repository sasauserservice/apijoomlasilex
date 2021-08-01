<?php

use Medoo\Medoo;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Helper\Extended;
use Helper\Matchs;
use Helper\Claims;
use Helper\Status;
use Helper\AuthHelper;
use Model\Auth;

$competitor->get('/', function() use ($database){
    return new Response(json_encode(["Nothing"]), 200);
} );


$competitor->get('/list/competitor', function() use ($database){
    
    $query = <<<SQL
    SELECT ID, EMAIL AS TEXT, SERIAL FROM SASA_MATCH_USERS USR LEFT JOIN SASA_USER_USERGROUP_MAP UMAP ON UMAP.USER_ID = USR.ID WHERE UMAP.GROUP_ID = '{$type}';
    SQL;

    $result = $database->query($query)->fetchAll(PDO::FETCH_ASSOC);

    return new Response(json_encode($result), 200);
} );

$competitor->get('/list/{type}', function($type) use ($database){
    
    $query = '';
    
    if($type=='103'){
        $query = <<<SQL
        SELECT USR.ID as iden, USR.EMAIL AS TEXT, USR.SERIAL  as id FROM SASA_MATCH_USERS USR LEFT JOIN SASA_USER_USERGROUP_MAP UMAP ON UMAP.USER_ID = USR.ID WHERE UMAP.GROUP_ID = '{$type}';
        SQL;
    }

    $result = $database->query($query)->fetchAll(PDO::FETCH_ASSOC);

    return new Response(json_encode($result), 200);
} );

$competitor->get('/list', function(Request $req) use ($database){
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

    $authmodel = new Auth;
    $isManager = $authmodel->userJoomlaManager($juezid);

    if($isManager == true){
        $sql = <<<SQL
        SELECT ID, COMP.DATA, array_to_json(COMP.MANAGER) MANAGER, COMP.TYPE FROM SASA_MATCH_COMPETITOR COMP
        SQL;
        $result = array();
        $first = $database->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        foreach($first as $i => $p){
            $n = $p;
            $mana = json_decode($n['manager']);
            $n['real_manager'] = array();
            $n['manager'] = $mana;
            $n['data'] = json_decode($n['data']);
            $n['text'] = $n['data']->name;
            foreach($mana as $j=>$is){
                $realU = Extended::getUserById2((int)$is);
                array_push($n['real_manager'], [
                    "email" => $realU['email']
                ]);
               // var_dump($realU);
            }
            array_push($result, $n);
        }
        return new Response(json_encode($result), 200);
    } else {
        $queryFunc = <<<SQL
        SELECT * FROM search_whole_db('{$datos_usuario["serial"]}');
        SQL;

        $respuesta = $database->query($queryFunc);
        if(!is_null($respuesta)){
            $respuesta->setFetchMode(\PDO::FETCH_ASSOC);
            $rs = $respuesta->fetchAll();
            $map= array();
            foreach($rs as $e){
                if($e['_tbl']=='sasa_match_competitor'){
                    array_push($map, $e);
                }
            }
            $result = $map;
            $nuevo = array();
            foreach($result as $klave){
                $rk = <<<SQL
                select ID, COMP.DATA, array_to_json(COMP.MANAGER) MANAGER, COMP.TYPE from sasa_match_competitor as COMP where ctid='{$klave["_ctid"]}';
                SQL;
                $first = $database->query($rk)->fetch(PDO::FETCH_ASSOC);
                array_push($nuevo, $first);
            }

            $pop = array();

            foreach($nuevo as $i => $p){
                $n = $p;
                $mana = json_decode($n['manager']);
                $n['real_manager'] = array();
                $n['manager'] = $mana;
                $n['data'] = json_decode($n['data']);
                $n['text'] = $n['data']->name;
                foreach($mana as $j=>$is){
                    $realU = Extended::getUserById2($is);
                    array_push($n['real_manager'], [
                        "email" => $realU['email']
                    ]);

                    echo $realU;
                }
                array_push($pop, $n);
            }
        }
        return new Response(json_encode($pop), 200);
    }
} );

$competitor->post('/create', function() use ($database){
    $data = json_decode($_POST['form']);
        
    if(!is_object($data)){
        return new Response(json_encode(["BAD DATA"]), 500);
    }

    $lista = array_map(function($po){
        return json_encode($po);
    }, $data->managers);

    $insert = array(
        "manager" => '{'.implode(",", $lista).'}',
        "type"     => intval($data->type),
        "data"     => json_encode($data->data)
    );

    $database->insert("match_competitor", $insert);

    if($database->error){
        return new Response(json_encode(["Error on register", $database->error]), 400);
    }
    
    return new Response(json_encode(["Saved"]), 200);
} ); 



$competitor->post('/update', function() use ($database){
    $data = json_decode($_POST['form']);
    
    if(!is_object($data)){
        return new Response(json_encode(["BAD DATA"]), 500);
    }

    $lista = array_map(function($po){
        return json_encode($po);
    }, $data->managers);

    $update = array(
        "manager" => '{'.implode(",", $lista).'}',
        "type"     => intval($data->type),
        "data"     => json_encode($data->data)
    );

    $database->update("match_competitor", $update, ["id" => $data->id]);

    if($database->error){
        return new Response(json_encode(["Error on update", $database->error]), 400);
    }
    
    return new Response(json_encode(["Update"]), 200);
} );


$competitor->post('/delete/{id}', function($id) use ($database){

    $database->delete("match_competitor", ["id" => $id]);

    if($database->error){
        return new Response(json_encode(["Error on delete", $database->error]), 400);
    }
    
    return new Response(json_encode(["Delete"]), 200);
} );




?>