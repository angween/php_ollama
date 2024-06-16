<?php
defined('APP_NAME') or exit('No direct script access allowed');

if (session_status() == PHP_SESSION_NONE) {
	session_start();

	if (isset($_SESSION[APP_SESSION])) {
		if (time() - $_SESSION[APP_SESSION]['created'] > 3600) {
			unset($_SESSION[APP_SESSION]);

			session_destroy();

			header('Location: index.php');
		}
	} else {
		$_SESSION[APP_SESSION]['created'] = time();
	}
}
