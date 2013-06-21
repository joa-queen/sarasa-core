<?php

namespace Sarasa\Core;

class CustomException extends \Exception {
	
	public function __construct($message, $code = 0, Exception $previous = null) {
		parent::__construct($message, $code);
	}

	public function __toString() {
		return Lang::_($this->message);
	}
}

?>
