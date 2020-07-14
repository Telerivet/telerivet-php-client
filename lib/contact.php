<?php
/**
    Telerivet_Contact
    
    Fields:
    
      - id (string, max 34 characters)
          * ID of the contact
          * Read-only
      
      - name
          * Name of the contact
          * Updatable via API
      
      - phone_number (string)
          * Phone number of the contact
          * Updatable via API
      
      - time_created (UNIX timestamp)
          * Time the contact was added in Telerivet
          * Read-only
      
      - time_updated (UNIX timestamp)
          * Time the contact was last updated in Telerivet
          * Read-only
      
      - send_blocked (bool)
          * True if Telerivet is blocked from sending messages to this contact
          * Updatable via API
      
      - conversation_status
          * Current status of the conversation with this contact
          * Allowed values: closed, active, handled
          * Updatable via API
      
      - last_message_time (UNIX timestamp)
          * Last time the contact sent or received a message (null if no messages have been sent
              or received)
          * Read-only
      
      - last_incoming_message_time (UNIX timestamp)
          * Last time a message was received from this contact
          * Read-only
      
      - last_outgoing_message_time (UNIX timestamp)
          * Last time a message was sent to this contact
          * Read-only
      
      - message_count (int)
          * Total number of non-deleted messages sent to or received from this contact
          * Read-only
      
      - incoming_message_count (int)
          * Number of messages received from this contact
          * Read-only
      
      - outgoing_message_count (int)
          * Number of messages sent to this contact
          * Read-only
      
      - last_message_id
          * ID of the last message sent to or received from this contact (null if no messages
              have been sent or received)
          * Read-only
      
      - default_route_id
          * ID of the phone or route that Telerivet will use by default to send messages to this
              contact (null if using project default route)
          * Updatable via API
      
      - group_ids (array of strings)
          * List of IDs of groups that this contact belongs to
          * Read-only
      
      - vars (associative array)
          * Custom variables stored for this contact
          * Updatable via API
      
      - project_id
          * ID of the project this contact belongs to
          * Read-only
 */
class Telerivet_Contact extends Telerivet_Entity
{    
    private $_group_ids_set = array();
    
    /**
        $contact->isInGroup($group)
        
        Returns true if this contact is in a particular group, false otherwise.
        
        Arguments:
          - $group (Telerivet_Group)
              * Required
          
        Returns:
            bool
     */    
    function isInGroup($group)
    {
        $this->load();
        return isset($this->_group_ids_set[$group->id]);
    }
    
    /**
        $contact->addToGroup($group)
        
        Adds this contact to a group.
        
        Arguments:
          - $group (Telerivet_Group)
              * Required
     */    
    function addToGroup($group)
    {
        $this->_api->doRequest("PUT", "{$group->getBaseApiPath()}/contacts/{$this->id}");               
        $this->_group_ids_set[$group->id] = true;        
    }
    
    /**
        $contact->removeFromGroup($group)
        
        Removes this contact from a group.
        
        Arguments:
          - $group (Telerivet_Group)
              * Required
     */    
    function removeFromGroup($group)
    {        
        $this->_api->doRequest("DELETE", "{$group->getBaseApiPath()}/contacts/{$this->id}");               
        unset($this->_group_ids_set[$group->id]);
    }
    
    /**
        $contact->queryMessages($options)
        
        Queries messages sent or received by this contact.
        
        Arguments:
          - $options (associative array)
            
            - direction
                * Filter messages by direction
                * Allowed values: incoming, outgoing
            
            - message_type
                * Filter messages by message_type
                * Allowed values: sms, mms, ussd, call, service
            
            - source
                * Filter messages by source
                * Allowed values: phone, provider, web, api, service, webhook, scheduled,
                    integration
            
            - starred (bool)
                * Filter messages by starred/unstarred
            
            - status
                * Filter messages by status
                * Allowed values: ignored, processing, received, sent, queued, failed,
                    failed_queued, cancelled, delivered, not_delivered
            
            - time_created[min] (UNIX timestamp)
                * Filter messages created on or after a particular time
            
            - time_created[max] (UNIX timestamp)
                * Filter messages created before a particular time
            
            - external_id
                * Filter messages by ID from an external provider
            
            - contact_id
                * ID of the contact who sent/received the message
            
            - phone_id
                * ID of the phone (basic route) that sent/received the message
            
            - broadcast_id
                * ID of the broadcast containing the message
            
            - scheduled_id
                * ID of the scheduled message that created this message
            
            - sort
                * Sort the results based on a field
                * Allowed values: default
                * Default: default
            
            - sort_dir
                * Sort the results in ascending or descending order
                * Allowed values: asc, desc
                * Default: asc
            
            - page_size (int)
                * Number of results returned per page (max 500)
                * Default: 50
            
            - offset (int)
                * Number of items to skip from beginning of result set
                * Default: 0
          
        Returns:
            Telerivet_APICursor (of Telerivet_Message)
    */
    function queryMessages($options = null)
    {
        return $this->_api->newApiCursor('Telerivet_Message', "{$this->getBaseApiPath()}/messages", $options);
    }

