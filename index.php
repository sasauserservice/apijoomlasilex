<?php

require "./vendor/autoload.php";

#header("Access-Control-Allow-Origin: *");
header("Accept: application/json");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: OPTIONS,GET,POST,PUT,DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

$pathProject = dirname(dirname(__FILE__));

define( '_JEXEC', 1 );
define( 'DS', DIRECTORY_SEPARATOR );
define( 'JPATH_BASE', $pathProject );

/**AUTOLOAD */
require_once(JPATH_BASE . DS . 'api' . DS . 'autoload.php');
/**AUTOLOAD */

require_once( JPATH_BASE . DS . 'includes' . DS . 'defines.php' );
require_once( JPATH_BASE . DS . 'includes' . DS . 'framework.php' );
require_once( JPATH_BASE . DS . 'libraries' . DS . 'joomla' . DS . 'database' . DS . 'factory.php' );

use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Http\HttpFactory;
use Medoo\Medoo;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

$Silex   = new Silex\Application();
$Joomla  = JFactory::getApplication('site');
$dbo     = JFactory::getDbo();
$session = JFactory::getSession();
$httpFac = HttpFactory::getHttp(null, ['curl', 'stream']);
$user    = JFactory::getUser();

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

$Silex["debug"] = true;

$Silex->register(new JDesrosiers\Silex\Provider\CorsServiceProvider(), [
    "cors.allowOrigin" => "http://jesusuzcategui.me:9001",
]);

$Silex["cors-enabled"]($Silex);

function updateSessionTable($username=null, $userid=null){
    global $session;

    $table = JTable::getInstance('session');
    $table->load($session->getId());
    $table->guest = 0;
    $table->username = $username;
    $table->userid   = $userid;
    $table->update();
}

function loginUserById($id){
    global $session;
    $user    = JFactory::getUser($id);

    if( !empty($user->id) ){
        $session->set('user', $user);
        updateSessionTable($user->username, $user->id);
        $user->setLastVisit();
        return true;
    }

    return false;
}

function generate_string($strength = 16) {
    $input = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $input_length = strlen($input);
    $random_string = '';
    for($i = 0; $i < $strength; $i++) {
        $random_character = $input[mt_rand(0, $input_length - 1)];
        $random_string .= $random_character;
    }

    return $random_string;
}


use Model\Content;

$Content = new Content();

/**ROUTES */
require_once('./router.php');
require_once('./Modules/Content.php');
require_once('./Modules/Match.php');
require_once('./Modules/MatchPanels.php');
/**ROUTES */

/*EXTERNAL DB CONEXIONS*/
require_once('./externaldb/core.php');
/*EXTERNAL DB CONEXIONS*/

#Enable cors

$Silex->after(function (Request $request, Response $response) {
    $response->headers->set('Access-Control-Allow-Origin', $request->headers->get('origin'));
}, 1);

/**INITIALITATION TO SILEX */
$Silex->run();
/**INITIALITATION TO SILEX */

?>
