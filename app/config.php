<?php
date_default_timezone_set('Asia/Jakarta');

$path = '../';

if ( ! file_exists( $path . 'vendor/autoload.php') ) {
	$path = '';
}

require_once( $path . "vendor/autoload.php");

// load ini file
$ini = parse_ini_file( $path . '.env', true);

define('APP_SESSION'        , $ini['app'    ]['SESSION'     ]);
define('APP_NAME'           , $ini['app'    ]['NAME'        ]);
define('APP_DESCRIPTION'    , $ini['app'    ]['DESCRIPTION' ]);

define('OLLAMA_GENERATE'    , $ini['ollama' ]['GENERATE'    ]);
define('OLLAMA_CHAT'        , $ini['ollama' ]['CHAT'        ]);
define('OLLAMA_MODEL'       , $ini['ollama' ]['MODEL'       ]);
define('OLLAMA_TEMPERATURE' , $ini['ollama' ]['TEMPERATURE' ]);
define('CHAT_GREETING'      , $ini['ollama' ]['GREETING'    ]);
define('CHAT_SYSTEM'        , $ini['ollama' ]['SYSTEM'      ]);

if ($_SERVER['SERVER_NAME'] == 'localhost') {
	define('DB_HOST'        , $ini['localhost']['DB_HOST' ]);
	define('DB_USER'        , $ini['localhost']['DB_USER' ]);
	define('DB_PASS'        , $ini['localhost']['DB_PASS' ]);
	define('DB_NAME'        , $ini['localhost']['DB_NAME' ]);
} else {
	define('DB_HOST'        , $ini['production']['DB_HOST' ]);
	define('DB_USER'        , $ini['production']['DB_USER' ]);
	define('DB_PASS'        , $ini['production']['DB_PASS' ]);
	define('DB_NAME'        , $ini['production']['DB_NAME' ]);
}
