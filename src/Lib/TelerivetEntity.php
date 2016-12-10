<?php
namespace Telerivet\Lib;

/*
Base class for all entities returned by the Telerivet API, including projects,
contacts, messages, groups, labels, scheduled messages, data tables, and data rows.
 */

abstract class TelerivetEntity
{
    protected $_is_loaded;
    protected $_data;
    protected $_api;
    protected $_vars;

    protected $_dirty = array();

    public function __construct($api, $data, $is_loaded = true)
    {
        $this->_api = $api;
        $this->_setData($data);
        $this->_is_loaded = $is_loaded;
    }

    protected function _setData($data)
    {
        $this->_data = $data;
        $this->_vars = new TelerivetCustomVars(isset($data['vars']) ? $data['vars'] : array());
    }

    public function load()
    {
        if (!$this->_is_loaded) {
            $this->_is_loaded = true;
            $old_dirty_vars = $this->_vars->getDirtyVariables();

            $this->_setData($this->_api->doRequest('GET', $this->getBaseApiPath()));

            foreach ($old_dirty_vars as $name => $value) {
                $this->_vars->set($name, $value);
            }

            foreach ($this->_dirty as $name => $value) {
                $this->_data[$name] = $value;
            }
        }
        return $this;
    }

    public function __get($name)
    {
        if ($name == 'vars') {
            $this->load();
            return $this->_vars;
        }

        $data = $this->_data;
        if (isset($data[$name])) {
            return $data[$name];
        } else if ($this->_is_loaded || array_key_exists($name, $data)) {
            return null;
        }

        $this->load();
        $data = $this->_data;

        return isset($data[$name]) ? $data[$name] : null;
    }

    public function __set($name, $value)
    {
        $this->_data[$name] = $value;
        $this->_dirty[$name] = $value;
    }

    /**
     * Saves any updated properties to Telerivet.
     */
    public function save()
    {
        $dirty_props = $this->_dirty;

        if ($this->_vars) {
            $dirty_vars = $this->_vars->getDirtyVariables();
            if ($dirty_vars) {
                $dirty_props['vars'] = $dirty_vars;
            }
        }

        $this->_api->doRequest('POST', $this->getBaseApiPath(), $dirty_props);
        $this->_dirty = array();

        if ($this->_vars) {
            $this->_vars->clearDirtyVariables();
        }
    }

    public function __toString()
    {
        $res = get_class($this);
        if (!$this->_is_loaded) {
            $res .= " (not loaded)";
        }
        $res .= " JSON: " . json_encode($this->_data);

        return $res;
    }

    abstract public function getBaseApiPath();
}
