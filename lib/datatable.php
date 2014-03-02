<?php

class Telerivet_DataTable extends Telerivet_Entity
{
    function queryRows($options = null)
    {
        return $this->_api->newApiCursor('Telerivet_DataRow', "{$this->getBaseApiPath()}/rows", $options);
    }

    function getRowById($id)
    {                                          
        return new Telerivet_DataRow($this->_api, array('id' => $id, 'table_id' => $this->id, 'project_id' => $this->project_id), false);
    }
    
    function createRow($options)
    {                                          
        $data = $this->_api->doRequest("POST", "{$this->getBaseApiPath()}/rows", array('name' => $name));
        return new Telerivet_DataRow($this->_api, $data);
    }
    
    function getBaseApiPath()
    {
        return "/projects/{$this->project_id}/tables/{$this->id}";
    }
}
