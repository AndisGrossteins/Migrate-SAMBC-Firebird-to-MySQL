Migrate-SAMBC-Firebird-to-MySQL
===============================

While managing SAM Broadcaster for [Radio H2O](http://radioh2o.lv/ "Radio H2O") I got sick of all the bugs and charset incompatibilities between filesystem and Firebird DB dealing with other SAMBC quirks. So, I decided to migrate SAMBC data from FirebirdSQL to MySQL to see if it could help.  In short; it didn’t solve main problem, because SAMBC messes up everything by using Windows default code page for its internal workings. Never the less, it was a nice exercise in coding for two databases and charset conversion.

## Migrate SAM Broadcaster Firebird database to MySQL ##

This script generates a valid MySQL SQL script from SAMBC Firebird database if `WORK_MODE` in `config.php` is set to `WORK_MODE_FILE`
or inserts data from Firebird DB directly into MySQL database if `WORK_MODE` is set to `WORK_MODE_INSERT`.
In latter case you have to make sure the database and its tables exist.

Make sure that the MySQL tables are empty and are set up for receiving utf8 data.
I use `utf8_unicode_ci`, which makes life easier because it is case insensitive.
You can convert MySQL database to utf8 with following SQL: `ALTER DATABASE samdb CHARACTER SET utf8 COLLATE utf8_unicode_ci`;

__Note:__ Charset conversion can be disable by setting `SAM_CHARSET` and `CHARESET` in `config.php` to same value.  

### Requirements: ###

1. Recent PHP version. I'm curently using PHP/5.5.1 and have not tested this script on older versions
2. PHP PDO extension enabled http://php.net/manual/en/book.pdo.php

---------------------------------

### Possible TODO: ###
* Drop and recreate MySQL tables from SAMBC sql files
* Maybe check songlist entries for moved/removed files before import
* Next step: Migrate to other software (Take note, Spacial)
