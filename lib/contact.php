<?php

class Telerivet_Contact extends Telerivet_Entity
{
    protected $_has_custom_vars = true;
    
    private $_group_ids_set = array();

    function queryMessages($options = null)
    {
        return $this->_api->newApiCursor('Telerivet_Message', "{$this->getBaseApiPath()}/messages", $options);
    }
    
    function queryGroups($options = null)
    {
        return $this->_api->newApiCursor('Telerivet_Group', "{$this->getBaseApiPath()}/groups", $options);
    }

    function queryScheduledMessages($options = null)
    {
        return $this->_api->newApiCursor('Telerivet_ScheduledMessage', "{$this->getBaseApiPath()}/scheduled", $options);
    }
    
    function isInGroup($group)
    {
        $this->_loadData();
        return isset($this->_group_ids_set[$group->id]);
    }
    
    function addToGroup($group)
    {
        $this->_api->doRequest("PUT", "{$group->getBaseApiPath()}/contacts/{$this->id}");               
        $this->_group_ids_set[$group->id] = true;        
    }
    
    function removeFromGroup($group)
    {        
        $this->_api->doRequest("DELETE", "{$group->getBaseApiPath()}/contacts/{$this->id}");               
        unset($this->_group_ids_set[$group->id]);
    }
    
    function delete()
    {        
        $this->_api->doRequest("DELETE", $this->getBaseApiPath());               
    }    
    
    function getBaseApiPath()
    {
        return "/projects/{$this->project_id}/contacts/{$this->id}";
    }
    
    protected function _setData($data)
    {
        parent::_setData($data);
        
        if (isset($data['group_ids']) && is_array($data['group_ids']))
        {
            foreach ($data['group_ids'] as $group_id)
            {
                $this->_group_ids_set[$group_id] = true;
            }
        }
    }
}
