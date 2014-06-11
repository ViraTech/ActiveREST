<?php
/**
 * ActiveREST microframework bootstrap
 *
 * @package ActiveREST
 * @version 1.0
 * @copyright (c) 2014, ActiveGroup http://activegroup.pw/
 * @author Eugene V Chernyshev <ev@activegroup.pw>
 * @license http://www.gnu.org/licenses/lgpl.html‎ LGPL
 * @link https://github.com/ActiveGroup/ActiveREST GIT repo
 */
define('ActiveREST',true);
require_once(implode(DIRECTORY_SEPARATOR,array(dirname(__FILE__),'ar.php')));
ActiveRest::app(implode(DIRECTORY_SEPARATOR,array(dirname(__FILE__),'config.php')))->run();
?>