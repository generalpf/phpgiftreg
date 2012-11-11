CREATE TABLE families (
  familyid int(11) NOT NULL auto_increment,
  familyname varchar(255) NOT NULL default '',
  PRIMARY KEY  (familyid)
);

CREATE TABLE memberships (
  userid int(11) NOT NULL default '0',
  familyid int(11) NOT NULL default '0',
  PRIMARY KEY  (userid,familyid)
);

ALTER TABLE users ADD initialfamilyid int NULL;
