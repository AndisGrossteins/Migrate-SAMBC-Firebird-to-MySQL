<?php

/**
 * Current directory variable for backward compatibility 
 */
!defined('__DIR__') && define('__DIR__', dirname(__FILE__));
/**
 * Path to application directory
 */
!defined('APP_PATH') && define('APP_PATH', __DIR__);
/**
 * Make a shortcut to DIRECTORY_SEPARATOR
 */
!defined('DS') && define('DS', DIRECTORY_SEPARATOR);
/**
 * Make a shortcut to DIRECTORY_SEPARATOR
 */
!defined('DEBUG') && define('DEBUG', TRUE);

// Set local debug log
ini_set('error_log', './php_errors.log');

// Implicit flushing of output buffer
ob_implicit_flush(TRUE);

/**
 * @constant Firebird database path
 */
define('FB_DATABASE', 'D:/Users/Andis/AppData/Local/SpacialAudio/SAMBC/SAMDB/SAMDB.fdb');

/**
 * @constant Firebird hostname and port. Usually it is 'localhost' for local machine
 */
define('FB_HOST', 'localhost/3050');

/**
 * @constant Firebird user. By default it is 'SYSDBA'
 */
define('FB_USER', 'SYSDBA');

/**
 * @constant Firebird password. By default it is 'masterkey'
 */
define('FB_PASS', 'masterkey');

/**
 * @constant MySQL hostname. Usually it is 'localhost' for local machine
 */
define('MYSQL_HOST', 'localhost');

/**
 * @constant MySQL user
 */
define('MYSQL_USER', 'mysqluser');

/**
 * @constant MySQL password
 */
define('MYSQL_PASS', 'mysqlpassword');

/**
 * @constant MySQL database
 */
define('MYSQL_DATABASE', 'samdb');

/**
 * @constant If set as WORK_MODE, inserts directly into MySQL database
 */
define('WORK_MODE_INSERT','INSERT');

/**
 * @constant If set as WORK_MODE, write sql to a file set in $output_file
 */
define('WORK_MODE_FILE','FILE');

/**
 * @constant Maximum rows to insert at once
 */
define('MAX_INSERT_ROWS',1000);

/**
 * @constant Charset for conversion to UTF-8. Will be used by @iconv in @iconv_deep function
 */
!defined('SAM_CHARSET') && define('SAM_CHARSET','CP1257');

/**
 * @constant Target charset/encoding
 */
!defined('CHARSET') && define('CHARSET','UTF-8');
ini_set('iconv.input_encoding',CHARSET);
ini_set('iconv.internal_encoding',CHARSET);
ini_set('iconv.output_encoding',CHARSET);
ini_set('mbstring.internal_encoding',CHARSET);

?>