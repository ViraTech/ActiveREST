<?php
/**
 * ActiveREST microframework core
 *
 * @package ActiveREST
 * @version 1.0
 * @copyright (c) 2014, ActiveGroup http://activegroup.pw/
 * @author Eugene V Chernyshev <ev@activegroup.pw>
 * @license http://www.gnu.org/licenses/lgpl.html‎ LGPL
 * @link https://github.com/ActiveGroup/ActiveREST GIT repo
 */
defined('ActiveREST') or die('Not an entry point.');
/**
 * ActiveRest main class
 * @property ActiveRestRequest $request Http request
 */
class ActiveRest extends ActiveRestBase
{
	/**
	 * Singleton storage
	 * @var ActiveRest
	 */
	static private $_self = null;

	/**
	 * Request handler
	 * @var ActiveRestRequest
	 */
	private $_request;

	/**
	 * Application configuration options
	 * @var array
	 */
	private $_config = array();

	/**
	 * Classes autoloader
	 * @var ActiveRestLoader
	 */
	private $_autoload;

	/**
	 * Authentication manager
	 * @var mixed
	 */
	private $_auth;

	/**
	 * User manager
	 * @var mixed
	 */
	private $_user;

	/**
	 * Application user defined parameters
	 * @var ActiveRestParam
	 */
	public $params;

	/**
	 * Application singleton
	 * @param array $config optional application configuration options
	 * @return ActiveRest
	 */
	static function app($config = null)
	{
		if (self::$_self == null) {
			self::$_self = new ActiveRest(require_once($config));
		}

		return self::$_self;
	}

	/**
	 * ActiveRest constructor
	 * @param array $config application configuration options
	 */
	public function __construct($config)
	{
		$this->_config = $config;
		$this->init();
	}

	public function init()
	{
		$this->_autoload = new ActiveRestLoader($this->_config['import']);
		$this->_request = new ActiveRestRequest;
		if (isset($this->_config['auth'])) {
			$this->_auth = $this->createClass($this->_config['auth']);
		}
		if (isset($this->_config['user'])) {
			$this->_user = $this->createClass($this->_config['user']);
		}
		$this->params = new ActiveRestParam(isset($this->_config['params']) && is_array($this->_config['params']) ? $this->_config['params'] : array());
		ob_start();
	}

	/**
	 * Run application
	 */
	public function run()
	{
		$route = $this->request->getRoute();
		if ($this->auth) {
			$this->auth->authenticate();
		}
		$this->process($route);
	}

	/**
	 * Process request
	 */
	public function process($route)
	{
		$handled = false;
		foreach ($this->_config['routes'] as $config) {
			if (!strcasecmp($config['route'],$route)) {
				if (isset($config['type'])) {
					if (!$this->request->checkRequestType($config['type'])) {
						continue;
					}
				}
				$this->go($route,$config);
				$handled = true;
			}
		}

		if (!$handled && isset($this->_config['defaultRoute']) && isset($this->_config['routes']['defaultRoute'])) {
			$this->runRoute($this->_config['defaultRoute']);
			$handled = true;
		}

		if (!$handled) {
			$this->request->error(404,"Not found");
		}

		$this->end();
	}

