<?php

define('SMARTY_DIR', dirname(__FILE__) . "/Smarty-3.1.12/libs/");
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

	public function display($t) {
		parent::assign('isadmin', $_SESSION['admin']);
		parent::assign('opt', $this->opt());
		parent::display($t);
	}
}
?>
