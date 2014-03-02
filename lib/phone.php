<?php

/**
    Represents a phone or gateway that you use to send/receive messages via Telerivet.
    
    Properties:
        id (string)
        name (string)
        phone_number (string)
        phone_type (string, "android","twilio","nexmo",etc.)
        time_created (UNIX timestamp)
        last_active_time (UNIX timestamp or null)
        vars (associative array of custom variables)
        project_id (string)            
    
    Example Usage:
    -------------
    
    $PROJECT_ID = 'YOUR_PROJECT_ID'; // from https://telerivet.com/dashboard/api
    $PHONE_ID = 'YOUR_PHONE_ID'; 
    
    $project = $telerivet->getProjectById($PROJECT_ID); 
    $phone = $project->getPhoneById($PHONE_ID); 
    
    echo $phone->queryMessages(array(
        'status' => 'queued'
    ))->count();   
    
 */
class Telerivet_Phone extends Telerivet_Entity
{
    protected $_has_custom_vars = true;

    /**
        Queries messages sent or received by this phone.
     
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

    function getBaseApiPath()
    {
        return "/projects/{$this->project_id}/phones/{$this->id}";
    }
}
