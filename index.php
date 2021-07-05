<?php

require "./vendor/autoload.php";

header("Access-Control-Allow-Origin: *");
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

$Silex   = new Silex\Application();
$Joomla  = JFactory::getApplication('site');
$dbo     = JFactory::getDbo();
$session = JFactory::getSession();
$httpFac = HttpFactory::getHttp(null, ['curl', 'stream']);
$user    = JFactory::getUser();

$Silex["debug"] = true;

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
/**ROUTES */


/**INITIALITATION TO SILEX */
$Silex->run();
/**INITIALITATION TO SILEX */

?>
