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
 * ActiveRest base request handler class
 */
class BaseHandler extends ActiveRestHandler
{
	/**
	 * Redis handle
	 * @var Redis
	 */
	protected $_redis = null;

	/**
	 * Validation errors
	 * @var array
	 */
	protected $_errors = array();

	/**
	 * Get (and create if needed) redis connection
	 * @return Redis
	 */
	public function getRedis()
	{
		if ($this->_redis === null) {
			$this->_redis = new Redis();
			$connection = array();
			if (empty(ActiveRest::app()->params['redis']['host']) && empty(ActiveRest::app()->params['redis']['sock'])) {
				ActiveRest::app()->request->error(500,'Bad Redis connection configuration');
			}
			elseif (!empty(ActiveRest::app()->params['redis']['sock'])) {
				$connection[] = ActiveRest::app()->params['redis']['sock'];
			}
			else {
				$connection[] = ActiveRest::app()->params['redis']['host'];
				$connection[] = ActiveRest::app()->params['redis']['port'];
				if (!empty(ActiveRest::app()->params['redis']['timeout'])) {
					$connection[] = ActiveRest::app()->params['redis']['timeout'];
				}
			}
			call_user_func_array(array($this->_redis,'connect'),$connection);
		}

		return $this->_redis;
	}

	/**
	 * Adds an error to error's list
	 * @param string $field
	 * @param string $message
	 */
	public function addError($field,$message)
	{
		$this->_errors[$field] = $message;
	}

	/**
	 * Return all errors list as array or null if no errors is present
	 * @return array
	 */
	public function getErrors()
	{
		$errors = array();

		foreach ($this->_errors as $field => $message) {
			$errors[] = array(
				'field'=>$field,
				'message'=>$message,
			);
		}

		return $errors;
	}

	/**
	 * Run after request. Closes redis connection.
	 */
	public function afterRequest()
	{
		$this->redis->close();
	}

}
?>
