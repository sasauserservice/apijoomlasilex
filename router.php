<?php

global $Silex;
global $dbo;

$Silex->get( '/', function() use ($dbo) {
    return 'Hello JoomlaSilex';
} );

$Silex->get( '/users', function() use ($dbo) {
    $result = $dbo->setQuery("SELECT * FROM #__users")->loadObjectList();
    return json_encode($result);
} );

?>