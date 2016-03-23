<?php
/*
 * Copyright (c) 2007-2013 DBee SA 
 * 34-36 rue de la Belle Feuille 92100 Boulogne Billancourt, FRANCE
 * Tous droits reserves
 *
 * $Id$
 *
 */

require_once 'lib/php/utils/StringUtils.class.php';

class XMLUtils {

	private static $_path;
	private static $_dom;

	public static function getXMLErrors($xmlFilePath)
	{
		$results = "";
		$file = file_get_contents($xmlFilePath, true);
		libxml_use_internal_errors(true);
		$dom = new DOMDocument('1.0', 'utf-8');
		$xml = $dom->loadXML($file);			
		$errors = libxml_get_errors();			
		foreach ($errors as $error)
		{
			$results .= self::formatXMLErrors($error, $xml);
		}
		libxml_clear_errors();
		return $results;
	}
	
	private static function formatXMLErrors($error, $xml)
	{
		$return = "[subTitle.smi] <br />";
		$return .= $xml[$error->line - 1] . "\n";
	
		switch ($error->level)
		{
			case LIBXML_ERR_WARNING:
				$return .= "Avertissement $error->code : ";
				break;
			 case LIBXML_ERR_ERROR:
				$return .= "Erreur $error->code : ";
				break;
			case LIBXML_ERR_FATAL:
				$return .= "Erreur Fatale $error->code : ";
				break;
		}

		$return .= trim($error->message) . " (Ligne : $error->line - Colonne : $error->column)";
		if ($error->file) $return .= " (Fichier : $error->file)";

		return $return . "<br />";		
	}
	
	private static function getDOM($sBase = 'config'){
		$dom = null;

		if (! isset(self::$_dom) || is_null(self::$_dom)){
			$dom = new DOMDocument();
			$dom->preserveWhiteSpace = false;
			$dom->formatOutput = true;
			if ( ! file_exists(self::$_path)){
				$dom->appendChild($dom->createElement($sBase));
			}else{
				$dom->load(self::$_path);
			}
		}else{
			$dom = self::$_dom;
		}
		return $dom;
	}

	private static function saveDOM($dom){
		self::$_dom = $dom;
		FSUtils::write(self::$_path, $dom->saveXML());
	}
	
	public static function write($path, $datas){
		self::$_path = $path;
		$dom = self::getDOM();
		$xpath = new DOMXPath($dom);

		// on met a jour si le noeud existe
		$params = $dom->getElementsByTagName('param');
		if ( $params->length > 0){
			for($i=0; $i < $params->length; $i++){
				$n = $params->item($i);
				$name = $n->getAttribute('name');
				if( isset($datas[$name])){
					$n->setAttribute('value',$datas[$name]);
					unset($datas[$name]);
				}else{
					$n->setAttribute('value', '');
				}
			}
		}
		// on insert ceux qui n'existe pas encore.
		foreach($datas as $k=>$v){
			$n = $dom->createElement('param');
			$n->setAttribute('name', $k);
			$n->setAttribute('value', $v);
			$dom->documentElement->appendChild($n);
		}
		self::saveDOM($dom);
	}

	/* Fonctions XML Generiques */

	private static function createXMLParents($dom, $xpath, $query){
		$sDelimiter = '/';
		$aQuery = explode($sDelimiter, $query);
		$alQuery = count($aQuery);
		$nStart = 0;
		if($alQuery>1){
			for($i=$alQuery-2; $i>=0; $i--){
				$node2 = $xpath->query(implode($sDelimiter, array_slice($aQuery, 0, $i+1)));
				if($node2->length > 0){
					$nStart = $i+1;
					break;
				}
			}
		}
		for($i=$nStart; $i<$alQuery; $i++){
			if($i==0){
				$parentNode = $dom->documentElement;
			}else{
				$parentNode = $xpath->query(implode($sDelimiter, array_slice($aQuery, 0, $i)));
				$parentNode = $parentNode->item(0);
			}
			$n = $dom->createElement($aQuery[$i]);
			$parentNode->appendChild($n);
		}
	}

