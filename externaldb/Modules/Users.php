<?php

use Symfony\Component\HttpFoundation\Response;
use Medoo\Medoo;
use Helper\Extended;
use Model\Auth as AuthModel;

$community->get('/', function() use ($database) {
    return 1;
});

$community->get('/list', function() use ($database){
    /**RETORNA DATOS DE USUARIOS PARA MATCH**/ 
    /**POR AHORA SOLO LA LISTA DEL USUARIO CON LOS TIPOS DEL USUARIO**/
    $query = <<<SQL
    SELECT * FROM SASA_MATCH_USERS;
    SQL;
    $result = $database->query($query)->fetchAll(PDO::FETCH_ASSOC);
    $response = array();
    
    foreach($result as $i => $user){
        $authm = new AuthModel;
        $new = $user;
        $groups = Extended::getGroupsFromUser($user['id']);
        $new['groups'] = $groups;
        $new['groups_ids'] = $authm->getGorupsOfUser($user['id'])->getOnlyGroupsId(true);
        array_push($response, $new);
    }

    return new Response(json_encode($response), 200);
});

$community->get('/group/{group}', function($group) use ($database){
    /**RETORNA DATOS DE USUARIOS PARA MATCH**/ 
    /**POR AHORA SOLO LA LISTA DEL USUARIO CON LOS TIPOS DEL USUARIO**/
    $query = <<<SQL
    SELECT  CONCAT(USR.ID,'__',USR.SERIAL) AS ID, USR.EMAIL AS TEXT, USR.ID AS IDENTIFICADOR FROM SASA_MATCH_USERS USR LEFT JOIN SASA_USER_USERGROUP_MAP AS USRMAP ON USRMAP.user_id = USR.id LEFT JOIN sasa_usergroups USRG ON USRG.id = USRMAP.group_id WHERE LOWER(USRG.title) = '{$group}'
    SQL;
    $result = $database->query($query)->fetchAll(PDO::FETCH_ASSOC);
    $response = array();
    
    foreach($result as $i => $user){
        $new = $user;
        array_push($response, $new);
    }

    return new Response(json_encode($response), 200);
});

$community->post('/create-user', function() use ($database){
    $data = json_decode($_POST['form']);
    $error = 0;
    if(!is_object($data) ){
        return new Response(json_encode(['Error']), 500);
    }

    $dataP = $database->query("SELECT COUNT(usr.id) as cantidad  FROM sasa_match_users as usr WHERE LOWER(usr.email) = '".strtolower($data->email)."'")->fetchAll(PDO::FETCH_ASSOC);

    if($dataP[0]['cantidad'] > 0){
        return new Response(json_encode(["Email has exists"]), 401);
    }

    $insert = [
        "name"     => $data->name,
        "email"    => $data->email,
        "password" => Medoo::raw("crypt('".$data->password."', gen_salt('bf'))"),
    ];

    $insertid = $database->insert('match_users', $insert);
     
    $account_id = $database->id();

    if($account_id){
        $map_user_group = array();
        for($i=0; $i<count($data->types); $i++){
            array_push($map_user_group, [
                "user_id" => $account_id,
                "group_id" => intval($data->types[$i]),
            ]);
        }

        $database->insert('user_usergroup_map', $map_user_group);
    }

    return new Response(json_encode(["Saved"]), 200);
});

$community->post('/delete-user/{user}', function($user) use($database){
    $database->delete('match_users', [
        "id" => $user,
    ]);

    if($database->error){
        return new Response(json_encode(["Error on server"]), 400);
    }

    return new Response(json_encode(["Delete ok"]), 200);
});

$community->post('/update-user', function() use ($database){
    $data = json_decode($_POST['form']);
    $error = 0;
    if(!is_object($data) ){
        return new Response(json_encode(['Error']), 500);
    }

    $user = Extended::getUserById($data->id);

    if(is_bool($user) || is_null($user)){
        return new Response(json_encode(['Error']), 500);
    }

    if( strtolower($user['email']) == strtolower($data->email) ){

    } else if(strtolower($user['email']) != strtolower($data->email)){
        $verify = Extended::getUserByEmail($data->email);
        if( $verify != false ){
            return new Response(json_encode(['EMAIL USED IN THE PLATFORM BY ANOTHER USER']), 401);
        }
    }

    if(trim($data->password) != ''){
        $update = [
            "name"     => $data->name,
            "email"    => $data->email,
            "status"   => $data->status,
            "password" => Medoo::raw("crypt('".$data->password."', gen_salt('bf'))"),
        ];
    } else {
        $update = [
            "name"     => $data->name,
            "email"    => $data->email,
            "status"   => $data->status,
        ];
    }

    $update = $database->update('match_users', $update, ['id' => $data->id]);

    if($database->error){
        return new Response(json_encode(["Error on update"]), 400);
    }

    if($update->rowCount() > 0){
        
        $deleteOld = $database->delete('user_usergroup_map', [
            "user_id" => $data->id,
        ]);

        $map_user_group = array();
        for($i=0; $i<count($data->types); $i++){
            array_push($map_user_group, [
                "user_id" => $data->id,
                "group_id" => intval($data->types[$i]),
            ]);
        }

        $database->insert('user_usergroup_map', $map_user_group);
    }

    return new Response(json_encode(["Saved"]), 200);
});

?>