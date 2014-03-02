<?php

class Telerivet_ScheduledMessage extends Telerivet_Entity
{
    function delete()
    {        
        $this->_api->doRequest("DELETE", $this->getBaseApiPath());               
    }            

    function getBaseApiPath()
    {
        return "/projects/{$this->project_id}/scheduled/{$this->id}";
    }
}
