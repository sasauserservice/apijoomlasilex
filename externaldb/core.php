<?php
use Medoo\Medoo;

/**CONEXION CON DB**/
$database = new Medoo([
	// [required]
	'type' => 'pgsql',
	'host' => '74.208.49.26',
	'database' => 'extendsystem',
	'username' => 'postgres',
	'password' => '2109',
 
	// [optional]
	'charset' => 'utf8mb4',
	'collation' => 'utf8mb4_general_ci',
	'port' => 5432,

    'prefix' => 'sasa_'
]);

/**CONEXION IMPLEMENTACION DE RUTAS**/
global $Silex;
global $dbo;

$Silex->mount('/site', function($site) use ($database, $dbo) {
    require_once('./externaldb/Site/index.php'); #All settings to users corresponding
});

$Silex->mount('/auth', function($auth) use ($database, $dbo) {
    require_once('./externaldb/Auth/index.php'); #All settings to users corresponding
});

$Silex->mount('/extdb', function($extdb) use ($database, $dbo){
    $extdb->mount('/profiles', function($profiles) use($database, $dbo) {
        require_once('./externaldb/Modules/Profiles.php');
    });
    $extdb->mount('/community', function($community) use($database, $dbo) {
        require_once('./externaldb/Modules/Users.php');
    });
    $extdb->mount('/competitor', function($competitor) use($database, $dbo) {
        require_once('./externaldb/Modules/Competitor.php');
    });
    $extdb->mount('/juzging', function($juzging) use($database, $dbo) {
        require_once('./externaldb/Modules/Juzging.php');
    });
    $extdb->mount('/ranking', function($ranking) use($database, $dbo) {
        require_once('./externaldb/Modules/Ranking.php');
    });
    require_once('./externaldb/Modules/Participations.php');
});



?>