	public static function getXMLNodes($path, $query, $childLimit=0, $text='text') {
		//Probleme de rechargement du dom resolu
		if ( self::$_path != $path)
		{
			self::$_dom = null;
			self::$_path = $path;
		}
		$dom = self::getDOM();
		$xpath = new DOMXPath($dom);

		$nodes = $xpath->query($query);
		if($nodes->length > 0){
			for($i = 0 ; $i < $nodes->length ; $i++){
				$currentNode = $nodes->item($i);
				$item = self::xmlToObject($currentNode, $childLimit, $text);
				$datas[] = $item;
			}
			return $datas;
		}
	}

	private static function xmlToObject($node, $childLimit=0, $text='text') {
		$attributes = $node->attributes;
		$item = new stdClass();
		$item->nodeName = $node->nodeName;
		if($node->nodeName == '#cdata-section'){
			$item->$text = trim($node->nodeValue);
			return $item;
		}
		if (isset($attributes->length))
		{
			for($j = 0 ; $j < $attributes->length ; $j++){
				$currentAttrName = $attributes->item($j)->nodeName;
				$item->$currentAttrName = $node->getAttribute($currentAttrName);
			}
		}
		$i = 0;
		if($node->childNodes != null){
			while($node->childNodes->item($i) && $childLimit != 0){
				$subitem = self::xmlToObject($node->childNodes->item($i), $childLimit-1, $text);
				if($subitem->nodeName == '#cdata-section') $item->$text = $subitem->$text;
				else {
					$nodeName = $subitem->nodeName.'s';
					if(!isset($item->$nodeName)) $item->$nodeName = Array();
					array_push($item->$nodeName, $subitem);
				}
				$i++;
			}
		}
		return $item;
	}

	public static function appendXMLNodes($path, $query, $datas, $removeType=null, $bInsert=false, $sBase=null) {
		if(!is_array($datas))
			throw new Exception(__METHOD__.'() parametre $datas doit etre de type array !');
        self::$_path = $path;
		if($sBase)
			$dom = self::getDOM($sBase);
		else
			$dom = self::getDOM();
        $xpath = new DOMXPath($dom);

        if($query != ''){
            $node = $xpath->query($query);
            if($node->length <= 0){
                self::createXMLParents($dom, $xpath, $query);
            }
            $node = $xpath->query($query);
            $parentNode = $node->item(0);
        } else {
            $parentNode = $dom->documentElement;
        }
        // on supprime l'ancienne config
        if(!$bInsert)
		{
			if(isset($removeType)){
				if($parentNode->hasChildNodes()){
					for($i=0; $i<$parentNode->childNodes->length; $i++){
						$child = $parentNode->childNodes->item($i);
						if($child->nodeName == $removeType) {
							$toRemove[] = $child;
						}
					}
					if(!empty($toRemove)){
						foreach($toRemove as $c){
							$parentNode->removeChild($c);
						}
					}
				}
			} else {
				while($parentNode->firstChild){
					$parentNode->removeChild($parentNode->firstChild);
				}
			}
		}

        self::objectToXML($dom, $parentNode, $datas);
        self::saveDOM($dom);
    }

	private static function objectToXML($dom, $parentNode, $datas, $insertBefore=null) {
        foreach($datas as $d){
			if($d->nodeName == '#cdata-section'){
				$c = $dom->createCDATASection($d->text);
				$parentNode->appendChild($c);
				break;
			}

			$n = $dom->createElement($d->nodeName);
			unset($d->nodeName);
			if(isset($d->text)){
				// traitement du noeud texte
				$c = $dom->createCDATASection($d->text);
				$n->appendChild($c);
				unset($d->text);
			}
			foreach($d as $k=>$v){
			if(is_array($v)){
				self::objectToXML($dom, $n, $v);
			} else $n->setAttribute($k, StringUtils::booltostr($v));
			}
			if($insertBefore == null) $parentNode->appendChild($n);
			else $parentNode->insertBefore($n, $insertBefore);
        }
    }

