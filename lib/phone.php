<?php

/**
    Telerivet_Phone
    
    Represents a basic route (i.e. a phone or gateway) that you use to send/receive messages via
    Telerivet.
    
    Basic Routes were formerly referred to as "Phones" within Telerivet. API
    methods, parameters, and properties related to Basic Routes continue to use the term "Phone"
    to maintain backwards compatibility.
    
    Fields:
    
      - id (string, max 34 characters)
          * ID of the phone
          * Read-only
      
      - name
          * Name of the phone
          * Updatable via API
      
      - phone_number (string)
          * Phone number or sender ID
          * Updatable via API
      
      - phone_type
          * Type of this phone/route (e.g. android, twilio, nexmo, etc)
          * Read-only
      
      - country
          * 2-letter country code (ISO 3166-1 alpha-2) where phone is from
          * Read-only
      
      - send_paused (bool)
          * True if sending messages is currently paused, false if the phone can currently send
              messages
          * Updatable via API
      
      - time_created (UNIX timestamp)
          * Time the phone was created in Telerivet
          * Read-only
      
      - last_active_time (UNIX timestamp)
          * Approximate time this phone last connected to Telerivet
          * Read-only
      
      - vars (associative array)
          * Custom variables stored for this phone. Variable names may be up to 32 characters in
              length and can contain the characters a-z, A-Z, 0-9, and _.
              Values may be strings, numbers, or boolean (true/false).
              String values may be up to 4096 bytes in length when encoded as UTF-8.
              Up to 100 variables are supported per object.
              Setting a variable to null will delete the variable.
          * Updatable via API
      
      - project_id
          * ID of the project this phone belongs to
          * Read-only
      
      - battery (int)
          * Current battery level, on a scale from 0 to 100, as of the last time the phone
              connected to Telerivet (only present for Android phones)
          * Read-only
      
      - charging (bool)
          * True if the phone is currently charging, false if it is running on battery, as of
              the last time it connected to Telerivet (only present for Android phones)
          * Read-only
      
      - internet_type
          * String describing the current type of internet connectivity for an Android phone,
              for example WIFI or MOBILE (only present for Android phones)
          * Read-only
      
      - app_version
          * Currently installed version of Telerivet Android app (only present for Android
              phones)
          * Read-only
      
      - android_sdk (int)
          * Android SDK level, indicating the approximate version of the Android OS installed on
              this phone; see [list of Android SDK
              levels](http://developer.android.com/guide/topics/manifest/uses-sdk-element.html#ApiLevels)
              (only present for Android phones)
          * Read-only
      
      - mccmnc
          * Code indicating the Android phone's current country (MCC) and mobile network
              operator (MNC); see [Mobile country code Wikipedia
              article](https://en.wikipedia.org/wiki/Mobile_country_code) (only present for Android
              phones). Note this is a string containing numeric digits, not an integer.
          * Read-only
      
      - manufacturer
          * Android phone manufacturer (only present for Android phones)
          * Read-only
      
      - model
          * Android phone model (only present for Android phones)
          * Read-only
      
      - send_limit (int)
          * Maximum number of SMS messages per hour that can be sent by this Android phone. To
              increase this limit, install additional SMS expansion packs in the Telerivet Gateway
              app. (only present for Android phones)
          * Read-only
    
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
    /**
        $phone->queryMessages($options)
        
        Queries messages sent or received by this basic route.
        
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
        $phone->save()
        
        Saves any fields or custom variables that have changed for this basic route.
    */
    function save()
    {
        parent::save();
    }

    function getBaseApiPath()
    {
        return "/projects/{$this->project_id}/phones/{$this->id}";
    }
}
