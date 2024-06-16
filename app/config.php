<?php
date_default_timezone_set('Asia/Jakarta');

// load ini file
$ini = parse_ini_file('../.env', true);

define('APP_SESSION'     , $ini['app']['SESSION'        ]);
define('APP_NAME'        , $ini['app']['NAME'           ]);
define('APP_DESCRIPTION' , $ini['app']['DESCRIPTION'    ]);

if ($_SERVER['SERVER_NAME'] == 'localhost') {
	define('DB_HOST', $ini['localhost']['DB_HOST']);
	define('DB_USER', $ini['localhost']['DB_USER']);
	define('DB_PASS', $ini['localhost']['DB_PASS']);
	define('DB_NAME', $ini['localhost']['DB_NAME']);
} else {
	define('DB_HOST', $ini['production']['DB_HOST']);
	define('DB_USER', $ini['production']['DB_USER']);
	define('DB_PASS', $ini['production']['DB_PASS']);
	define('DB_NAME', $ini['production']['DB_NAME']);
}