    /**
        $contact->queryGroups($options)
        
        Queries groups for which this contact is a member.
        
        Arguments:
          - $options (associative array)
            
            - name
                * Filter groups by name
                * Allowed modifiers: name[ne], name[prefix], name[not_prefix], name[gte], name[gt],
                    name[lt], name[lte]
            
            - dynamic (bool)
                * Filter groups by dynamic/non-dynamic
            
            - sort
                * Sort the results based on a field
                * Allowed values: default, name
                * Default: default
            
            - sort_dir
                * Sort the results in ascending or descending order
                * Allowed values: asc, desc
                * Default: asc
            
            - page_size (int)
                * Number of results returned per page (max 500)
                * Default: 50
            
            - offset (int)
                * Number of items to skip from beginning of result set
                * Default: 0
          
        Returns:
            Telerivet_APICursor (of Telerivet_Group)
    */
    function queryGroups($options = null)
    {
        return $this->_api->newApiCursor('Telerivet_Group', "{$this->getBaseApiPath()}/groups", $options);
    }

    /**
        $contact->queryScheduledMessages($options)
        
        Queries messages scheduled to this contact (not including messages scheduled to groups that
        this contact is a member of)
        
        Arguments:
          - $options (associative array)
            
            - message_type
                * Filter scheduled messages by message_type
                * Allowed values: sms, mms, ussd, call, service
            
            - time_created (UNIX timestamp)
                * Filter scheduled messages by time_created
                * Allowed modifiers: time_created[ne], time_created[min], time_created[max]
            
            - next_time (UNIX timestamp)
                * Filter scheduled messages by next_time
                * Allowed modifiers: next_time[exists], next_time[ne], next_time[min],
                    next_time[max]
            
            - sort
                * Sort the results based on a field
                * Allowed values: default, name
                * Default: default
            
            - sort_dir
                * Sort the results in ascending or descending order
                * Allowed values: asc, desc
                * Default: asc
            
            - page_size (int)
                * Number of results returned per page (max 500)
                * Default: 50
            
            - offset (int)
                * Number of items to skip from beginning of result set
                * Default: 0
          
        Returns:
            Telerivet_APICursor (of Telerivet_ScheduledMessage)
    */
    function queryScheduledMessages($options = null)
    {
        return $this->_api->newApiCursor('Telerivet_ScheduledMessage', "{$this->getBaseApiPath()}/scheduled", $options);
    }

    /**
        $contact->queryDataRows($options)
        
        Queries data rows associated with this contact (in any data table).
        
        Arguments:
          - $options (associative array)
            
            - time_created (UNIX timestamp)
                * Filter data rows by the time they were created
                * Allowed modifiers: time_created[ne], time_created[min], time_created[max]
            
            - sort
                * Sort the results based on a field
                * Allowed values: default
                * Default: default
            
            - sort_dir
                * Sort the results in ascending or descending order
                * Allowed values: asc, desc
                * Default: asc
            
            - page_size (int)
                * Number of results returned per page (max 500)
                * Default: 50
            
            - offset (int)
                * Number of items to skip from beginning of result set
                * Default: 0
          
        Returns:
            Telerivet_APICursor (of Telerivet_DataRow)
    */
    function queryDataRows($options = null)
    {
        return $this->_api->newApiCursor('Telerivet_DataRow', "{$this->getBaseApiPath()}/rows", $options);
    }

    /**
        $contact->queryServiceStates($options)
        
        Queries this contact's current states for any service
        
        Arguments:
          - $options (associative array)
            
            - id
                * Filter states by id
                * Allowed modifiers: id[ne], id[prefix], id[not_prefix], id[gte], id[gt], id[lt],
                    id[lte]
            
            - vars (associative array)
                * Filter states by value of a custom variable (e.g. vars[email], vars[foo], etc.)
                * Allowed modifiers: vars[foo][exists], vars[foo][ne], vars[foo][prefix],
                    vars[foo][not_prefix], vars[foo][gte], vars[foo][gt], vars[foo][lt], vars[foo][lte],
                    vars[foo][min], vars[foo][max]
            
            - sort
                * Sort the results based on a field
                * Allowed values: default
                * Default: default
            
            - sort_dir
                * Sort the results in ascending or descending order
                * Allowed values: asc, desc
                * Default: asc
            
            - page_size (int)
                * Number of results returned per page (max 500)
                * Default: 50
            
            - offset (int)
                * Number of items to skip from beginning of result set
                * Default: 0
          
        Returns:
            Telerivet_APICursor (of Telerivet_ContactServiceState)
    */
    function queryServiceStates($options = null)
    {
        return $this->_api->newApiCursor('Telerivet_ContactServiceState', "{$this->getBaseApiPath()}/states", $options);
    }

    /**
        $contact->save()
        
        Saves any fields or custom variables that have changed for this contact.
    */
    function save()
    {
        parent::save();
    }

    /**
        $contact->delete()
        
        Deletes this contact.
    */
    function delete()
    {
        $this->_api->doRequest("DELETE", "{$this->getBaseApiPath()}");
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
