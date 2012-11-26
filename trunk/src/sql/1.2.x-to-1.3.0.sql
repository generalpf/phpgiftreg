ALTER TABLE items ADD comment text NULL;

CREATE TABLE events (
  eventid int(11) NOT NULL auto_increment,
  userid int(11) default NULL,
  description varchar(100) NOT NULL default '',
  eventdate date NOT NULL default '0000-00-00',
  recurring tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (eventid)
);

INSERT INTO events(userid,description,eventdate,recurring) VALUES(NULL,'Christmas','2000-12-25',1);
