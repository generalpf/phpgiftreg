CREATE TABLE ranks (
  ranking int(11) NOT NULL auto_increment,
  title varchar(50) NOT NULL default '',
  rendered varchar(255) NOT NULL default '',
  rankorder int(11) NOT NULL default '0',
  PRIMARY KEY  (ranking)
) TYPE=MyISAM;

INSERT INTO ranks VALUES (1,'1 - Wouldn\'t mind it','<img src=\"images/star_on.gif\"><img src=\"images/star_off.gif\"><img src=\"images/star_off.gif\"><img src=\"images/star_off.gif\"><img src=\"images/star_off.gif\">',1);
INSERT INTO ranks VALUES (2,'2 - Would be nice to have','<img src=\"images/star_on.gif\"><img src=\"images/star_on.gif\"><img src=\"images/star_off.gif\"><img src=\"images/star_off.gif\"><img src=\"images/star_off.gif\">',2);
INSERT INTO ranks VALUES (3,'3 - Would make me happy','<img src=\"images/star_on.gif\"><img src=\"images/star_on.gif\"><img src=\"images/star_on.gif\"><img src=\"images/star_off.gif\"><img src=\"images/star_off.gif\">',3);
INSERT INTO ranks VALUES (4,'4 - I would really, really like this','<img src=\"images/star_on.gif\"><img src=\"images/star_on.gif\"><img src=\"images/star_on.gif\"><img src=\"images/star_on.gif\"><img src=\"images/star_off.gif\">',4);
INSERT INTO ranks VALUES (5,'5 - I\'d love to get this','<img src=\"images/star_on.gif\"><img src=\"images/star_on.gif\"><img src=\"images/star_on.gif\"><img src=\"images/star_on.gif\"><img src=\"images/star_on.gif\">',5);

UPDATE items SET ranking = 1;

