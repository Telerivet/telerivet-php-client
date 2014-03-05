<?php

class Telerivet_Service extends Telerivet_Entity
{
    function queryContactStates($options = null)
    {
        return $this->_api->newApiCursor('Telerivet_ContactServiceState', "{$this->getBaseApiPath()}/states", $options);
    }

    function getContactState($contact)
    {
        try 
        {
            return new Telerivet_ContactServiceState($this->_api, $this->_api->doRequest('GET', $this->getBaseApiPath() . '/states/' . $contact->id));
        }
        catch (Telerivet_NotFoundException $ex)
        {
            return null;
        }
    }
    
    function setContactState($contact, $options)
    {
        return new Telerivet_ContactServiceState($this->_api, $this->_api->doRequest('POST', $this->getBaseApiPath() . '/states/' . $contact->id, $options));
    }

    function resetContactState($contact)
    {
        return new Telerivet_ContactServiceState($this->_api, $this->_api->doRequest('DELETE', $this->getBaseApiPath() . '/states/' . $contact->id));        
    }

    function getBaseApiPath()
    {
        return "/projects/{$this->project_id}/services/{$this->id}";
    }
}
