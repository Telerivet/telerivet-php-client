<?php

class Telerivet_ContactServiceState extends Telerivet_Entity
{
    protected $_has_custom_vars = true;

    function reset()
    {
        return $this->_api->doRequest('DELETE', $this->getBaseApiPath());
    }

    function getBaseApiPath()
    {
        return "/projects/{$this->project_id}/services/{$this->service_id}/states/{$this->contact_id}";
    }    
}