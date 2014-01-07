<?php
/**
 * @package Migrate SAMBC Firebird to MySQL v0.2
 * @version $Id: migrate.php 042 2014-01-07 12:44:33Z andy $
 * @author Andis Grosšteins
 * @copyright (C) 2014 - Andis Grosšteins (http://axellence.lv)
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * 
 * 
 * This script generates a valid MySQL script from SAMBC Firebird database if WORK_MODE is set to WORK_MODE_FILE
 * or inserts data from Firebird DB directly into MySQL database if WORK_MODE is set to WORK_MODE_INSERT.
 * In latter case you have to make sure the database and its tables exist.
 * 
 * Make sure that the MySQL tables are empty and are set up for receiving utf8 data.
 * I use utf8_unicode_ci, which makes life easier because it is case insensitive.
 * You can convert MySQL database to utf8 with following SQL: ALTER DATABASE `samdb` CHARACTER SET utf8 COLLATE utf8_unicode_ci;
 * 
 * Requirements:
 * 1) Recent PHP version. I'm using 5.5.1 curently and have not tested this script on older versions
 * 2) PHP PDO extension enabled @link http://php.net/manual/en/book.pdo.php
 * 
 * TODO: Drop and recreate MySQL tables from SAMBC sql files
 * TODO: Maybe check songlist entries for moved/removed files before import
 * 
 * 
 */

define('VERISON','0.2');

set_time_limit(600);

require_once('functions.php');
require_once('config.php');
$output_file = "SAMBC_mysql.sql";

/**
 * Set work mode
 */
define('WORK_MODE', WORK_MODE_FILE);
//define('WORK_MODE', WORK_MODE_INSERT);


/**
 * Most charsets supported by FirebirdSQL
 */
$fb_charset = array(
	'BINARY','OCTETS','ASCII','UTF8',
	'ISO8859_1','ISO8859_2','ISO8859_3','ISO8859_4','ISO8859_5','ISO8859_6','ISO8859_7','ISO8859_8','ISO8859_9','ISO8859_13',
	'WIN1250','WIN1251','WIN1252','WIN1253','WIN1254','WIN1255','WIN1256','WIN1257','WIN1258',
	'DOS437','DOS737','DOS775','DOS850','DOS852','DOS857','DOS858','DOS860','DOS861','DOS862','DOS863','DOS864','DOS865','DOS866','DOS869',
	'BIG_5','KSC_5601','SJIS_0208','EUCJ_0208','GB_2312','CP943C','TIS620',
	'KOI8R','KOI8U','CYRL'
);

try {
	$options = array(
		PDO::ATTR_AUTOCOMMIT=>TRUE,
		PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
	);
	$charset_search = array('/^cp-?(\d+)/i','/^iso-?(\d+)-(\d+)/i','/^utf-?(\d+)/i');
	$charset_replace = array('WIN\1','ISO\1_\2','UTF\1');
	$charset = preg_replace($charset_search, $charset_replace, SAM_CHARSET);
	if(!in_array($charset,$fb_charset)){
		$charset = 'NONE';
	}
	$connect_string = "firebird:dbname=".FB_HOST.":".FB_DATABASE.";charset=".$charset;
	$fb_pdo = new PDO ($connect_string, FB_USER, FB_PASS, $options);
} catch (PDOException $e) {
	header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
	echo "Failed to get Firebird DB handle: " . $e->getMessage() . "\n";
	exit;
}

try {
	$options = array(
		PDO::ATTR_AUTOCOMMIT=>FALSE,
		PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
	);
	$mysql_pdo = new PDO ("mysql:dbname=".MYSQL_DATABASE.";host=".MYSQL_HOST, MYSQL_USER, MYSQL_PASS, $options);
} catch (PDOException $e) {
	header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
	echo "Failed to get MySQL handle: " . $e->getMessage() . "\n";
	exit;
}

$t_start = microtime(true);

if(php_sapi_name() != 'cli'){
	echo '<pre>';
	echo "Job started at ".date("Y-m-d H:i:s\n");
	flush();
	ob_flush();
}

/**
 * Table name translations
 */
$tables_translation = array(
	'ADZ' => 'adz',
	'CATEGORY' => 'category',
	'CATEGORYLIST' => 'categorylist',
	'DISK' => 'disk',
	'EVENT' => 'event',
	'EVENTTIME' => 'eventtime',
	'FIXEDLIST' => 'fixedlist',
	'FIXEDLIST_ITEM' => 'fixedlist_item',
	'HISTORYLIST' => 'historylist',
	'QUEUELIST' => 'queuelist',
	'REQUESTLIST' => 'requestlist',
	'SONGLIST' => 'songlist',
);

$sql_out = array();

/**
 * Execute MySQL insert statement
 * @param string mysql_sql Valid SQL statement
 */
function insertMySQL($mysql_sql){
	global $mysql_pdo;
	$mysql_pdo->beginTransaction();
	$query = $mysql_pdo->prepare($mysql_sql);
	try{
		$result = $query->execute();
	} catch (PDOException $e) {
		echo $e->getMessage()."\n";
		echo "[!!!]\nFAIL: doing rollback!\n";
		$mysql_pdo->rollBack();
		return false;
	}
	echo 'Inserted '.$query->rowCount()." rows\n";
	return $mysql_pdo->commit();
}

/**
 * Charset as used by MySQL
 */
