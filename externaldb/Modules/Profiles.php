<?php

use Symfony\Component\HttpFoundation\Response;

$profiles->get('/', function() use ($database) {
    return 1;
});

$profiles->get('/list', function() use ($database) {
    $response = $database->query("SELECT profi.id, profi.type, profi.data->>'name' as text  FROM sasa_match_modules_profiles as profi")->fetchAll(PDO::FETCH_ASSOC);
    return new Response(json_encode($response), 200);
});

$profiles->post('/update', function() use ($database){
    $data = json_decode($_POST['form']);
    $error = 0;
    if(!is_object($data) ){
        return new Response(json_encode(["Bad data"]), 500);
    }

    $update = array();    
    
    if(count($data->data) > 0){
        for($i=0;$i<count($data->data);$i++){
            $update = [
                "type" => $data->data[$i]->type,
                "data" => json_encode($data->data[$i]->fields)
            ];

            $where = [
                "id" => $data->data[$i]->id,
            ];

            $result = $database->update('match_modules_profiles', $update, $where);

            if( $result->rowCount() == 0 ){
                $error++;
            }
        }

        if($error > 0){
            return new Response(json_encode(["Error"]), 400);
        }

        return new Response(json_encode(["Update"]), 200);
    }
});

$profiles->post('/create', function() use ($database) {
    $data = json_decode($_POST['form']);
    $error = 0;
    if(!is_object($data) ){
        return new Response(json_encode(["Bad data"]), 500);
    }

    $insert = array();
    
    if(count($data->data) > 0){
        for($i=0;$i<count($data->data);$i++){
            array_push($insert, [
                "type" => $data->data[$i]->type,
                "data" => json_encode($data->data[$i]->fields),
                "created_by" => $data->conected_user,
            ]);
        }

        $result = $database->insert('match_modules_profiles', $insert);

        if($result->rowCount()== 0){
            return new Response(json_encode(["Error"]), 400);
        }

        return new Response(json_encode(["Saved"]), 200);
    }
});

?>