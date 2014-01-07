<?php
/**
 * @package Migrate SAMBC Firebird to MySQL v0.2
 * @version $Id: functions.php 042 2014-01-07 12:44:33Z andy $
 * @author Andis Grosšteins
 * @copyright (C) 2014 - Andis Grosšteins (http://axellence.lv)
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * 
 */

/**
 * Wrapper for $_SERVER variables
 * @param string $var key to get from $_SERVER array
 * @param mixed $def default return value
 * @return mixed _SERVER variable value or $def
 */
function env($var, $def = NULL){
	return isset($_SERVER[$var])? $_SERVER[$var] : $def;
}

/**
 * Wrapper for $_GET variables
 * @param string $var key to get from $_GET array
 * @param mixed $def default return value
 * @return mixed _GET variable value or $def
 */
function getvar($var, $def = NULL){
	return isset($_GET[$var])? $_GET[$var] : $def;
}

/**
 * Wrapper for $_POST variables
 * @param string $var key to get from $_POST array
 * @param mixed $def default return value
 * @return mixed _POST variable value or $def
 */
function postvar($var, $def = NULL){
	return isset($_POST[$var])? $_POST[$var] : $def;
}

/**
 * Wrapper for $_COOKIE variables
 * @param string $var key to get from $_COOKIE array
 * @param mixed $def default return value
 * @return mixed _COOKIE variable value or $def
 */
function cookie($var, $def = NULL){
	return isset($_COOKIE[$var])? $_COOKIE[$var] : $def;
}

/**
 * Apply icon charset conversion to multidimensional array
 * @param string $from_charset character set from which to convert
 * @param string $to_charset target character set
 * @param mixed $array array
 * @return array
 */
function iconv_deep($from_charset, $to_charset, $array){
	$return = array();
	if(empty($array) || !is_array($array)){
		return $array;
	}
	foreach($array as $k => &$val){
		if(is_string($val)){
			// Sometimes PHP crashes if iconv is given an ASCII string for conversion
			$enc = mb_detect_encoding($val);
			if($enc != 'ASCII' && $enc != $to_charset){
				//$data = mb_convert_encoding($data, 'ISO-8859-2', 'UTF-8');
				$val = iconv($from_charset, $to_charset, $val);
			}
		}
		if(is_array($val)){
			$val = iconv_deep($from_charset, $to_charset, $val);
		}
		$return[$k] = $val;
	}
	return $return;
}

/**
 * Apply htmlentities to an array
 * @param mixed $input Array or string
 * @param string $encoding desired encoding. default: utf-8
 * @return mixed Input with all HTML entities converted in strings
 */
function htmlentities_deep($input, $encoding = 'utf-8'){
	if(is_string($input)){
		return htmlentities( $input, ENT_QUOTES, $encoding, FALSE);
	}
	if(is_array($input) || is_object($input)){
		foreach($input as $k => &$val){
			if(is_string($val)){
				$val = htmlentities( $val, ENT_QUOTES, $encoding, FALSE);
			}
			if(is_array($val)|| is_object($val)){
				$val = htmlentities_deep($val, $encoding);
			}
		}
	}
	return $input;
}

/**
 * Debug wrapper for var_dump()
 * @param mixed $args ...
 */
function debug($args){
	if(defined('DEBUG') && DEBUG != FALSE){
		$args = func_get_args();
		foreach ($args as $arg) {
			var_dump($arg);
		}
	}
}

/**
 * Map PHP charset string to MySQL charset string
 * @param string $charset A PHP multibyte compatible charset string
 * @param string $fallback charset. default - BINARY
 * @return mixed returns valid MySQL charset string if mapping is found, othervise - false
 */
function mysql_charset($charset, $fallback = 'BINARY'){
	$charset = preg_replace('/^win(downs)?-?(\d+)/i', 'WIN\1', $charset);
	$charset = strtoupper($charset);
	// Charset names translation table mb_string to MySQL
	$charsets = array(
		'BIG-5' => 'big5', // Big5 Traditional Chinese
		// 'dec8', // DEC West European
		'CP1252' => 'cp850', 'WIN-1252' => 'cp850', // DOS West European
		'hp8', // HP West European
		'KOI8-R' => 'koi8r', // KOI8-R Relcom Russian
		'WIN-1252' => 'latin1', 'CP1252' => 'latin1', // cp1252 West European
		'ISO-8859-2' => 'latin2', // ISO 8859-2 Central European
		// '7BIT' => 'swe7', // 7bit Swedish
		'US-ASCII' => 'ascii', 'ASCII' => 'ascii', // US ASCII
		'EUC-JP' => 'ujis', // EUC-JP Japanese
		'SJIS' => 'sjis', // Shift-JIS Japanese
		'ISO-8859-8' => 'hebrew', // ISO 8859-8 Hebrew
		//'tis620', // TIS620 Thai
		'EUC-KR' => 'euckr', // EUC-KR Korean
		//'koi8u', // KOI8-U Ukrainian
		'GB2312' => 'gb2312', // GB2312 Simplified Chinese
		'ISO-8859-7' => 'greek', // ISO 8859-7 Greek
		'WIN-1250' => 'cp1250', 'CP1250' => 'cp1250', // Windows Central European
		'GB18030' => 'gbk', // GBK Simplified Chinese
		'ISO-8859-9' => 'latin5', // ISO 8859-9 Turkish
		// 'armscii8', // ARMSCII-8 Armenian
		'UTF-8' => 'utf8', 'UTF8' => 'utf8', // UTF-8 Unicode
		'UCS-2' => 'ucs2', 'UCS2' => 'ucs2', // UCS-2 Unicode
		'WIN-866' => 'cp866', 'CP866' => 'cp866', // DOS Russian
		'CP895' => 'keybcs2', // DOS Kamenicky Czech-Slovak
		'X-MAC-CE' => 'macce', // Mac Central European
		'MACINTOSH' => 'macroman', // Mac West European
		'IBM852' => 'cp852', 'CP852' => 'cp852', // DOS Central European
		'ISO-8859-13' => 'latin7', // ISO 8859-13 Baltic
		// 'UTF-8' => 'utf8mb4', // UTF-8 Unicode
		'WIN-1251' => 'cp1251', 'CP1251' => 'cp1251',// Windows Cyrillic
		'UTF-16' => 'utf16', // UTF-16 Unicode
		'UTF-16LE' => 'utf16le', // UTF-16LE Unicode
		'WIN-1256' => 'cp1256', 'CP1256' => 'cp1256', // Windows Arabic
		'WIN-1257' => 'cp1257', 'CP1257' => 'cp1257', // Windows Baltic
		'UTF-32' => 'utf32', 'UTF32' => 'utf32',// UTF-32 Unicode
		'BINARY' => 'binary', // Binary pseudo charset
		'GEOSTD8' => 'geostd8', // GEOSTD8 Georgian
		'WIN-932' => 'cp932', 'CP932' => 'cp932', // SJIS for Windows Japanese
		'JIS-MS' => 'eucjpms', // UJIS for Windows Japanese
	);
	if(isset($charsets[$charset])){
		return $charsets[$charset];
	}else{
		$fallback = strtoupper($fallback);
		$fallback = isset($charsets[$fallback])? $charsets[$fallback] : FALSE;
		return $fallback;
	}
	return FALSE;
}
?>