$mysql_charset = mysql_charset(CHARSET);

if(WORK_MODE == WORK_MODE_FILE){
	// Clear the output file
	file_put_contents($output_file, '');
	
	$sql_out[] = "
-- SAMBC Firebird to MySQL migration v".VERISON."
-- Generated at: ".date('Y-m-d H:i:s')."
-- Host: localhost    Database: ".MYSQL_DATABASE."
-- ------------------------------------------------------

USE `".MYSQL_DATABASE."`;";

	// this emulates mysqldump 5.6.12
	$sql_out[] = "/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES $mysql_charset */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = $mysql_charset */;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = '' */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;";
	file_put_contents($output_file , implode("\n", $sql_out), FILE_APPEND);
}
$sql_out = array();

if(WORK_MODE == WORK_MODE_INSERT){
	// Set MySQL connection charset
	$charset_sql = "SET character_set_results = '$mysql_charset',
	character_set_client = '$mysql_charset',
	character_set_connection = '$mysql_charset',
	character_set_database = '$mysql_charset',
	character_set_server = '$mysql_charset'";
	$mysql_pdo->exec($charset_sql);
}

foreach($tables_translation as $fb_table => $mysql_table){
	$count_sql="SELECT COUNT(*) FROM $fb_table";
	$result = $fb_pdo->query($count_sql);
	$rows = $result->fetch(PDO::FETCH_NUM);
	$result->closeCursor();
	$total_rows = $remainig_rows = (int)$rows[0];

	$fb_sql = "SELECT * FROM $fb_table ORDER BY ID ASC";
	$t_fetch_start = microtime(true);
	$query = $fb_pdo->prepare($fb_sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
	$query->execute();

	echo "Processing $fb_table => `$mysql_table`\n";
	echo "Total rows: ".$total_rows."\n";
	if(php_sapi_name() != 'cli'){
		flush();
		ob_flush();
	}
	$sql_out_tmp = array();
	if($total_rows){
		$sql_out_tmp[] = "--";
		$sql_out_tmp[] = "-- Data for table `$mysql_table`";
		$sql_out_tmp[] = "--";
		
		// Add charset hints to file
		if(CHARSET == 'UTF-8' && WORK_MODE == WORK_MODE_FILE){
			$sql_out_tmp[] = "/*!40101 SET character_set_client = '$mysql_charset' */;";
		}
		
		$table_sql = array();
		$insert_part = "";
		$data_sql = array();
		$rows = 0;

		while($row = $query->fetch(PDO::FETCH_ASSOC)){
			// Convert encoding for some tables
			if(CHARSET != SAM_CHARSET && in_array($fb_table, array('HISTORYLIST','SONGLIST','QUEUELIST','CATEGORY','EVENT','ADZ'))){
				$row = iconv_deep(SAM_CHARSET, CHARSET.'//TRANSLIT', $row);
			}

			if(empty($insert_part)){
				$insert_part = "INSERT INTO `$mysql_table`";
				$fields = array_keys($row);
				$insert_part .= "(`".implode('`,`', $fields)."`) VALUES";
				$table_sql[] = $insert_part;
			}

			foreach($row as $field => &$value){
				// needs some more processing for empty albumyear and other numeric types
				if(strtolower($field) == 'albumyear' || is_numeric($value)){
					$value = strpos($value,'.')? (float)$value : (int)$value;
					continue;
				}
				if($value === NULL){
					$value = 'NULL';
					continue;
				}
				// Quote string fields
				if(is_string($value)){
					$value = $mysql_pdo->quote($value);
					continue;
				}
			}
			$data_sql[] = "(".implode(",", $row).")";
			
			$rows++;
			$remainig_rows --;
			
			// Prepare and insert each MAX_INSERT_ROWS
			if( ($total_rows > MAX_INSERT_ROWS && $rows >= MAX_INSERT_ROWS) || ($remainig_rows <= MAX_INSERT_ROWS && $remainig_rows <= 0 )){
				$table_sql[] = implode(",\n", $data_sql).";\n";
				if(WORK_MODE == WORK_MODE_INSERT){
					$mysql_sql = implode("\n", $table_sql);
					insertMySQL($mysql_sql);
				}
				if(WORK_MODE == WORK_MODE_FILE){
					$sql_out_tmp[] = implode("\n", $table_sql);
					file_put_contents($output_file , implode("\n", $sql_out_tmp), FILE_APPEND);
					//$sql_out[] = implode("\n", $sql_out_tmp);
				}
				$table_sql = $data_sql = $sql_out_tmp = array();
				$table_sql[] = "-- More data for table `$mysql_table`";
				$table_sql[] = $insert_part;
				$rows = 0;
			}
		}
	}
	echo "Processed in ".round(microtime(true)-$t_fetch_start,3)."s\n";
	echo "-------------------------------------------------------\n\n";
}

if(WORK_MODE == WORK_MODE_FILE){
	$sql_out[] = "/*!50003 SET sql_mode = @saved_sql_mode */ ;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;";

	$sql_out[] = "/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;";


$sql_out[] = "-- Dump completed on ".date('Y-m-d H:i:s');
	file_put_contents($output_file , implode("\n", $sql_out), FILE_APPEND);
}
echo "Time elapsed: ".round(microtime(true)-$t_start,3)."s\n";
echo "Job finished at ".date("Y-m-d H:i:s\n");
?>