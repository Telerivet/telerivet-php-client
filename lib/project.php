<?php

/**
    Represents a Telerivet project and methods for accessing its child entities, including contacts,
    messages, scheduled messages, groups, labels, phones, and data tables. 
    
    Provides methods for sending and scheduling messages.

    Properties:
        id (string)
        name (string)
        timezone_id (string, see https://tools.ietf.org/html/rfc2445#section-4.3.10)
        vars (associative array of custom variables)        
    
    Example Usage:
    -------------
    
    $PROJECT_ID = 'YOUR_PROJECT_ID'; // from https://telerivet.com/dashboard/api
    
    $project = $telerivet->getProjectById($PROJECT_ID); 
    
    $project->sendMessage(array(
        'to_number' => '555-0001',
        'content' => 'Hello world!'
    ));   
    
 */
class Telerivet_Project extends Telerivet_Entity
{
    protected $_has_custom_vars = true;

    /*
        Sends one message (SMS or USSD request).
     
        Arguments:
            $options (associative array)
                - content (string)
                - to_number OR contact_id (string)               
                - phone_id or route_id (string, uses project/contact default phone if omitted)                
                - status_url (callback webhook URL)                
                - status_secret (passed as 'secret' POST parameter to status_url)                
                - is_template (bool, default false; set true to evaluate variables like [[contact.name]] in message content)                
                - message_type ("sms" or "ussd"; default "sms")                                
                         
        Returns:
            Telerivet_APICursor (of Telerivet_Phone)
     */
    function sendMessage($options)
    {
        return $this->_api->doRequest('POST', $this->getBaseApiPath() . '/messages/send', $options);        
    }
    
    /*
        Sends an SMS message (optionally with mail-merge templates) to a group or list of up to 500 phone numbers
     
        Arguments:
            $options (associative array)
                - content (string)
                - group_id (string) OR to_numbers (array of up to 500 strings)
                - phone_id or route_id (string, uses project/contact default phone if omitted)                
                - status_url (callback webhook URL)                                
                - status_secret (passed as 'secret' POST parameter to status_url)                
                - exclude_contact_id (string -- optionally excludes one contact from receiving the message only when group_id param is set)
                - is_template (bool, default false; set true to evaluate variables like [[contact.name]] in message content)                                              
                         
        Returns:
            (associative array)
                - count_queued (int)
     */
    function sendMessages($options)
    {
        return $this->_api->doRequest('POST', $this->getBaseApiPath() . '/messages/send_batch', $options);        
    }
    
    /*
        Schedules an SMS message to a group or single contact
     
        Arguments:
            $options (associative array)
                - content (string)
                - group_id OR to_number (string)
                - start_time (UNIX timestamp, when the message will be sent (or first sent for recurring messages))
                    OR start_time_offset (number of seconds until the message is sent)
                - rrule (string; default "COUNT=1" (no recurrence); see https://tools.ietf.org/html/rfc2445#section-4.3.10)
                - phone_id or route_id (string, uses project/contact default phone if omitted)                
                - message_type ("sms" or "ussd"; default "sms")                                
                - is_template (bool, default false; set true to evaluate variables like [[contact.name]] in message content)                                                              
                - timezone_id (string, see http://en.wikipedia.org/wiki/List_of_tz_database_time_zones, uses project default if omitted)
                - end_time (UNIX timestamp, after which a recurring message will stop)
                    OR end_time_offset (number of seconds until recurring message will stop)
                
        Returns:
            Telerivet_ScheduledMessage
     */
    function scheduleMessage($options)
    {
        return $this->_api->doRequest('POST', $this->getBaseApiPath() . '/scheduled', $options);        
    }

