<?php

/**
    Represents a label used to organize messages within Telerivet.
    
    Properties:
        id (string)
        name (string)
        time_created (UNIX timestamp)        
        project_id (string)            
    
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
    function getBaseApiPath()
    {
        return "/projects/{$this->project_id}/labels/{$this->id}";
    }

        
    /**
        Queries messages with this label.
     
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
        Deletes this label (Note: no messages are deleted.)
     */    
    function delete()
    {        
        $this->_api->doRequest("DELETE", $this->getBaseApiPath());               
    }    
}
