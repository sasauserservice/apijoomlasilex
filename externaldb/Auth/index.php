<?php

use Helper\AuthHelper;
use Helper\Extended;
use Helper\Status;
use Helper\Matchs;
use Helper\Claims;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Model\Auth AS AuthModel;

$auth->get('/globalinfo', function(Request $request) use ($dbo, $database){
    $token = $request->headers->get('Authorization');
    
    $validate = AuthHelper::verify($token);
    
    $datos_usuario = Extended::getUserByEmail($validate->data->email);

    $juezid =  $datos_usuario['id'];

    $authmodel = new AuthModel;
    $isManager = $authmodel->userJoomlaManager($juezid);

    if($isManager){
        $miseventos = <<<SQL
        select
        cont.id,
        cont.title
        from  sasa_content as cont
        ;
        SQL; 
    }else{
        #JUCES GENERAL
    $miseventos = <<<SQL
    select
    cont.id,
    cont.title
    from sasa_match_core_panels as panel
    cross join json_array_elements(panel.generaljudge) each_general
    left join sasa_match_core_panels_events as pevent
    on pevent.panel = panel.id
    left join sasa_content as cont
    on cont.id = pevent.event
    where (each_general->>'general') = '{$juezid}';
    SQL;
    }

    

    $resulsetMisEventos = $dbo->setQuery($miseventos)->loadObjectList();

    $response = array(
        "events" => [
            "full" => $resulsetMisEventos,
            "onlyid" => array_map(function($e){
                return $e->id;
            }, $resulsetMisEventos) 
        ]
    );

    return new Response(json_encode($response));
});


$auth->get('/getinfouser', function(Request $request) use ($dbo, $database){
    $token = $request->headers->get('Authorization');
    $validate = AuthHelper::verify($token);
    $datos_usuario = Extended::getUserByEmail($validate->data->email);
    $juezid = (int) $datos_usuario['id'];

    $return = array(
        $validate
    );
    return new Response(json_encode($return));
});

$auth->get('/authenticate', function(Request $request) use ($database, $dbo){
    
    $token = $request->headers->get('Authorization');

    $validate = AuthHelper::verify($token);

    return new Response(json_encode($validate), 200);
});

$auth->get('/test', function(Request $request) use ($database, $dbo){
    $sql = <<<SQL
    SELECT joomla.* from DBLINK('dbname=apijoomla', 'SELECT id, title from sasa_usergroups') AS joomla(id bigint, title text) UNION SELECT id, title from sasa_usergroups order by id
    SQL;
    $rs = $database->query($sql);
    $rs->setFetchMode(PDO::FETCH_ASSOC);
    $result = $rs->fetchAll();

    $user = \JFactory::getUser(1);
    $array_grupos = array();
    foreach($user->groups as $k => $v){
        $index = array_search($k, array_column($result, 'id'));
        array_push( $array_grupos, $result[$index]['title'] );
    }
    $user->groups = $array_grupos;
    var_dump($user);
   return 1;
});

$auth->post('/initapp', function(Request $request) use ($database, $dbo){
    //$request = Request::createFromGlobals();
    $form    = json_decode($request->get('form', null));
    $model   = new AuthModel;
    $user    = $model->validateLogin($form->email, $form->password);

    if(!$user){
        return new Response(json_encode(["User not authenticated"]), 403);
    }

    $jwt = AuthHelper::init($user);

    return new Response(json_encode([
        "data" => $user,
        "authorization" => $jwt,
    ]), 200);
});

?>