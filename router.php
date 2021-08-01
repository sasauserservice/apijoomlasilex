<?php

use Helper\Matchs;
use Helper\Status;
use Symfony\Component\HttpFoundation\Response;
  use Joomla\CMS\Uri\Uri;

global $Silex;
global $dbo;
global $database;
global $Joomla;
global $user;

$Silex->get( '/', function() use ($dbo) {
    return 'Hello JoomlaSilex';
} );

$Silex->get( '/users', function() use ($dbo) {
    $result = $dbo->setQuery("SELECT * FROM #__users")->loadObjectList();
    return json_encode($result);
} );

$Silex->get('/getData', function() use ($dbo, $user){
    return json_encode($user);
});


$Silex->post('/delete-event/{event}', function($event) use ($dbo){
	$delete = $dbo->setQuery("DELETE FROM sasa_content WHERE id = '{$event}'")->execute();
	if(!$delete){
		return new Response(json_encode(["Message" => "Delete ko"]), 400);
	}

	return new Response(json_encode(["Message" => "Delete successfully"]), 200);
});


$Silex->post('/create-match', function() use ($dbo, $Joomla, $user){

	
    $request_body = file_get_contents('php://input');
    $data = json_decode($request_body);
	$article = JTable::getInstance('content');
    $article->title = $data->title;
    $article->catid = 9;
    $article->alias = generate_string(10);

	$obj = new stdClass;
	$obj->type = $data->type;
	$obj->alias = $article->alias;
	$obj->parrafos = [];
    $obj->ft = false;


    $article->state = 1;
    $article->access = 1;
    $article->language = '*';
    $article->created  = JFactory::getDate()->toSQL();
    $article->created_by = $data->createdBy;
    $article->created_by_alias = JFactory::getUser($data->createdBy)->name;
    $article->metadata  = '{"page_title":"","author":"","robots":""}';
    $article->introtext = '';
    $article->fulltext = json_encode($obj) ;
    //$article->fulltext = '' ;
    $article->images = '{"image_intro":"","float_intro":"","image_intro_alt":"","image_intro_caption":"","image_fulltext":"","float_fulltext":"","image_fulltext_alt":"","image_fulltext_caption":""}';
    $article->urls = '{"urla":false,"urlatext":"","targeta":"","urlb":false,"urlbtext":"","targetb":"","urlc":false,"urlctext":"","targetc":""}';
    $article->attribs = '{"article_layout":"","show_title":"","link_titles":"","show_tags":"","show_intro":"","info_block_position":"","info_block_show_title":"","show_category":"","link_category":"","show_parent_category":"","link_parent_category":"","show_associations":"","show_author":"","link_author":"","show_create_date":"","show_modify_date":"","show_publish_date":"","show_item_navigation":"","show_icons":"","show_print_icon":"","show_email_icon":"","show_vote":"","show_hits":"","show_noauth":"","urls_position":"","alternative_readmore":"","article_page_title":"","show_publishing_options":"","show_article_options":"","show_urls_images_backend":"","show_urls_images_frontend":""}';
    $article->metadata = '{"robots":"","author":"","rights":"","xreference":""}';
    $article->metakey = '';
    $article->metadesc = '';

    if(!$article->store(true)){
        return new Response(json_encode(["Error" => true, "Message" => "Article not created", "Status" => 400]), 400);
    }

	$statusSend = Status::createStatusEvent($article->id);

    return new Response(json_encode(["Error" => false, "Message" => "Article created success", "Status" => 200, "Estatus" => $statusSend, "Data" => $article]), 200);
});
$Silex->post('/create', function() use ($dbo, $Joomla, $user){
    $request_body = file_get_contents('php://input');
    $data = json_decode($request_body);
    $article = JTable::getInstance('content');
    $article->title = $data->title;
    $article->catid = 8;
    $article->alias = generate_string(10);
    $article->state = 1;
    $article->access = 1;
    $article->language = '*';
    $article->created  = JFactory::getDate()->toSQL();
    $article->created_by = $data->createdBy;
    $article->created_by_alias = JFactory::getUser($data->createdBy)->name;
    $article->metadata  = '{"page_title":"","author":"","robots":""}';
    $article->introtext = '';
    $article->fulltext = '';
    $article->images = '{"image_intro":"","float_intro":"","image_intro_alt":"","image_intro_caption":"","image_fulltext":"","float_fulltext":"","image_fulltext_alt":"","image_fulltext_caption":""}';
    $article->urls = '{"urla":false,"urlatext":"","targeta":"","urlb":false,"urlbtext":"","targetb":"","urlc":false,"urlctext":"","targetc":""}';
    $article->attribs = '{"article_layout":"","show_title":"","link_titles":"","show_tags":"","show_intro":"","info_block_position":"","info_block_show_title":"","show_category":"","link_category":"","show_parent_category":"","link_parent_category":"","show_associations":"","show_author":"","link_author":"","show_create_date":"","show_modify_date":"","show_publish_date":"","show_item_navigation":"","show_icons":"","show_print_icon":"","show_email_icon":"","show_vote":"","show_hits":"","show_noauth":"","urls_position":"","alternative_readmore":"","article_page_title":"","show_publishing_options":"","show_article_options":"","show_urls_images_backend":"","show_urls_images_frontend":""}';
    $article->metadata = '{"robots":"","author":"","rights":"","xreference":""}';
    $article->metakey = '';
    $article->metadesc = '';

    if(!$article->store(true)){
        return new Response(json_encode(["Error" => true, "Message" => "Article not created", "Status" => 400]), 400);
    }

    return new Response(json_encode(["Error" => false, "Message" => "Article created success", "Status" => 200, "Data" => $article]), 200);
});

