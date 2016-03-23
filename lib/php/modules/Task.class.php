<?php
/*
 * Copyright (c) 2007-2013 DBee SA 
 * 34-36 rue de la Belle Feuille 92100 Boulogne Billancourt, FRANCE
 * Tous droits reserves
 *
 * $Id$
 *
 */

require_once 'lib/php/utils/XMLUtils.class.php';

class Task
{		
	
	public static /* String */ function getTasks()
	{
		$lang = "fr";
		
		$path = 'data/tasks.xml';		
		$query = 'task/text[@lang="' . $lang . '"]';
		$limit = "-1";
		$tasks = XMLUtils::getXMLNodes($path, $query, $limit);
		
		return $tasks;
	}
}
