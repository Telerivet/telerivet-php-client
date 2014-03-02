<?php

/**
    Represents a group used to organize contacts within Telerivet.
    
    Properties:
        id (string)
        name (string)
        num_members (int)
        time_created (UNIX timestamp)        
        project_id (string)            
    
    Example Usage:
    -------------
    
    $PROJECT_ID = 'YOUR_PROJECT_ID'; // from https://telerivet.com/dashboard/api   
    
    $project = $telerivet->getProjectById($PROJECT_ID); 
    
    $group = $project->getOrCreateGroup("Subscribers");
    
    echo $group->num_members;
    
    $contact = $project->getOrCreateContact(array('phone_number' => '555-0123'));
    $contact->addToGroup($group);
    
 */
class Telerivet_Group extends Telerivet_Entity
{
    function getBaseApiPath()
    {
        return "/projects/{$this->project_id}/groups/{$this->id}";
    }

    /**     
        Queries contacts within this group.
        
        Arguments:
            $options (associative array)
                - name (string)
                - name_prefix (string)
                - phone_number (string)
                - phone_number_prefix (string)
                - time_created_min (UNIX timestamp)
                - time_created_max (UNIX timestamp)
                - last_message_time_min (UNIX timestamp)
                - last_message_time_max (UNIX timestamp)
                - last_message_time_exists (bool)
                - vars (associative array where keys are custom variable name, or custom variable name followed by "_prefix", "_min", or "_max")
                - sort ("default","name","phone_number","last_message_time")
                - sort_dir ("asc", "desc")
                - page_size (int)
                         
        Returns:
            Telerivet_APICursor (of Telerivet_Contact)
     */    
    function queryContacts($options = null)
    {
        return $this->_api->newApiCursor('Telerivet_Contact', "{$this->getBaseApiPath()}/contacts", $options);
    }
    
    /**     
        Queries scheduled messages to this group.
     
        Arguments:
            $options (associative array)
                - message_type ("sms","mms","ussd","call")
                - time_created_min (UNIX timestamp)
                - time_created_max (UNIX timestamp)
                - next_time_min (UNIX timestamp)
                - next_time_max (UNIX timestamp)
                - next_time_exists (bool)
                - sort ("default", "next_time")
                - sort_dir ("asc", "desc")
                - page_size (int)
                         
        Returns:
            Telerivet_APICursor (of Telerivet_ScheduledMessage)
     */    
    function queryScheduledMessages($options = null)
    {
        return $this->_api->newApiCursor('Telerivet_ScheduledMessage', "{$this->getBaseApiPath()}/scheduled", $options);
    }    
    
    /**
        Deletes this group (Note: no contacts are deleted.)
     */    
    function delete()
    {        
        $this->_api->doRequest("DELETE", $this->getBaseApiPath());               
    }            
}
