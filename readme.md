## ActiveREST
=============

The ActiveREST microframework is a simple but powerful RESTful framework written in pure PHP to work with Redis databases.

### General Purpose

The ActiveREST has purposed for use as submodule for a rapid databases management through the RESTful interface.
It's very simple in configuration and usage and can be extended to use for everything you need.

### Requirements, Installation & Configuraion

#### Requirements

* PHP 5.3 or later
* *(optionally)* Redis or other database engine
* Apache/PHP-FPM/other web server

#### Installation

Download ZIP and extract it contents (or clone repository) to website root folder.

In case of use Apache as webserver create additional `.htaccess` file manually:

```
RewriteEngine on
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . index.php
```

#### Configuration

To configure ActiveREST application you need to edit `config.php` file. Configuration is provided as PHP array.

Config Section | Description
-------------- | -----------
auth           | Authentication options.
               | Set `required` parameter to `true` or `false` to enable or disable authentication.
               | Set `class` to class name responsible for authentication mechanism (predefined or your own).
               | Set `realm` to your application name or whatever you want.
user           | `class` parameter have sense only when `required` in `auth` section is set to `true`
               | and must set to class name responsible for user's authentication.
               | With `hash` parameter you can set your own hash function used in passwords comparison. Default function is `sha1`.
               | `users` parameter provide the list of users as PHP array.
import         | This section describes file import configuration as array of values. Each value must provide path to class file
               | relative to web root folder. For example, `components.*` is correspond to `/web/server/root/components/*.php` and
               | `extensions.SomeClass` is correspond to `/web/server/root/extensions/SomeClass.php`.
routes         | The most important section that configures route handling.
               | For more details see **Route Handlers** section of this manual.
params         | Everything you must need to your application working properly. Provides as PHP array.
               | See more in **Extended Application Parameters** section.

### Route Handlers

The route handlers configured in `routes` section of `config.php` is a list of options set for each route/method pairs.

Example of route handlers configuration:

```PHP
array(
	'route'=>'test',
	'type'=>'get',
	'handler'=>array('TestHandler','read'),
)
```

There is a `route` option which correspond to http route. Actually `test` in this means `http://your.web.server/test/`.

Option `type` describes the type of request. The request may be `head`, `get`, `post`, `put` and `delete`.
Note that `head` and `delete` requests may not have request body. The request type can hold multiple values. In this case
it configuration might look like `'type'=>'head,delete'`.

`handler` option provides request handler mechanism. It can be PHP function, or array describes the class name and method, or
closure.

Classes which methods is used as request handlers must be extended from `ActiveRestHandler` class. Surely you can create your
own base handler class and populate it with common used functions like I did with `BaseHandler` class.

### Extended Application Parameters

The application parameters describes in `params` section of `config.php`.

It is looks like PHP array with some keys and values (may be arrays too).

For example:
```
'params'=>array(
	'redis'=>array(
		'host'=>null,
		'port'=>null,
		'sock'=>'/var/run/redis/redis.sock',
		'timeout'=>5,
		'database'=>0,
	),
)
```

Everything in `params` key can be accessed from any part of ActiveREST application as array `ActiveRest::app()->params`.
The `host` variable of `redis` section can used like `ActiveRest::app()->params['redis']['host']`.

### Classes of ActiveREST microframework

##### ActiveRestBase

Base application class.

##### ActiveRest

The application singleton. Has `app()` method to access application functions.

##### ActiveRestLoader

Autoloader handler class. Responsible for PHP classes autoloading under PSR-0 standard.

##### ActiveRestRequest

HTTP request class. Hold related to HTTP request functions.

##### ActiveRestHandler

Base request handler class.

##### ActiveRestComponent

Base class for all ActiveREST components.

##### ActiveRestParam

Component for extended application parameters (see **Extended Application Parameters** section).

##### ActiveRestUser

User manager base class.

##### ActiveRestUserSimple

Simple user manager. Stores user name/password pairs.

##### ActiveRestAuth

Authentication base class.

##### ActiveRestAuthDigest

Implements HTTP-DIGEST authentication mechanism.
