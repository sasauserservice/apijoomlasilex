<?php

require "./vendor/autoload.php";

$pathProject = dirname(dirname(__FILE__));

define( '_JEXEC', 1 );
define( 'DS', DIRECTORY_SEPARATOR );
define( 'JPATH_BASE', $pathProject );

/**AUTOLOAD */
require_once(JPATH_BASE . DS . 'backend' . DS . 'autoload.php');
/**AUTOLOAD */

require_once( JPATH_BASE . DS . 'includes' . DS . 'defines.php' );
require_once( JPATH_BASE . DS . 'includes' . DS . 'framework.php' );
require_once( JPATH_BASE . DS . 'libraries' . DS . 'joomla' . DS . 'database' . DS . 'factory.php' );

$Silex  = new Silex\Application();
$Joomla = JFactory::getApplication('site');
$dbo    = JFactory::getDbo();

use Model\Content;

$Content = new Content();

/**ROUTES */
require_once('./router.php');
/**ROUTES */

/**INITIALITATION TO SILEX */
$Silex->run();
/**INITIALITATION TO SILEX */

?>