    public static function updateXMLNode($path, $query, $datas) {
		self::$_path = $path;
		$dom = self::getDOM();
		$xpath = new DOMXPath($dom);

		$node = $xpath->query($query);
		$bCreate = $node->length <= 0;
		if($bCreate){
			$parentQuery = substr($query, 0, strrpos($query, '/'));
			$node = $xpath->query($parentQuery);
			if($node->length <= 0)
				self::createXMLParents($dom, $xpath, $parentQuery);
			$parentNode = $xpath->query($parentQuery);
			$parentNode = $parentNode->item(0);
			$n = $dom->createElement($datas->nodeName);
		} else {
			$n = $node->item(0);
		}
		unset($datas->nodeName);
		if(isset($datas->text)){
			// traitement du noeud texte
			$textFound = false;
			foreach ($n->childNodes as $subNode) {
				if($subNode->nodeType == XML_TEXT_NODE) {
					$n->replaceChild($dom->createCDATASection($datas->text), $subNode);
					$textFound = true;
				} else if($subNode->nodeType == XML_CDATA_SECTION_NODE) {
					$n->replaceChild($dom->createCDATASection($datas->text), $subNode);
					$textFound = true;
				}
			}
			if(!$textFound)
				$n->appendChild($dom->createCDATASection($datas->text));
			unset($datas->text);
		}
		if(isset($datas->removeAttr)){
			if(!empty($datas->removeAttr)){
				if(is_string($datas->removeAttr))
					$datas->removeAttr = Array($datas->removeAttr);
				if(is_array($datas->removeAttr)){
					foreach($datas->removeAttr as $sAttr){
						if($n->hasAttribute($sAttr))
							$n->removeAttribute($sAttr);
					}
				}
				unset($datas->removeAttr);
			}
		}
		foreach($datas as $k=>$v){
			if(is_string($v) || is_bool($v))
				$n->setAttribute($k, StringUtils::booltostr($v));
			else
				$n->setAttribute($k, $v);
		}
		if($bCreate)
			$parentNode->appendChild($n);
		self::saveDOM($dom);
	}


	public static function moveXMLNode($path, $query, $sens=false) {
		self::$_path = $path;
		$dom = self::getDOM();
		$xpath = new DOMXPath($dom);

		$nodes = $xpath->query($query);
		if ($nodes->length > 0) {
			$n = $nodes->item(0);
			$parent_node = $n->parentNode;
			$oNode = self::getXMLNodes($path, $query, -1);

			$delete = $n;
			if($sens == 'up'){
				$node_ref = $delete->previousSibling;
			} else {
				$node_ref = $delete->nextSibling;
				$node_ref = $node_ref->nextSibling;
			}
			self::objectToXML($dom, $parent_node, $oNode, $node_ref);
			$parent_node->removeChild($delete);
			self::saveDOM($dom);
		}
	}
	
	public static function positionXMLNode($sPath, $sQuery, $nPos) {
		self::$_path = $sPath;
		$dom = self::getDOM();
		$xpath = new DOMXPath($dom);

		$nodes = $xpath->query($sQuery);
		if ($nodes->length > 0) {
			$n = $nodes->item(0);
			$oNode = $n;
			$nXMLPos = $oNode->getLineNo();
			$oNodeParent = $n->parentNode;
			$nNewXMLPos = $oNodeParent->getLineNo() + 1 + $nPos;
			if($nNewXMLPos == $nXMLPos) return;
			if($nNewXMLPos < $nXMLPos){
				$oNodeRef = $oNodeParent->childNodes->item($nPos);
				$oNodeParent->insertBefore($oNode, $oNodeRef);
			} else {
				$oNodeRef = $oNodeParent->childNodes->item($nPos + 1);
				$oNodeParent->insertBefore($oNode, $oNodeRef);
			}
			self::saveDOM($dom);
		}
	}

	public static function deleteXMLNodes($path, $query)
	{
		while (self::deleteXMLNode($path, $query)) {}
	}
	
	public static function deleteXMLNode($path, $query) {
		self::$_path = $path;
		$dom = self::getDOM();
		$xpath = new DOMXPath($dom);

		$node = $xpath->query($query);
		if($node->length > 0){
			$n = $node->item(0);
			$parent_node = $n->parentNode;
			$parent_node->removeChild($n);
			self::saveDOM($dom);
			return true;
		}
		return false;
	}

	/* Fonctions XML Generiques */

	public static function getParams($path, $query){
		self::$_path = $path;
		$datas = array();
		$dom = self::getDOM();
		$xpath = new DOMXPath($dom);

		// on met a jour si le noeud existe
		$params = $xpath->query($query);
		if ( $params->length > 0){
			for($i=0; $i < $params->length; $i++){
				$n = $params->item($i);
				$datas[$n->getAttribute('name')] = StringUtils::strtobool($n->getAttribute('value'));
			}
		}
		return $datas;
	}
}
?>
