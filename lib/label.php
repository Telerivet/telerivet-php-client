<?php

/**
    Telerivet_Label
    
    Represents a label used to organize messages within Telerivet.
    
    Fields:
    
      - id (string, max 34 characters)
          * ID of the label
          * Read-only
      
      - name
          * Name of the label
          * Updatable via API
      
      - time_created (UNIX timestamp)
          * Time the label was created in Telerivet
          * Read-only
      
      - vars (associative array)
          * Custom variables stored for this label. Variable names may be up to 32 characters in
              length and can contain the characters a-z, A-Z, 0-9, and _.
              Values may be strings, numbers, or boolean (true/false).
              String values may be up to 4096 bytes in length when encoded as UTF-8.
              Up to 100 variables are supported per object.
              Setting a variable to null will delete the variable.
          * Updatable via API
      
      - project_id
          * ID of the project this label belongs to
          * Read-only
    
    Example Usage:
    -------------
    
    $PROJECT_ID = 'YOUR_PROJECT_ID'; // from https://telerivet.com/dashboard/api   
    
    $project = $telerivet->getProjectById($PROJECT_ID); 
    
    $label = $project->getOrCreateLabel("Important");
    
    echo $label->queryMessages()->count();   
    
    $last_message = $project->queryMessages(array('sort_dir' => 'desc'))->next();    
    $last_message->addLabel($label);
    
 */
class Telerivet_Label extends Telerivet_Entity
{
    /**
        $label->queryMessages($options)
        
        Queries messages with the given label.
        
        Arguments:
          - $options (associative array)
            
            - direction
                * Filter messages by direction
                * Allowed values: incoming, outgoing
            
            - message_type
                * Filter messages by message_type
                * Allowed values: sms, mms, ussd, ussd_session, call, chat, service
            
            - source
                * Filter messages by source
                * Allowed values: phone, provider, web, api, service, webhook, scheduled,
                    integration
            
            - starred (bool)
                * Filter messages by starred/unstarred
            
            - status
                * Filter messages by status
                * Allowed values: ignored, processing, received, sent, queued, failed,
                    failed_queued, cancelled, delivered, not_delivered, read
            
            - time_created[min] (UNIX timestamp)
                * Filter messages created on or after a particular time
            
            - time_created[max] (UNIX timestamp)
                * Filter messages created before a particular time
            
            - external_id
                * Filter messages by ID from an external provider
                * Allowed modifiers: external_id[ne], external_id[exists]
            
            - contact_id
                * ID of the contact who sent/received the message
                * Allowed modifiers: contact_id[ne], contact_id[exists]
            
            - phone_id
                * ID of the phone (basic route) that sent/received the message
            
            - broadcast_id
                * ID of the broadcast containing the message
                * Allowed modifiers: broadcast_id[ne], broadcast_id[exists]
            
            - scheduled_id
                * ID of the scheduled message that created this message
                * Allowed modifiers: scheduled_id[ne], scheduled_id[exists]
            
            - group_id
                * Filter messages sent or received by contacts in a particular group. The group must
                    be a normal group, not a dynamic group.
            
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
        $label->save()
        
        Saves any fields that have changed for the label.
    */
    function save()
    {
        parent::save();
    }

    /**
        $label->delete()
        
        Deletes the given label (Note: no messages are deleted.)
    */
    function delete()
    {
        $this->_api->doRequest("DELETE", "{$this->getBaseApiPath()}");
    }

    function getBaseApiPath()
    {
        return "/projects/{$this->project_id}/labels/{$this->id}";
    }
}