    /**
        Gets OR creates and possibly updates a contact by name or phone number.
        
        If a phone number is provided, Telerivet will search for an existing contact
        with that phone number (including suffix matches to allow finding contacts with phone numbers in a different format).
        
        If a phone number is not provided but a name is provided, Telerivet will search 
        for a contact with that exact name (case insensitive).
        
        If no existing contact is found, a new contact will be created.
        
        Then that contact will be updated with any parameters provided (name, phone_number, and vars).
        
        Examples:
        
        // get or create a contact by phone number (don't update anything if it already exists)
        $contact = $project->getOrCreateContact(array('phone_number' => '555-1231'));
        
        // get or create a contact by name (don't update anything if it already exists)
        $contact = $project->getOrCreateContact(array('name' => 'John Smith'));
        
        // get or create a contact by phone number, then update name and custom variable
        $contact = $project->getOrCreateContact(array(
            'phone_number' => '555-0312', 
            'name' => 'John Smith', 
            'vars' => array('birthdate' => '1924-10-01')
        ));
              
        Arguments:
            $options
                - phone_number (string)
                - name (string)
                - vars (associative array with custom contact information)                
         
        Returns:
            Telerivet_Contact
     */   
    function getOrCreateContact($options)
    {                                       
        $data = $this->_api->doRequest("POST", "{$this->getBaseApiPath()}/contacts", $options);
        return new Telerivet_Group($this->_api, $data);
    }    
        
    /**     
        Queries contacts within this project.
     
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
        Gets a contact by ID.
        
        Note: This does not make any API requests until you access a property of the contact.
     
        Returns:
            Telerivet_Contact
     */   
    function getContactById($id)
    {
        return new Telerivet_Contact($this->_api, array('id' => $id, 'project_id' => $this->id), false);
    }
    
    
    /**     
        Queries phones within the current project.
     
        Arguments:
            $options (associative array)
                - name (string)
                - name_prefix (string)
                - phone_number (string)
                - phone_number_prefix (string)
                - last_active_time_min (UNIX timestamp)
                - last_active_time_max (UNIX timestamp)
                - sort ("default")
                - sort_dir ("asc", "desc")
                - page_size (int)
                         
        Returns:
            Telerivet_APICursor (of Telerivet_Phone)
     */
    function queryPhones($options = null)
    {
        return $this->_api->newApiCursor('Telerivet_Phone', "{$this->getBaseApiPath()}/phones", $options);
    }

    /**
        Gets a phone by ID.
        
        Note: This does not make any API requests until you access a property of the phone.
     
        Arguments:
            $id (string -- see https://telerivet.com/dashboard/api) 
         
        Returns:
            Telerivet_Phone
     */   
    function getPhoneById($id)
    {
        return new Telerivet_Phone($this->_api, array('id' => $id, 'project_id' => $this->id), false);
    }               
        
    /**     
        Queries messages within this project.
     
        Arguments:
            $options (associative array)
                - message_type ("sms","mms","ussd","call")
                - direction ("incoming","outgoing")
                - source ("api","web","scheduled","webhook","service","phone","provider")
                - starred (bool)
                - status ("queued","sent","failed","failed_queued","delivered","not_delivered","received","processing","ignored")
                - time_created_min (UNIX timestamp)
                - time_created_max (UNIX timestamp)
                - contact_id (string)
                - phone_id (string)
                - sort ("default")
                - sort_dir ("asc", "desc")
                - page_size (int)
                         
        Returns:
            Telerivet_APICursor (of Telerivet_Message)
     */
    function queryMessages($options = null)
    {
        return $this->_api->newApiCursor('Telerivet_Message', "{$this->getBaseApiPath()}/messages", $options);
    }
    
    /**
        Gets a message by ID.
        
        Note: This does not make any API requests until you access a property of the message.
     
        Arguments:
            $id (string)
         
        Returns:
            Telerivet_Message
     */   
    function getMessageById($id)
    {
        return new Telerivet_Message($this->_api, array('id' => $id, 'project_id' => $this->id), false);
    }

    /**     
        Queries groups within this project.
     
        Arguments:
            $options (associative array)
                - name (string)
                - name_prefix (string)
                - sort ("default")
                - sort_dir ("asc", "desc")
                - page_size (int)
                         
        Returns:
            Telerivet_APICursor (of Telerivet_Phone)
     */
    function queryGroups($options = null)
    {
        return $this->_api->newApiCursor('Telerivet_Group', "{$this->getBaseApiPath()}/groups", $options);
    }

