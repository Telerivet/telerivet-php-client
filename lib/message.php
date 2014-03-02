<?php

class Telerivet_Message extends Telerivet_Entity
{
    private $_label_ids_set = array();

    function getBaseApiPath()
    {
        return "/projects/{$this->project_id}/messages/{$this->id}";
    }
    
    function delete()
    {        
        $this->_api->doRequest("DELETE", $this->getBaseApiPath());               
    }        
    
    function hasLabel($label)
    {
        $this->_loadData();
        return isset($this->_label_ids_set[$label->id]);
    }
    
    function addLabel($label)
    {
        $this->_api->doRequest("PUT", "{$label->getBaseApiPath()}/messages/{$this->id}");               
        $this->_label_ids_set[$label->id] = true;        
    }
    
    function removeLabel($label)
    {        
        $this->_api->doRequest("DELETE", "{$label->getBaseApiPath()}/messages/{$this->id}");               
        unset($this->_label_ids_set[$label->id]);
    }
    
    protected function _setData($data)
    {
        parent::_setData($data);
        
        if (isset($data['label_ids']) && is_array($data['label_ids']))
        {
            foreach ($data['label_ids'] as $label_id)
            {
                $this->_label_ids_set[$label_id] = true;
            }
        }
    }
}