$Silex->get('/find/{alias}', function($alias) use ($dbo) {
    $article = $dbo->setQuery("SELECT * FROM #__content WHERE alias = '".$alias."'")->loadObject();

    if( is_null($article) ){
        return new Response(json_encode(["Error" => true, "Message" => "Article no found", "Status" => 404]), 400);
    }

    $firstInstance = new stdClass;
    $firstInstance->alias = $article->alias;
    $firstInstance->id = $article->id;
    $firstInstance->parrafos = [];
    $firstInstance->ft = false;

    if($article->fulltext == ''){
      $article->fulltext = json_encode($firstInstance);
    } else if( $article->fulltext != '' ){
		$fulltext = json_decode($article->fulltext);
		if(!$fulltext->id){
			$fulltext->id = $firstInstance->id;
		}

		$article->fulltext = json_encode($fulltext);
	}

    return new Response(json_encode(["Error" => false, "Message" => "Article found", "Status" => 200, "Data" => $article]), 200);
});

// solo los eventos     
$Silex->get('/allarticle', function() use ($dbo, $database) {
  $articles = $dbo->setQuery("SELECT *, TO_CHAR(created::DATE, 'Mon dd, yyyy') as created  FROM #__content WHERE catid = '9'")->loadObjectList();
  $return = array();
  foreach($articles as $i => $art){
	  
	  $o = $art;
	  $status = Status::getStatusEvent($o->id);
	  $o->fulltext = json_decode($o->fulltext);
	  $o->panels  = Matchs::getPanelFromEvent($o->id);
	  $o->statusEvent = ($status == false) ? ["eventid" => $o->id, "status" => 0] : $status;
	  $parametersAssigned = Matchs::parametersAssingedToJudgesOnEvent($o->id);
	  $parametersTotals = Matchs::parameterFromEvents($o->id);
	  $diference = array_diff($parametersTotals, $parametersAssigned);

	  $entrysql = <<<SQL
	  SELECT * FROM public.sasa_match_modules_participations WHERE event_id = '{$o->id}'
ORDER BY id ASC
SQL;

	  $entrys = $database->query($entrysql)->fetchAll(PDO::FETCH_ASSOC);

	  if( count($parametersTotals) > 0 ){
		  if( count($diference) > 0){
			  $status = false;
		  } else {
			  $status = true;
		  }
	  }

	  $o->statusA = $status;
	  $o->cantEntries = count($entrys);
	  array_push($return, $o);
  }
  return new Response(json_encode(["Error" => false, "Message" => "", "Status" => 200, "Data" => $return]), 200);
});