	/**
	 * Run found route
	 * @param string $route route name
	 * @param array $config route configuration
	 */
	public function go($route,$config)
	{
		$handler = $config['handler'];
		if (is_array($handler)) {
			if (count($handler) == 2) {
				if (class_exists($handler[0])) {
					if (method_exists($handler[0],$handler[1])) {
						$class = new $handler[0];
						$method = $handler[1];
						if (method_exists($class,'beforeRequest')) {
							$class->beforeRequest();
						}
						$class->$method();
						if (method_exists($class,'afterRequest')) {
							$class->afterRequest();
						}
					}
					else {
						ActiveRest::app()->request->error(500,strtr("Non-existent handler method {method} in class {class} for route {route}",array(
							'{method}'=>$handler[1],
							'{class}'=>$handler[0],
							'{route}'=>$route,
						)));
					}
				}
				else {
					ActiveRest::app()->request->error(500,strtr("Non-existent handler class {class} for route {route}",array(
						'{class}'=>$handler[0],
						'{route}'=>$route,
					)));
				}
			}
			elseif (count($handler) == 1) {
				if (function_exists($handler)) {
					$func = $handler[0];
					$func();
				}
				else {
					ActiveRest::app()->request->error(500,strtr("Non-existent handler function {function} for route {route}",array(
						'{function}'=>$handler[0],
						'{route}'=>$route,
					)));
				}
			}
			else {
				ActiveRest::app()->request->error(500,strtr("Bad configuration for route {route} handler",array(
					'{route}'=>$route,
				)));
			}
		}
		elseif (is_callable($handler)) {
			$handler();
		}
		else {
			ActiveRest::app()->request->error(500,strtr("Bad configuration for route {route} handler",array(
				'{route}'=>$route,
			)));
		}
	}

	/**
	 * Http request handler
	 * @return ActiveRestRequest
	 */
	public function getRequest()
	{
		return $this->_request;
	}

	/**
	 * User manager if configured
	 * @return mixed
	 */
	public function getUser()
	{
		return $this->_user;
	}

	/**
	 * Authentication class if configured
	 * @return mixed
	 */
	public function getAuth()
	{
		return $this->_auth;
	}

	/**
	 * Stop application
	 */
	public function end()
	{
		ob_end_flush();
		exit();
	}

	public function __clone() {}
}
/**
 * Class autoloading and other handling
 */
class ActiveRestLoader extends ActiveRestBase
{
	/**
	 * Mapping for classes autoloading
	 * @var array
	 */
	private $_classMap = array();

	/**
	 * Classes import configuration
	 * @var array
	 */
	private $_import = array();

	/**
	 * Loader constructor
	 * @param array $import class imports list
	 */
	function __construct($import = array())
	{
		$this->init();
		$this->setImport($import);
		spl_autoload_register(array($this,'autoload'));
	}

	/**
	 * Classes automatic loading handler
	 * @param string $className
	 */
	public function autoload($className)
	{
		if (isset($this->_classMap[$className])) {
			include_once($this->_classMap[$className]);
		}
		elseif ($this->import !== array()) {
			foreach ($this->import as $importPath) {
				$this->loadClass($className,$importPath);
			}
		}
	}

	/**
	 * Find and load class by it's name according to PSR-0 standard
	 * @param string $className name of class to be loaded
	 * @param string $importPath import path to look in
	 */
	private function loadClass($className,$importPath)
	{
		if (stripos($importPath,'/') !== false) {
			$delimiter = '/';
		}
		else {
			$delimiter = '.';
		}
		$classPath = explode($delimiter,$importPath);
		$lastPart = array_pop($classPath);
		if ($lastPart == '*' || $lastPart == $className) {
			array_push($classPath,implode('.',array($className,'php')));
			if ($classPath[0][0] != '/') {
				array_unshift($classPath,dirname(__FILE__));
			}
			$classPath = implode(DIRECTORY_SEPARATOR,$classPath);
			if (file_exists($classPath)) {
				include_once($classPath);
				$this->addClassMap($className,$classPath);
			}
		}
	}

	/**
	 * Class initiation
	 */
	private function init()
	{
		$this->_classMap = array(
			'ActiveRest'=>'ar.php',
			'ActiveRestBase'=>'ar.php',
			'ActiveRestLoader'=>'ar.php',
			'ActiveRestRequest'=>'ar.php',
			'ActiveRestComponent'=>'ar.php',
			'ActiveRestHandler'=>'ar.php',
			'ActiveRestParam'=>'ar.php',
			'ActiveRestAuth'=>'ar.php',
			'ActiveRestAuthDigest'=>'ar.php',
			'ActiveRestUser'=>'ar.php',
			'ActiveRestUserSimple'=>'ar.php',
		);
	}

