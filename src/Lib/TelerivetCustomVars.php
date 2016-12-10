<?php
namespace Telerivet\Lib;

class TelerivetCustomVars implements \Iterator
{
    private $_dirty = array();
    private $_vars;

    public function __construct($vars)
    {
        $this->_vars = $vars;
    }

    public function all()
    {
        return $this->_vars;
    }

    public function getDirtyVariables()
    {
        return $this->_dirty;
    }

    public function clearDirtyVariables()
    {
        $this->_dirty = array();
    }

    public function rewind()
    {
        return reset($this->_vars);
    }

    public function current()
    {
        return current($this->_vars);
    }

    public function key()
    {
        return key($this->_vars);
    }

    public function next()
    {
        return next($this->_vars);
    }

    public function valid()
    {
        return key($this->_vars) !== null;
    }

    public function __isset($name)
    {
        return isset($this->_vars[$name]);
    }

    public function __unset($name)
    {
        unset($this->_vars[$name]);
    }

    public function get($name)
    {
        return isset($this->_vars[$name]) ? $this->_vars[$name] : null;
    }

    public function __get($name)
    {
        return $this->get($name);
    }

    public function set($name, $value)
    {
        $this->_vars[$name] = $value;
        $this->_dirty[$name] = $value;
    }

    public function __set($name, $value)
    {
        $this->set($name, $value);
    }
}
