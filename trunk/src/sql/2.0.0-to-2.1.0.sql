CREATE TABLE `subscriptions` (
	`publisher` int(11) NOT NULL,
	`subscriber` int(11) NOT NULL,
	`last_notified` datetime DEFAULT NULL,
	PRIMARY KEY (`publisher`,`subscriber`)
);
