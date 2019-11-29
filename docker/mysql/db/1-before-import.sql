-- Set network buffer length to a large byte number
set global net_buffer_length=1000000;
-- Set maximum allowed packet size to a large byte number
set global max_allowed_packet=1000000000;
-- Disable foreign key checking to avoid delays,errors and unwanted behaviour
SET foreign_key_checks = 0;
-- See: https://stackoverflow.com/questions/49992868/mysql-errorthe-user-specified-as-a-definer-mysql-infoschemalocalhost-doe
-- SET GLOBAL innodb_fast_shutdown = 1;

-- following line fixes timestamp issues with legacy default values
SET sql_mode='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';

-- fix mysql error while importing a cak´s dump "The user specified as a definer ('ck-production'@'%') does not exist".
-- @see: https://stackoverflow.com/questions/10169960/mysql-error-1449-the-user-specified-as-a-definer-does-not-exist
GRANT ALL ON *.* TO 'ck-production'@'%' IDENTIFIED BY 'pIMjqqSztIus1y7u'; 
FLUSH PRIVILEGES;

