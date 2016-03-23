<?php
/*
 * Copyright (c) 2007-2013 DBee SA 
 * 34-36 rue de la Belle Feuille 92100 Boulogne Billancourt, FRANCE
 * Tous droits reserves
 *
 * $Id$
 *
 */

abstract class StringUtils
{	
	/**
	 * Function: getData
	 * Filtrer un texte recupere a partir de la BD
	 *
	 * Parameters:
	 * $str - Texte recupere a partir de la BD
	 * $encoding - Encodage (Par defaut : UTF-8)
	 *
	 * Return:
	 * $str - Texte filtre
	 */
	
	public static function getData($str, $encoding="UTF-8", $option=null)
	{
		$str = str_replace("%u20AC", "EUR", $str); //Remplacer '%u20AC' par 'EUR'
		$currentEncoding = mb_detect_encoding($str, mb_detect_order(), true);
		if (!empty($currentEncoding) && ($currentEncoding != $encoding)) {
			$strConvert = mb_convert_encoding($str, $encoding, $currentEncoding);
		} else {
			$strConvert = $str;
		}

		if ($option == "XmlAttribute") {
			$strConvert = str_replace("&", "&amp;", $strConvert);
			$strConvert = str_replace("<", "&lt;", $strConvert);
			$strConvert = str_replace(">", "&gt;", $strConvert);
			$strConvert = str_replace('"', '&quot;', $strConvert);
		}
		return $strConvert;
	}
	
	public static function setData($str, $encoding="UTF-8")
	{
		$currentEncoding = mb_detect_encoding($str, mb_detect_order(), true);
		if (!empty($currentEncoding) && ($currentEncoding != $encoding)) {
			$strConvert = mb_convert_encoding($str, $encoding, $currentEncoding);
		} else {
			$strConvert = $str;
		}
		return $strConvert;
	}
	
	public static /* String */ function traceError($e, $msg)
	{		
		define('DEBUG', true);
		ob_end_clean();
		if ( DEBUG ) {
			echo "<strong> Error : </strong>".$msg."<br/>";
			echo "<strong> File : </strong>".$e->getFile()."<br/>";
			echo "<strong> Line : </strong>".$e->getLine();
		} else {
			echo  $msg;
		}
	}
	
	public static /* Void */ function clearQueryString($sQS)
	{
		if (substr($sQS, -1) == '#') $sQS = substr($sQS, 0, -1);
		return $sQS;
	}
	
	public static function isUtf8($str){
		return ( mb_detect_encoding($str . 'a', "UTF-8, ISO-8859-1", true) == "UTF-8");
	}
	
	public static function decode($str){
		// par securitÃ© on ne boucle pas plus de 5 fois.
		$secu = 5;
		while ( self::isUtf8($str) && $secu > 0){
			$str = utf8_decode($str);
			$secu--;
		}
		return $str;
	}
	
	public static function encode($str){
		if ( ! self::isUtf8($str))
			return utf8_encode($str);
		else{
			return utf8_encode(self::decode($str));
		}
	}
	
	public static function isAlphaNumeric($text)
	{
		$pattern = "/^([0-9a-zA-Z'àâéèêôùûüïîçÀÂÉÈÔÙÛÇ[:blank:]-]{1,75})$/";
		if (preg_match($pattern, utf8_decode($text))) return true;
		return false;
	}
	
	public static function isAlphabetic($text)
	{
		$pattern = "/^([a-zA-Z'àâéèêôùûüïîçÀÂÉÈÔÙÛÇ[:blank:]-]{1,75})$/";
		if (preg_match($pattern, utf8_decode($text))) return true;
		return false;
	}
		
	public static function isValidAddress($adr)
	{
		if (self::isAlphaNumeric($adr) && !is_numeric($adr)) return true;
		return false;
	}
	
	public static function isValidEmail($email)
	{
		$q = "/^[-a-z0-9\._]+@[-a-z0-9\.]+\.[a-z]{2,4}$/i";
		if ( ! preg_match($q, $email))
			return false;
		return true;
	}
	
	public static function isValidDate($date){
		if ( ! is_numeric($date) )
			return false;
			
		if ( strlen($date) != 8 )	
			return false;
	
		$y = substr($date,0,4);
		$m = substr($date,4,2);
		$i = substr($date,6,2);
		
    	return checkdate($m,$i,$y);
	}
	
	public static function isValidTime($time){
		$q = "";
		if ( ! preg_match($q, $ip))
			return false;
		return true;
	}
	
	public static function isValidTimecode($tc){
		$q = "/^([0-9]{2}):([0-5][0-9]):([0-5][0-9])\.[0-9]$/";
		if ( ! preg_match($q, $tc))
			return false;
		return true;
		
	}
	
	public static function isValidIP($ip){
		$q = "/^(([1-9]?[0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([1-9]?[0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])(\/([1-2]?[0-9]|3[0-2]))?$/";
		if ( ! preg_match($q, $ip))
			return false;
		return true;
	}
	
	public static function mysqlDateTimeFormat($format, $datetime){
		if ( empty($datetime)) return '';
		return date($format,self::mysqlDateTime2Timestamp($datetime));
	}
	
	public static function mysqlDateFormat($format, $date){
		if ( empty($date)) return '';
		return date($format,self::mysqlDate2Timestamp($date));
	}
	
	public static function mysqlDateTime2Timestamp($datetime){
		list($date, $time) = explode(" ",$datetime);
		list($year, $month, $day) = explode("-",$date);
		list($hour, $minute, $second) = explode(":",$time);
		return mktime( $hour, $minute, $second, $month, $day, $year);
	}
	
	public static function mysqlDate2Timestamp($date){
		list($year, $month, $day) = explode("-",$date);
		return mktime( 0, 0, 0, $month, $day, $year);
	}
	
	
	public static function generateRandomString($length = 8){

	  $password = "";
	  $possible = "0123456789bcdfghjkmnpqrstvwxyz"; 
	    
	  $i = 0; 
	    
	  // add random characters to $password until $length is reached
	  while ($i < $length) { 
	    $char = substr($possible, mt_rand(0, strlen($possible)-1), 1);
	    if (!strstr($password, $char)) { 
	      $password .= $char;
	      $i++;
	    }
	  }
	  return $password;
	}
	
	public static function generateXKey($length=32){
		$password = '';
		$possible = implode('', range('a', 'z')).implode('', range(0, 9)).implode('', range('A', 'Z'));
		
		$i = 0; 
	    // add random characters to $password until $length is reached
		while ($i < $length) { 
		$char = substr($possible, mt_rand(0, strlen($possible)-1), 1);
		$password .= $char;
		$i++;
		}
		return $password;
	}
	
	public static function strtobool($string){
		switch($string){
			case '1' :
			case 'true' :
			case 'on' :
				return true;
			case '0' :
			case 'false' :
			case 'off' :
				return false;
			default :
				return $string;
		}
	}
	
	public static function booltostr($bool){
		if($bool === true) return 'true';
		if($bool == 'on') return 'true';
		if($bool === false) return 'false';
		return $bool;
	}
	
	public static function specialChars($str)
	{
		$str = str_replace("&", "&amp;", $str);
		$str = str_replace("<", "&lt;", $str);
		$str = str_replace(">", "&gt;", $str);
		$str = str_replace('"', '&quot;', $str);
		return $str;
	}
	
	public static function getUrl()
	{
		return (isset($_SERVER['HTTPS']) ? 'https' : 'http') . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	}
	
}
?>