$Silex->post('/login', function() use ($dbo, $Joomla) {
    /**RECEPCION DATA**/
    $request_body = file_get_contents('php://input');
    $data = json_decode($request_body);
    $idU = $dbo->setQuery("SELECT id FROM #__users WHERE LOWER(email) LIKE '".strtolower($data->email)."'")->loadResult();

    if( is_null($idU) ){

        return new Response(json_encode(["Error" => true, "Message" => "User no found", "Status" => 404]), 404);
    }

    $login = loginUserById($idU);

    if(!$login){
        return new Response(json_encode(["Error" => true, "Message" => "Login failed", "Status" => 400]), 400);
    }

    $user = JFactory::getUser();

    return new Response(json_encode(["Error" => false, "Message" => "Login successful", "Status" => 200, "Data" => $user]), 200);


    /**RECEPCION DATA**/
});

$Silex->get('/testing', function() use ($dbo){
  $uri = Uri::root();
  echo str_replace('/api', '', $uri);
  return 1;
});

$Silex->post('/subirimagen', function() use ($dbo){
  $file = JFactory::getApplication()->input->files->get('file'); #CAPTURAMOS EL ARCHIVO
	$path = JRequest::getVar('dest', null); #CAPTURAMOS LA RUTA PARA DESTINO.
	$name = JRequest::getVar('filename', null); #CAPTURAMOS EL NOMBRE PARA EL ARCHIVO.
  $response = new stdClass;

	$pathCompleto = JPATH_ROOT . str_replace('/', DS, $path);

	if(!is_dir($pathCompleto)){
		$response->error = 1;
	} else {
			$extension = JFile::getExt($file['name']);
			$file['name'] = $name.".".$extension;
			$filename = JFile::makeSafe($file['name']);
			$src = $file['tmp_name'];
			$dest = $pathCompleto . DS . $filename;
			$size  = filesize($src) / 1000;
      $uri = Uri::root();

			switch ($extension) {
				case 'jpeg' :
				case 'jpg':
					$image = imagecreatefromjpeg($src);

					if ($size < 300) {
						if (imagejpeg($image, $dest, 100)) {
							$response->error = null;
							$response->url   = str_replace('/api', '', $uri) . "" . str_replace(JPATH_ROOT, '', $dest);
						} else {
							$response->error = 2;
						}
					} else if ($size > 300) {

						$dimen = getimagesize($src);

						$ratio = $dimen[0]/$dimen[1]; // width/height

						if( $ratio > 1) {
							$nuevo_ancho = 2000;
							$nuevo_alto = 2000/$ratio;
						} else {
							$nuevo_ancho = 2000*$ratio;
							$nuevo_alto = 2000;
						}

						$imagen_p = imagecreatetruecolor($nuevo_ancho, $nuevo_alto);

						$imagen = imagecreatefromjpeg($src);

						imagecopyresampled($imagen_p, $imagen, 0, 0, 0, 0, $nuevo_ancho, $nuevo_alto, $dimen[0], $dimen[1]);

						if (imagejpeg($imagen_p, $dest, 100)) {
							$response->error = null;
							$response->url   = str_replace('/api', '', $uri) . "" . str_replace(JPATH_ROOT, '', $dest);
						} else {
							$respose->error = 2;
						}
					}


					break;
				case 'png':

				$image = @imagecreatefrompng($src);

					if ($size < 400) {
						if (JFile::upload($src, $dest)) {
							$response->error = null;
							$response->url   = str_replace('/api', '', $uri) . "" . str_replace(JPATH_ROOT, '', $dest);
						} else {
							$response->error = 2;
						}
					}
					else if ($size > 400) {

						$dimen = getimagesize($src);

						$ratio = $dimen[0]/$dimen[1]; // width/height

						if( $ratio > 1) {
							$nuevo_ancho = 1920;
							$nuevo_alto = 1920/$ratio;
						} else {
							$nuevo_ancho = 1920*$ratio;
							$nuevo_alto = 1920;
						}

						$imagen_p = imagecreatetruecolor($nuevo_ancho, $nuevo_alto);
						$imagen = imagecreatefrompng($src);
						imagecopyresampled($imagen_p, $imagen, 0, 0, 0, 0, $nuevo_ancho, $nuevo_alto, $dimen[0], $dimen[1]);

						if (imagejpeg($imagen_p, $dest, 100)) {
							$response->error = null;
							$response->url   = str_replace('/api', '', $uri) . "" . str_replace(JPATH_ROOT, '', $dest);
						} else {
							$response->error = 2;
						}
					}

					break;


				default:
					# code...
					break;
			}
	}
  return new Response(json_encode($response), 200);
});

