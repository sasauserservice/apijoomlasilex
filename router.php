<?php

use Symfony\Component\HttpFoundation\Response;
  use Joomla\CMS\Uri\Uri;

global $Silex;
global $dbo;
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

    if($article->fulltext == ''){
      $article->fulltext = json_encode($firstInstance);
    }

    return new Response(json_encode(["Error" => false, "Message" => "Article found", "Status" => 200, "Data" => $article]), 200);
});


$Silex->get('/allarticle', function() use ($dbo) {
  $articles = $dbo->setQuery("SELECT * FROM #__content")->loadObjectList();
  return new Response(json_encode(["Error" => false, "Message" => "", "Status" => 200, "Data" => $articles]), 200);
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

?>
