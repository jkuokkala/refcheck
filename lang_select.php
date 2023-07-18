<?php

define('SUPPORTED_LANGUAGES', ['en', 'fi']);

function get_user_accept_language($fallback='en') {
	foreach (preg_split('/[;,]/', $_SERVER['HTTP_ACCEPT_LANGUAGE']) as $sub) {
		if (substr($sub, 0, 2) == 'q=')
			continue;
		if (strpos($sub, '-') !== false)
			$sub = explode('-', $sub)[0];
		if (in_array(strtolower($sub), SUPPORTED_LANGUAGES))
			return $sub;
	}
	return $fallback;
}

function select_lang() {
	$lang = "";
	if (isset($_GET['lang'])) {
		$lang = $_GET['lang'];
	} elseif (isset($_POST["lang"])) {
		$lang = $_POST["lang"];
	} elseif (isset($_COOKIE["lang"])) {
		$lang = $_COOKIE["lang"];
	} else {
		$lang = get_user_accept_language();
	}
	setcookie('lang', $lang, time() + (86400 * 365 * 5), "/"); // expiration time 5y
	return $lang;
}
?>