	/**
	 * Add import path to imports list
	 * @property array $import imports
	 */
	public function setImport($import = array())
	{
		$this->_import = $import;
	}

	/**
	 * Return current import paths as array
	 * @return array
	 */
	public function getImport()
	{
		return $this->_import;
	}

	/**
	 * Returns class map list
	 * @return array
	 */
	public function getClassMap()
	{
		return $this->_classMap;
	}

	/**
	 * Add a new class mapping. Do nothing if entry exists and $override set to false
	 * @param string $class class name
	 * @param string $filename file name
	 * @param type $override
	 */
	public function addClassMap($class,$filename,$override = false)
	{
		if ($override || !isset($this->_classMap[$class])) {
			$this->_classMap[$class] = $filename;
		}
	}
}
/**
 * Request handler class
 */
class ActiveRestRequest extends ActiveRestBase
{
	/**
	 * Request types
	 */
	const REQUEST_HEAD = 'HEAD';
	const REQUEST_GET = 'GET';
	const REQUEST_POST = 'POST';
	const REQUEST_PUT = 'PUT';
	const REQUEST_DELETE = 'DELETE';

	/**
	 * Rest params
	 * @var array
	 */
	private $_rest = array();
	/**
	 * Return current route
	 * @return string
	 */
	public function getRoute()
	{
		$route = parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);
		return $route == '/' ? '/' : trim($route,' /');
	}

	/**
	 * Return request parameter if available
	 * @param string $variable name of parameter
	 * @param mixed $defaultValue default value
	 * @return mixed parameter or default value
	 */
	public function getParam($variable,$defaultValue = null)
	{
		return isset($_REQUEST[$variable]) ? $_REQUEST[$variable] : $defaultValue;
	}

	/**
	 * Decode and return all JSON data passed to handler
	 * @return array
	 */
	public function getRest()
	{
		if ($this->_rest == array()) {
			$data = $this->getRequestBody();
			$this->_rest = CJSON::decode($data);
		}

		return $this->_rest;
	}

	/**
	 * Return rest parameter if available
	 * @param string $variable name of parameter
	 * @param mixed $defaultValue default value
	 * @return mixed
	 */
	public function getRestParam($variable,$defaultValue = null)
	{
		return isset($this->rest[$variable]) ? $this->rest[$variable] : $defaultValue;
	}

	/**
	 * Return all rest parameters
	 * @return array
	 */
	public function getRestParams()
	{
		return $this->rest;
	}

	/**
	 * Return server parameter if available
	 * @param string $variable parameter name
	 * @param mixed $defaultValue default value
	 * @return mixed
	 */
	public function getServerParam($variable,$defaultValue = null)
	{
		return isset($_SERVER[$variable]) ? $_SERVER[$variable] : $defaultValue;
	}

	/**
	 * Return request body
	 * @return string
	 */
	public function getRequestBody()
	{
		$data = '';
		$blob = fopen('php://input','r');
		while (!feof($blob)) {
			$data .= fread($blob,1024);
		}
		return $data;
	}

	/**
	 * Check if current request is POST
	 * @return boolean
	 */
	public function isPostRequest()
	{
		return $this->checkRequestType(self::REQUEST_POST);
	}

	/**
	 * Check if current request is PUT
	 * @return boolean
	 */
	public function isPutRequest()
	{
		return $this->checkRequestType(self::REQUEST_PUT);
	}

	/**
	 * Check if current request is DELETE
	 * @return boolean
	 */
	public function isDeleteRequest()
	{
		return $this->checkRequestType(self::REQUEST_DELETE);
	}

	/**
	 * Check if current request is HEAD
	 * @return boolean
	 */
	public function isHeadRequest()
	{
		return $this->checkRequestType(self::REQUEST_HEAD);
	}

	/**
	 * Check for request type is corresponds with provided value
	 * @param mixed $type request type as string or array
	 * @return boolean
	 */
	public function checkRequestType($type)
	{
		$type = array_map('trim',array_map('strtoupper',is_array($type) ? $type : explode(',',$type)));
		return in_array($this->getRequestType(),$type);
	}

	/**
	 * Return request method or GET if not present
	 * @return string
	 */
	public function getRequestType()
	{
		return isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : self::REQUEST_GET;
	}

	/**
	 * Check for AJAX request
	 * @return boolean
	 */
	public function isAjaxRequest()
	{
		return !empty($_SERVER['HTTP_X_REQUESTED_WITH']);
	}

	/**
	 * Return server protocol with version (HTTP/1.1)
	 * @return string
	 */
	public function getHttpProtocol()
	{
		return isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0';
	}

	/**
	 * Add response header
	 * @param string $message header contents
	 * @param integer $code http status code (optional)
	 */
	public function addHeader($message,$statusCode = null)
	{
		if ($statusCode) {
			$params[] = implode(' ',array($this->getHttpProtocol(),$statusCode,$message));
			$params[] = true;
			$params[] = $statusCode;
		}
		else {
			$params[] = $message;
		}
		call_user_func_array('header',$params);
	}

	/**
	 * Throw an error and optionally exit
	 * @param integer $errorCode error code
	 * @param string $message error message
	 * @param boolean $stopApplication stop application and exit
	 */
	public function error($errorCode,$message,$stopApplication = true)
	{
		$this->addHeader($message,$errorCode);
		echo CJSON::encode(array(
			'errorCode'=>$errorCode,
			'errorMessage'=>$message,
		));
		if ($stopApplication) {
			ActiveRest::app()->end();
		}
	}
}
/**
 * Default handler class
 */
