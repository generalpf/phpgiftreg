<?php

define('SMARTY_DIR', dirname(__FILE__) . "/smarty-3.1.48/libs/");
require_once(SMARTY_DIR . "Smarty.class.php");
require_once(dirname(__FILE__) . "/config.php");

class MySmarty extends Smarty {
	public function __construct() {
		parent::__construct();

		date_default_timezone_set("GMT+0");
	}

	public function dbh() {
		$opt = $this->opt();
		return new PDO(
			$opt["pdo_connection_string"],
			$opt["pdo_username"],
			$opt["pdo_password"]);
	}

	public function opt() {
		static $opt;
		if (!isset($opt)) {
			$opt = getGlobalOptions();
		}
		return $opt;
	}

	public function display($template = NULL, $cache_id = NULL, $compile_id = NULL, $parent = NULL) {
		parent::assign('isadmin', isset($_SESSION['admin']) ? $_SESSION['admin'] : false);
		parent::assign('opt', $this->opt());
		parent::display($template, $cache_id, $compile_id);
	}
}
?>
