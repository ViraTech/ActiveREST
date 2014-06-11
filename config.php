<?php
/**
 * ActiveREST microframework configuration
 *
 * @package ActiveREST
 * @version 1.0
 * @copyright (c) 2014, ActiveGroup http://activegroup.pw/
 * @author Eugene V Chernyshev <ev@activegroup.pw>
 * @license http://www.gnu.org/licenses/lgpl.html‎ LGPL
 * @link https://github.com/ActiveGroup/ActiveREST GIT repo
 */
defined('ActiveREST') or die('Not an entry point.');
return array(
	'auth'=>array(
		'class'=>'ActiveRestAuthDigest',
		'required'=>true,
		'realm'=>'ActiveREST Server',
	),
	'user'=>array(
		'class'=>'ActiveRestUserSimple',
		'hash'=>null,
		'users'=>array(
			'test'=>array(
				'password'=>'123',
			),
		),
	),
	'import'=>array(
		'components.*',
		'handlers.*',
	),
	'routes'=>array(
		array(
			'route'=>'/',
			'handler'=>function()
			{
				echo CJSON::encode(array(
					'errorCode'=>200,
					'message'=>'Congrats! You are in.',
				));
			},
		),
		array(
			'route'=>'test',
			'type'=>'get',
			'handler'=>array('TestHandler','read'),
		),
		array(
			'route'=>'test',
			'type'=>'put',
			'handler'=>array('TestHandler','create'),
		),
		array(
			'route'=>'test',
			'type'=>'post',
			'handler'=>array('TestHandler','update'),
		),
		array(
			'route'=>'test',
			'type'=>'delete',
			'handler'=>array('TestHandler','delete'),
		),
	),
	'params'=>array(
		'redis'=>array(
			'host'=>null,
			'port'=>null,
			'sock'=>'/var/run/redis/redis.sock',
			'timeout'=>5,
			'database'=>0,
		),
	),
);
?>