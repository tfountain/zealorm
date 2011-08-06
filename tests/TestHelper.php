<?php
set_include_path(
    '../library'.PATH_SEPARATOR.
    '/home/tim/workspace/fabric/library'.PATH_SEPARATOR.
    '/usr/share/php'
);

// setup the autoloader
require_once 'Zend/Loader/Autoloader.php';
$autoloader = Zend_Loader_Autoloader::getInstance()
	->registerNamespace('Zeal');

// setup db connection
/*$db = Zend_Db::factory('Pdo_Sqlite', array(
	'dbname'   => '../data/db.sqlite'
));
Zend_Registry::set('db', $db);
*/
