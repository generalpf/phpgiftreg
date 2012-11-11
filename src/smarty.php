<?php
define('SMARTY_DIR',str_replace("\\","/",getcwd()).'/includes/Smarty-3.1.12/libs/');
require_once(SMARTY_DIR . 'Smarty.class.php');
$smarty = new Smarty();
$smarty->testInstall();
$smarty->assign('name', 'Ryan');
$smarty->display('test.tpl');
?>
hello!