$Silex->post('/match/add/{type}', function($type) use ($dbo){
	
	$data = json_decode(json_encode($_POST));
	
	$query = '';
  	
	if($type=='param'){
    	$query = "INSERT INTO sasa_match_core_parameters(title, criteria, description, createdby) values ('".$data->title."', '".$data->criterias."', '".$data->description."', 1)";
  	}

  	if($type=='penalty'){	
    	$query = "INSERT INTO sasa_match_core_parameters_penalty(title, data, points, createdby) values ('".$data->title."', '".$data->data."', '".$data->points."', 1)";
  	}

  	if(!$dbo->setQuery($query)->execute()){
    	return new Response(json_encode(["Error" => true, "Message" => "Error on server"]), 400);
  	}
	
	return new Response(json_encode(["Error" => false, "Message" => "Data saved"]), 200);
});

$Silex->post('/match/addpenalty', function() use ($dbo){
	$data = $_POST['penalties'];
	$decode = json_decode($data);
	$error = 0;
	foreach( $decode as $p ){
		$query = "INSERT INTO sasa_match_core_parameters_penalty(title, data, points, createdby) values ('".$p->title."', '".json_encode($p->json)."', '".doubleval($p->points)."', 1)";
		if(!$dbo->setQuery($query)->execute()){
			$error++;
		}
	}

	if($error > 0){
		return new Response(json_encode(["Error" => true, "Message" => "Error on server"]), 400);
	}

	return new Response(json_encode(["Error" => false, "Message" => "Data saved"]), 200);
});


$Silex->get('/match/get/{type}', function($type) use ($dbo){
	$data = json_decode(json_encode($_POST));
  $query = '';
  if($type=='param'){
    $query = "SELECT * FROM sasa_match_core_parameters ORDER BY ID DESC";
  }

  if($type=='penalty'){
    $query = "SELECT * FROM sasa_match_core_parameters_penalty ORDER BY ID DESC";
  }

  $response = array();

  $result = $dbo->setQuery($query)->loadObjectList();

  foreach($result as $param)
  {
	  $new = clone $param;
	  if($new->criteria){
		$new->criteria = json_decode($new->criteria, true);
		$crits = $new->criteria;
		$strcri = [];
		foreach($crits as $cri){
			//var_dump($cri);
			array_push($strcri, $cri['title'] . ' (Val: ' . $cri['points'] . ')');
		}

		$new->strcri =implode(' | ', $strcri);
	  }
	  
	  if($new->data){
		$new->data = json_decode($new->data, true);
	  }
	  
	  array_push($response, $new);
  }

  return new Response(json_encode(["Error" => false, "Message" => "Data received", "Data" => $response]), 200);
});


?>
