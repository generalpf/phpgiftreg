CREATE TABLE allocs(itemid int NOT NULL, userid int NOT NULL, bought bit NOT NULL, quantity int NOT NULL, PRIMARY KEY(itemid,userid,bought));   

INSERT INTO allocs(itemid,userid,bought,quantity) SELECT itemid,boughtid,1,1 FROM items WHERE boughtid IS NOT NULL;
INSERT INTO allocs(itemid,userid,bought,quantity) SELECT itemid,reservedid,0,1 FROM items WHERE reservedid IS NOT NULL;

ALTER TABLE items DROP COLUMN boughtid;
ALTER TABLE items DROP COLUMN reservedid;

ALTER TABLE items ADD quantity int NOT NULL;

UPDATE items SET quantity = 1 WHERE quantity = 0;

ALTER TABLE users ADD email_msgs bit NOT NULL;
UPDATE users SET email_msgs = 1 WHERE email IS NOT NULL;

ALTER TABLE items MODIFY price decimal(7,2);

ALTER TABLE users ADD COLUMN list_stamp datetime NOT NULL;
UPDATE users SET list_stamp = NOW();