class ActiveRestHandler extends ActiveRestBase
{
	public function beforeRequest() {}
	public function afterRequest() {}
}
/**
 * ActiveREST base class
 */
class ActiveRestBase
{
	function __construct() {}

	function __get($name)
	{
		if (property_exists($this,$name)) {
			return $this->$name;
		}

		if (method_exists($this,'get' . ucfirst($name))) {
			return $this->{'get' . ucfirst($name)}();
		}

		return null;
	}

	function __set($name,$value)
	{
		if (method_exists($this,'get' . ucfirst($name))) {
			$this->{'set' . ucfirst($name)}($value);
		}
		else {
			$this->$name = $value;
		}
	}

	function __clone() {}

	function createClass($params = array())
	{
		if (isset($params['class'])) {
			$className = $params['class'];
			unset($params['class']);

			return class_exists($className) ? new $className($params) : null;
		}
	}
}
/**
 * Configuration based component class
 */
class ActiveRestComponent extends ActiveRestBase
{
	function __construct($params = array())
	{
		if (is_array($params) && $params != array()) {
			foreach ($params as $variable => $value) {
				if (property_exists($this,$variable)) {
					$this->$variable = $value;
				}
			}
		}
	}
}
/**
 * User defined parameters storage
 */
class ActiveRestParam extends ActiveRestComponent implements Iterator,ArrayAccess
{
	protected $_p = 0;
	protected $_i = array();

	function __construct($params = array())
	{
		$this->_i = is_array($params) ? $params : array($params);
	}

	public function rewind()
	{
		$this->_p = 0;
	}

	public function current()
	{
		return $this->_i[$this->_p];
	}

	public function key()
	{
		return $this->_p;
	}

	public function next()
	{
		$this->_p++;
	}

	public function valid()
	{
		return isset($this->_i[$this->_p]);
	}

	public function offsetExists($p)
	{
		return isset($this->_i[$p]);
	}

	public function offsetGet($p)
	{
		return $this->_i[$p];
	}

