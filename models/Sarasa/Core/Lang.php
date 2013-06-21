<?php

namespace Sarasa\Core;

class Lang {
	public static function _($phrase) {
		switch($phrase)
		 {
		 	/** **/
			case 'System error has occured':
				switch($_SESSION['lang']) {
					default:
						return $phrase;
				}
				break;
				
			default:
				return $phrase;
		}
	}
}

?>
