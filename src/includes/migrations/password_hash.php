<?php
/* Widens users.password to fit password_hash() output, which can grow when
	PHP changes its default algorithm.  Existing MD5/SHA1/plaintext values are
	left alone; login upgrades each one the next time its user signs in. */
return function($dbh, $opt) {
	$users = $opt["table_prefix"] . "users";
	$column = getColumnInfo($dbh, $users, "password");
	if ($column && $column["CHARACTER_MAXIMUM_LENGTH"] < 255) {
		$dbh->exec("ALTER TABLE `$users` MODIFY COLUMN `password` varchar(255) NOT NULL default ''");
	}
};
?>