	public function offsetSet($p,$v)
	{
		if (!$p) {
			$this->_i[] = $v;
		}
		else {
			$this->_i[$p] = $v;
		}
	}

	public function offsetUnset($p)
	{
		unset($this->_i[$p]);
	}
}
/**
 * User Manager Base class
 */
abstract class ActiveRestUser extends ActiveRestComponent
{
	/**
	 * Hash function for password compare
	 * @var string
	 */
	protected $hash = 'sha1';

	abstract function login($username,$password);
}
/**
 * Simple array user storage
 */
class ActiveRestUserSimple extends ActiveRestUser
{
	protected $users = array();

	public function login($username,$password)
	{
		return $this->exists($username) && strcasecmp($this->cypher($password),$this->password($username)) === 0;
	}

	public function exists($username)
	{
		return isset($this->users[$username]);
	}

	public function cypher($password)
	{
		return $this->hash && function_exists($this->hash) ? call_user_func($this->hash,$password) : $password;
	}

	public function password($username)
	{
		return $this->exists($username) ? $this->users[$username]['password'] : false;
	}
}
/**
 * Authentication Base class
 */
abstract class ActiveRestAuth extends ActiveRestComponent
{
	abstract function authenticate();
}
/**
 * Digest Authentication class
 */
class ActiveRestAuthDigest extends ActiveRestAuth
{
	const DIGEST_SERVER_VAR = 'PHP_AUTH_DIGEST';

	protected $required = true;
	protected $realm = 'ActiveREST';

	public function authenticate()
	{
		if ($this->required) {
			$digest = ActiveRest::app()->request->getServerParam(self::DIGEST_SERVER_VAR);
			if (!$digest) {
				ActiveRest::app()->request->addHeader('Unauthorized',401);
				ActiveRest::app()->request->addHeader($this->getDigestHeader());
				ActiveRest::app()->end();
			}
			$data = $this->parseDigestHeader($digest);
			if (!(ActiveRest::app()->user->exists($data['username']) && $this->validate($data))) {
				ActiveRest::app()->request->error(403,'Access denied');
			}
		}

		return true;
	}

	/**
	 * Format digest header
	 * @return string
	 */
	private function getDigestHeader()
	{
		$params = array(
			'Digest realm="' . $this->realm . '"',
			'qop="auth"',
			'nonce="' . uniqid() . '"',
			'opaque="' . md5($this->realm) . '"',
		);
		return 'WWW-Authenticate: ' . implode(',',$params);
	}

	/**
	 * Parse digest header and return parameters as array
	 * @return array
	 */
	private function parseDigestHeader($digest)
	{
		$parts = array(
			'nonce'=>1,
			'nc'=>1,
			'cnonce'=>1,
			'qop'=>1,
			'username'=>1,
			'uri'=>1,
			'response'=>1,
		);
		$data = array();
		$keys = implode('|', array_keys($parts));
		preg_match_all('@(' . $keys . ')=(?:([\'"])([^\2]+?)\2|([^\s,]+))@',$digest,$matches,PREG_SET_ORDER);
		foreach ($matches as $m) {
			$data[$m[1]] = $m[3] ? $m[3] : $m[4];
			unset($parts[$m[1]]);
		}

		return $parts ? false : $data;
	}

	/**
	 * Validate digest data
	 * @param array $data data
	 * @return boolean
	 */
	private function validate($data)
	{
		$value = md5(implode(':',array(
			md5(implode(':',array(
				$data['username'],
				$this->realm,
				ActiveRest::app()->user->password($data['username']),
			))),
			$data['nonce'],
			$data['nc'],
			$data['cnonce'],
			$data['qop'],
			md5(implode(':',array(
				ActiveRest::app()->request->getServerParam('REQUEST_METHOD'),
				$data['uri'],
			))),
		)));

		return !strcasecmp($value,$data['response']);
	}
}
?>