    /**
        Gets OR creates a group by name.
              
        Arguments:
            $name (string)
                
        Returns:
            Telerivet_Group
     */       
    function getOrCreateGroup($name)
    {                                          
        $data = $this->_api->doRequest("POST", "{$this->getBaseApiPath()}/groups", array('name' => $name));
        return new Telerivet_Group($this->_api, $data);
    }    
    
    /**
        Gets a group by ID.
        
        Note: This does not make any API requests until you access a property of the group.
     
        Arguments:
            $id (string)
         
        Returns:
            Telerivet_Group
     */       
    function getGroupById($id)
    {
        return new Telerivet_Group($this->_api, array('id' => $id, 'project_id' => $this->id), false);
    }        
        
    /**     
        Queries labels within this project.
     
        Arguments:
            $options (associative array)
                - name (string)
                - name_prefix (string)
                - sort ("default")
                - sort_dir ("asc", "desc")
                - page_size (int)
                         
        Returns:
            Telerivet_APICursor (of Telerivet_Label)
     */    
    function queryLabels($options = null)
    {
        return $this->_api->newApiCursor('Telerivet_Label', "{$this->getBaseApiPath()}/labels", $options);
    }
        
    /**
        Gets OR creates a label by name.
              
        Arguments:
            $name (string)
                
        Returns:
            Telerivet_Label
     */       
    function getOrCreateLabel($name)
    {                                       
        $data = $this->_api->doRequest("POST", "{$this->getBaseApiPath()}/labels", array('name' => $name));
        return new Telerivet_Label($this->_api, $data);
    }        
    
    /**
        Gets a label by ID.
        
        Note: This does not make any API requests until you access a property of the label.
     
        Arguments:
            $id (string)
         
        Returns:
            Telerivet_Label
     */   
    function getLabelById($id)
    {
        return new Telerivet_Label($this->_api, array('id' => $id, 'project_id' => $this->id), false);
    }
    
    
    /**     
        Queries data tables within this project.
     
        Arguments:
            $options (associative array)
                - name (string)
                - name_prefix (string)
                - sort ("default")
                - sort_dir ("asc", "desc")
                - page_size (int)
                         
        Returns:
            Telerivet_APICursor (of Telerivet_DataTable)
     */
    function queryDataTables($options = null)
    {
        return $this->_api->newApiCursor('Telerivet_DataTable', "{$this->getBaseApiPath()}/tables", $options);
    }
    
    /**
        Gets a data table by ID.
        
        Note: This does not make any API requests until you access a property of the label.
     
        Arguments:
            $id (string)
         
        Returns:
            Telerivet_DataTable
     */   
    function getDataTableById($id)
    {
        return new Telerivet_DataTable($this->_api, array('id' => $id, 'project_id' => $this->id), false);
    }
    
    /**     
        Queries scheduled messages within this project.
     
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
        Gets a scheduled message by ID.
        
        Note: This does not make any API requests until you access a property of the scheduled message.
     
        Arguments:
            $id (string)
         
        Returns:
            Telerivet_ScheduledMessage
     */       
    function getScheduledMessageById($id)
    {
        return new Telerivet_ScheduledMessage($this->_api, array('id' => $id, 'project_id' => $this->id), false);
    }        
    
    /**     
        Queries services within this project.
     
        Arguments:
            $options (associative array)
                - event ("incoming_message","scheduled","manual")
                - name
                - active
                - priority               
                - page_size (int)
                         
        Returns:
            Telerivet_APICursor (of Telerivet_ScheduledMessage)
     */
    function queryServices($options = null)
    {
        return $this->_api->newApiCursor('Telerivet_Service', "{$this->getBaseApiPath()}/services", $options);
    }    
    
    /**
        Gets a service message by ID.
        
        Note: This does not make any API requests until you access a property of the scheduled message.
     
        Arguments:
            $id (string)
         
        Returns:
            Telerivet_Service
     */       
    function getServiceById($id)
    {
        return new Telerivet_Service($this->_api, array('id' => $id, 'project_id' => $this->id), false);
    }            
    
    function getBaseApiPath()
    {
        return "/projects/{$this->id}";
    }
}