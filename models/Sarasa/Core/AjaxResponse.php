<?php

namespace Sarasa\Core;

class AjaxResponse {
	
	private $_responses;
	
	function AjaxResponse() {
		$this->_responses = array();
	}

	public function assign($id, $value, $val = NULL) {
		if ($val && $value == 'innerHTML') $value = $val;
		$this->_responses[] = array('assign', $id, $value);
	}

	public function script($script) {
		$this->_responses[] = array('script', $script);
	}

	public function redirect($url) {
		$this->_responses[] = array('redirect', $url);
	}

	public function log($text) {
		$this->_responses[] = array('log', $text);
	}

	public function fadeOut($id) {
		$this->_responses[] = array('fadeout', $id);
	}

	public function append($id, $type, $html = NULL) {
		if (!$html) $html = $type;
		$this->_responses[] = array('append', $id, $html);
	}

	public function prepend($id, $type, $html = NULL) {
		if (!$html) $html = $type;
		$this->_responses[] = array('prepend', $id, $html);
	}

	public function alert($value) {
		$this->script('alert("'.$value.'");');
	}

	public function remove($value) {
		$this->script('$("#'.$value.'").remove();');
	}
	
	public function toJSON() {
		return json_encode($this->_responses);
	}
	
}
