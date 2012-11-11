ALTER TABLE users ADD approved bit NOT NULL;
UPDATE users SET approved = 1;
ALTER TABLE users ADD admin bit NOT NULL;
UPDATE users SET admin = 0;
UPDATE users SET admin = 1 WHERE username = 'yournamehere';