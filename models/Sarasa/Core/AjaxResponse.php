<?php

namespace Sarasa\Core;

class AjaxResponse
{

    protected $_responses;
    
    public function __construct()
    {
        $this->_responses = array();
    }

    public function assign($id, $value, $val = null)
    {
        if ($val && $value == 'innerHTML') {
            $value = $val;
        }

        $this->_responses[] = array('assign', $id, $value);
    }

    public function script($script)
    {
        $this->_responses[] = array('script', $script);
    }

    public function redirect($url)
    {
        $this->_responses[] = array('redirect', $url);
    }

    public function log($text)
    {
        $this->_responses[] = array('log', $text);
    }

    public function fadeOut($id)
    {
        $this->_responses[] = array('fadeout', $id);
    }

    public function append($id, $type, $html = null)
    {
        if (!$html) {
            $html = $type;
        }

        $this->_responses[] = array('append', $id, $html);
    }

    public function prepend($id, $type, $html = null)
    {
        if (!$html) {
            $html = $type;
        }
        $this->_responses[] = array('prepend', $id, $html);
    }

    public function alert($value)
    {
        $this->script('alert("'.$value.'");');
    }

    public function remove($value)
    {
        $this->script('$("#'.$value.'").remove();');
    }
    
    public function toJSON()
    {
        return json_encode($this->_responses);
    }
}
