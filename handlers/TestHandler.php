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
 * ActiveRest request handler class for example
 */
class TestHandler extends BaseHandler
{
	/**
	 * Get and return test data
	 * Read "id" in request params, return value if found, 404 error otherwise
	 */
	public function read()
	{
		$id = ActiveRest::app()->request->getParam('id');
		$data = $this->redis->get('test:' . intval($id));

		$returnCode = empty($data) ? 404 : 302;
		$message = empty($data) ? 'Not Found' : 'Found';

		ActiveRest::app()->request->addHeader($message,$returnCode);
		echo CJSON::encode(array(
			'errorCode'=>$returnCode,
			'message'=>$message,
			'data'=>$data,
		));
	}

	/**
	 * Create test variable
	 */
	public function create()
	{
		$this->process('create');
	}

	/**
	 * Update existing test variable(s)
	 */
	public function update()
	{
		$this->process('update');
	}

	/**
	 * Process create or update request
	 * @param string $action action name - create or update
	 * @return array JSON result as array
	 */
	private function process($action)
	{
		$data = ActiveRest::app()->request->getRestParams();

		list($message,$returnCode) = $this->add($data,$action);

		ActiveRest::app()->request->addHeader($message,$returnCode);
		echo CJSON::encode(array(
			'errorCode'=>$returnCode,
			'message'=>$message,
		));
	}

	/**
	 * Create or update test variable
	 * @param array $data rest params
	 * @param string $action create or update
	 * @return type
	 */
	private function add($data,$action)
	{
		if ($this->validate($data)) {
			$this->redis->set('test:' . $data['id'],$data['value']);
			$returnCode = $action == 'create' ? 201 : 200;
			$message = $action == 'create' ? 'Created' : 'Updated';
		}
		else {
			$returnCode = 412;
			$message = array();
			foreach ($this->getErrors() as $error) {
				$message[] = $error['message'];
			}
			$message = implode(', ',$message);
		}

		return array($message,$returnCode);
	}

	/**
	 * Delete test variable
	 */
	public function delete()
	{
		$id = ActiveRest::app()->request->getParam('id');
		$data = array();

		if ($this->redis->exists('test:' . $id)) {
			$data = array(
				'id'=>$id,
				'result'=>$this->redis->delete('test:' . $id),
			);
		}

		$returnCode = empty($data) ? 404 : 410;
		$message = empty($data) ? 'Not Found' : 'Removed';

		ActiveRest::app()->request->addHeader($message,$returnCode);
		echo CJSON::encode(array(
			'errorCode'=>$returnCode,
			'message'=>$message,
			'data'=>$data,
		));
	}

	/**
	 * Validate test data
	 * @param array $data test data
	 * @return boolean true if validated
	 */
	private function validate($data)
	{
		if (!isset($data['id']) || $data['id'] != intval($data['id'])) {
			$this->addError('id','Request parameters must have (int) id field.');
			return false;
		}

		if (empty($data['value'])) {
			$this->addError('value',"Test must have non-empty (string) value field.");
			return false;
		}

		return true;
	}
}
?>