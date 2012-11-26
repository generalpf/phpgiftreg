UPDATE ranks SET rendered = REPLACE(rendered,'star_on.gif\"','star_on.gif\" alt=\"*\"');
UPDATE ranks SET rendered = REPLACE(rendered,'star_off.gif\"','star_off.gif\" alt=\"\"');

ALTER TABLE users ADD comment text NULL;
