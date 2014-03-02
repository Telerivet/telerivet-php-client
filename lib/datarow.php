<?php

class Telerivet_DataRow extends Telerivet_Entity
{
    protected $_has_custom_vars = true;

    function delete()
    {        
        $this->_api->doRequest("DELETE", $this->getBaseApiPath());               
    }
    
    function getBaseApiPath()
    {
        return "/projects/{$this->project_id}/tables/{$this->table_id}/rows/{$this->id}";
    }
}
