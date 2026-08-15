-- Runs once on first start of the MySQL container.
--
-- Doctrine appends "_test" to the database name when APP_ENV=test, so the
-- application user needs rights on that second database as well — the image
-- only grants it on MYSQL_DATABASE.
CREATE DATABASE IF NOT EXISTS handwerk_test;
GRANT ALL PRIVILEGES ON handwerk_test.* TO 'handwerk'@'%';
FLUSH PRIVILEGES;
