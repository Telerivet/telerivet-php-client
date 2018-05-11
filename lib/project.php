<?php

/**
    Telerivet_Project
    
    Represents a Telerivet project.
    
    Provides methods for sending and scheduling messages, as well as
    accessing, creating and updating a variety of entities, including contacts, messages,
    scheduled messages, groups, labels, phones, services, and data tables.
    
    Fields:
    
      - id (string, max 34 characters)
          * ID of the project
          * Read-only
      
      - name
          * Name of the project
          * Updatable via API
      
      - timezone_id
          * Default TZ database timezone ID; see
              <http://en.wikipedia.org/wiki/List_of_tz_database_time_zones>
          * Read-only
      
      - url_slug
          * Unique string used as a component of the project's URL in the Telerivet web app
          * Read-only
      
      - vars (associative array)
          * Custom variables stored for this project
          * Updatable via API
      
      - organization_id (string, max 34 characters)
          * ID of the organization this project belongs to
          * Read-only
    Example Usage:
    -------------
    
    $PROJECT_ID = 'YOUR_PROJECT_ID'; // from https://telerivet.com/dashboard/api
    
    $project = $telerivet->initProjectById($PROJECT_ID); 
    
    $project->sendMessage(array(
        'to_number' => '555-0001',
        'content' => 'Hello world!'
    ));   
    
 */
class Telerivet_Project extends Telerivet_Entity
{
    /**
        $project->sendMessage($options)
        
        Sends one message (SMS, voice call, or USSD request).
        
        Arguments:
          - $options (associative array)
              * Required
            
            - message_type
                * Type of message to send
                * Allowed values: sms, ussd, call
                * Default: sms
            
            - content
                * Content of the message to send (if `message_type` is `call`, the text will be
                    spoken during a text-to-speech call)
                * Required if sending SMS message
            
            - to_number (string)
                * Phone number to send the message to
                * Required if contact_id not set
            
            - contact_id
                * ID of the contact to send the message to
                * Required if to_number not set
            
            - route_id
                * ID of the phone or route to send the message from
                * Default: default sender phone ID for your project
            
            - service_id
                * Service that defines the call flow of the voice call (when `message_type` is
                    `call`)
            
            - audio_url
                * The URL of an MP3 file to play when the contact answers the call (when
                    `message_type` is `call`).
                    
                    If `audio_url` is provided, the text-to-speech voice is not used to say
                    `content`, although you can optionally use `content` to indicate the script for the
                    audio.
                    
                    For best results, use an MP3 file containing only speech. Music is not
                    recommended because the audio quality will be low when played over a phone line.
            
            - tts_lang
                * The language of the text-to-speech voice (when `message_type` is `call`)
                * Allowed values: en-US, en-GB, en-GB-WLS, en-AU, en-IN, da-DK, nl-NL, fr-FR, fr-CA,
                    de-DE, is-IS, it-IT, pl-PL, pt-BR, pt-PT, ru-RU, es-ES, es-US, sv-SE
                * Default: en-US
            
            - tts_voice
                * The name of the text-to-speech voice (when message_type=call)
                * Allowed values: female, male
                * Default: female
            
            - status_url
                * Webhook callback URL to be notified when message status changes
            
            - status_secret
                * POST parameter 'secret' passed to status_url
            
            - is_template (bool)
                * Set to true to evaluate variables like [[contact.name]] in message content. [(See
                    available variables)](#variables)
                * Default: false
            
            - label_ids (array)
                * List of IDs of labels to add to this message
            
            - vars (associative array)
                * Custom variables to store with the message
            
            - priority (int)
                * Priority of the message (currently only observed for Android phones). Telerivet
                    will attempt to send messages with higher priority numbers first (for example, so
                    you can prioritize an auto-reply ahead of a bulk message to a large group).
                * Default: 1
            
            - user_id
                * ID of the Telerivet user account that sent the message (use
                    [project.getUsers](#Project.getUsers) to look up user IDs). In order to use this
                    parameter, the user account associated with the API key must have administrator
                    permissions for the project, and the user account associated with the user_id
                    parameter must have access to the project.
                * Default: User account associated with the API key
          
        Returns:
            Telerivet_Message
     */
    function sendMessage($options)
    {
        return new Telerivet_Message($this->_api, $this->_api->doRequest('POST', $this->getBaseApiPath() . '/messages/send', $options));
    }
    
    /**
        $project->scheduleMessage($options)
        
        Schedules a message to a group or single contact. Note that Telerivet only sends scheduled
        messages approximately once every 15 seconds, so it is not possible to control the exact
        second at which a scheduled message is sent.
        
        Arguments:
          - $options (associative array)
              * Required
            
            - message_type
                * Type of message to send
                * Allowed values: sms, ussd, call
                * Default: sms
            
            - content
                * Content of the message to schedule
                * Required if sending SMS message
            
            - group_id
                * ID of the group to send the message to
                * Required if to_number not set
            
            - to_number (string)
                * Phone number to send the message to
                * Required if group_id not set
            
            - start_time (UNIX timestamp)
                * The time that the message will be sent (or first sent for recurring messages)
                * Required if start_time_offset not set
            
            - start_time_offset (int)
                * Number of seconds from now until the message is sent
                * Required if start_time not set
            
            - rrule
                * A recurrence rule describing the how the schedule repeats, e.g. 'FREQ=MONTHLY' or
                    'FREQ=WEEKLY;INTERVAL=2'; see <https://tools.ietf.org/html/rfc2445#section-4.3.10>.
                    (UNTIL is ignored; use end_time parameter instead).
                * Default: COUNT=1 (one-time scheduled message, does not repeat)
            
            - route_id
                * ID of the phone or route to send the message from
                * Default: default sender phone ID
            
            - service_id
                * Service that defines the call flow of the voice call (when `message_type` is
                    `call`)
            
            - audio_url
                * The URL of an MP3 file to play when the contact answers the call (when
                    `message_type` is `call`).
                    
                    If `audio_url` is provided, the text-to-speech voice is not used to say
                    `content`, although you can optionally use `content` to indicate the script for the
                    audio.
                    
                    For best results, use an MP3 file containing only speech. Music is not
                    recommended because the audio quality will be low when played over a phone line.
            
            - tts_lang
                * The language of the text-to-speech voice (when `message_type` is `call`)
                * Allowed values: en-US, en-GB, en-GB-WLS, en-AU, en-IN, da-DK, nl-NL, fr-FR, fr-CA,
                    de-DE, is-IS, it-IT, pl-PL, pt-BR, pt-PT, ru-RU, es-ES, es-US, sv-SE
                * Default: en-US
            
            - tts_voice
                * The name of the text-to-speech voice (when message_type=call)
                * Allowed values: female, male
                * Default: female
            
            - is_template (bool)
                * Set to true to evaluate variables like [[contact.name]] in message content
                * Default: false
            
            - label_ids (array)
                * Array of IDs of labels to add to the sent messages (maximum 5)
            
            - timezone_id
                * TZ database timezone ID; see
                    <http://en.wikipedia.org/wiki/List_of_tz_database_time_zones>
                * Default: project default timezone
            
            - end_time (UNIX timestamp)
                * Time after which a recurring message will stop (not applicable to non-recurring
                    scheduled messages)
            
            - end_time_offset (int)
                * Number of seconds from now until the recurring message will stop
          
        Returns:
            Telerivet_ScheduledMessage
     */
    function scheduleMessage($options)
    {
        return new Telerivet_ScheduledMessage($this->_api, $this->_api->doRequest('POST', $this->getBaseApiPath() . '/scheduled', $options));
    }

    /**
        $project->getOrCreateContact($options)
        
        Retrieves OR creates and possibly updates a contact by name or phone number.
        
        If a phone number is provided, by default, Telerivet will search for
        an existing contact with that phone number (including suffix matches to allow finding
        contacts with phone numbers in a different format). If a phone number is not provided but a
        name is provided, Telerivet will search for a contact with that exact name (case
        insensitive). This behavior can be modified by setting the `lookup_key` parameter to look up
        a contact by another field, including a custom variable.
        
        If no existing contact is found, a new contact will be created.
        
        Then that contact will be updated with any parameters provided
        (`name`, `phone_number`, `vars`, `default_route_id`, `send_blocked`, `add_group_ids`,
        `remove_group_ids`).
        
        Arguments:
          - $options (associative array)
            
            - name
                * Name of the contact
            
            - phone_number
                * Phone number of the contact
            
            - lookup_key
                * The field used to search for a matching contact, or 'none' to always create a new
                    contact. To search by a custom variable, precede the variable name with 'vars.'.
                * Allowed values: phone_number, name, id, vars.variable_name, none
                * Default: phone_number
            
            - send_blocked (bool)
                * True if Telerivet is blocked from sending messages to this contact
            
            - default_route_id
                * ID of the route to use by default to send messages to this contact
            
            - add_group_ids (array)
                * ID of one or more groups to add this contact as a member (max 20)
            
            - id
                * ID of an existing contact (only used if `lookup_key` is 'id')
            
            - remove_group_ids (array)
                * ID of one or more groups to remove this contact as a member (max 20)
            
            - vars (associative array)
                * Custom variables and values to update on the contact
          
        Returns:
            Telerivet_Contact
        
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
     */   
    function getOrCreateContact($options)
    {                                       
        $data = $this->_api->doRequest("POST", "{$this->getBaseApiPath()}/contacts", $options);
        return new Telerivet_Contact($this->_api, $data);
    }    
    
    /**
        $project->sendMessages($options)
        
        Sends an SMS message (optionally with mail-merge templates) or voice call to a group or a
        list of up to 500 phone numbers
        
        Arguments:
          - $options (associative array)
              * Required
            
            - message_type
                * Type of message to send
                * Allowed values: sms, call
                * Default: sms
            
            - content
                * Content of the message to send
                * Required if sending SMS message
            
            - group_id
                * ID of the group to send the message to
                * Required if to_numbers not set
            
            - to_numbers (array of strings)
                * List of up to 500 phone numbers to send the message to
                * Required if group_id not set
            
            - route_id
                * ID of the phone or route to send the message from
                * Default: default sender phone ID
            
            - service_id
                * Service that defines the call flow of the voice call (when `message_type` is
                    `call`)
            
            - audio_url
                * The URL of an MP3 file to play when the contact answers the call (when
                    `message_type` is `call`).
                    
                    If `audio_url` is provided, the text-to-speech voice is not used to say
                    `content`, although you can optionally use `content` to indicate the script for the
                    audio.
                    
                    For best results, use an MP3 file containing only speech. Music is not
                    recommended because the audio quality will be low when played over a phone line.
            
            - tts_lang
                * The language of the text-to-speech voice (when `message_type` is `call`)
                * Allowed values: en-US, en-GB, en-GB-WLS, en-AU, en-IN, da-DK, nl-NL, fr-FR, fr-CA,
                    de-DE, is-IS, it-IT, pl-PL, pt-BR, pt-PT, ru-RU, es-ES, es-US, sv-SE
                * Default: en-US
            
            - tts_voice
                * The name of the text-to-speech voice (when message_type=call)
                * Allowed values: female, male
                * Default: female
            
            - status_url
                * Webhook callback URL to be notified when message status changes
            
            - status_secret
                * POST parameter 'secret' passed to status_url
            
            - label_ids (array)
                * Array of IDs of labels to add to all messages sent (maximum 5)
            
            - exclude_contact_id
                * Optionally excludes one contact from receiving the message (only when group_id is
                    set)
            
            - is_template (bool)
                * Set to true to evaluate variables like [[contact.name]] in message content [(See
                    available variables)](#variables)
                * Default: false
            
            - vars (associative array)
                * Custom variables to set for each message
          
        Returns:
            (associative array)
              - count_queued (int)
                  * Number of messages queued to send
              
              - broadcast_id
                  * ID of broadcast created for this message batch. If `count_queued` is 0 or 1, a
                      broadcast will not be created, and the `broadcast_id` property will be null.
    */
    function sendMessages($options)
    {
        $data = $this->_api->doRequest("POST", "{$this->getBaseApiPath()}/messages/send_batch", $options);
        return $data;
    }

    /**
        $project->receiveMessage($options)
        
        Add an incoming message to Telerivet. Acts the same as if the message was received by a
        phone. Also triggers any automated services that apply to the message.
        
        Arguments:
          - $options (associative array)
              * Required
            
            - content
                * Content of the incoming message
                * Required unless `message_type` is `call`
            
            - message_type
                * Type of message
                * Allowed values: sms, call
                * Default: sms
            
            - from_number
                * Phone number that sent the incoming message
                * Required
            
            - phone_id
                * ID of the phone (basic route) that received the message
                * Required
            
            - to_number
                * Phone number that the incoming message was sent to
                * Default: phone number of the phone that received the message
            
            - simulated (bool)
                * If true, Telerivet will not send automated replies to actual phones
            
            - starred (bool)
                * True if this message should be starred
            
            - label_ids (array)
                * Array of IDs of labels to add to this message (maximum 5)
            
            - vars (associative array)
                * Custom variables to set for this message
          
        Returns:
            Telerivet_Message
    */
    function receiveMessage($options)
    {
        return new Telerivet_Message($this->_api, $this->_api->doRequest("POST", "{$this->getBaseApiPath()}/messages/receive", $options));
    }

    /**
        $project->queryContacts($options)
        
        Queries contacts within the given project.
        
        Arguments:
          - $options (associative array)
            
            - name
                * Filter contacts by name
                * Allowed modifiers: name[ne], name[prefix], name[not_prefix], name[gte], name[gt],
                    name[lt], name[lte]
            
            - phone_number
                * Filter contacts by phone number
                * Allowed modifiers: phone_number[ne], phone_number[prefix],
                    phone_number[not_prefix], phone_number[gte], phone_number[gt], phone_number[lt],
                    phone_number[lte]
            
            - time_created (UNIX timestamp)
                * Filter contacts by time created
                * Allowed modifiers: time_created[ne], time_created[min], time_created[max]
            
            - last_message_time (UNIX timestamp)
                * Filter contacts by last time a message was sent or received
                * Allowed modifiers: last_message_time[exists], last_message_time[ne],
                    last_message_time[min], last_message_time[max]
            
            - last_incoming_message_time (UNIX timestamp)
                * Filter contacts by last time a message was received
                * Allowed modifiers: last_incoming_message_time[exists],
                    last_incoming_message_time[ne], last_incoming_message_time[min],
                    last_incoming_message_time[max]
            
            - last_outgoing_message_time (UNIX timestamp)
                * Filter contacts by last time a message was sent
                * Allowed modifiers: last_outgoing_message_time[exists],
                    last_outgoing_message_time[ne], last_outgoing_message_time[min],
                    last_outgoing_message_time[max]
            
            - incoming_message_count (int)
                * Filter contacts by number of messages received from the contact
                * Allowed modifiers: incoming_message_count[ne], incoming_message_count[min],
                    incoming_message_count[max]
            
            - outgoing_message_count (int)
                * Filter contacts by number of messages sent to the contact
                * Allowed modifiers: outgoing_message_count[ne], outgoing_message_count[min],
                    outgoing_message_count[max]
            
            - send_blocked (bool)
                * Filter contacts by blocked status
            
            - vars (associative array)
                * Filter contacts by value of a custom variable (e.g. vars[email], vars[foo], etc.)
                * Allowed modifiers: vars[foo][exists], vars[foo][ne], vars[foo][prefix],
                    vars[foo][not_prefix], vars[foo][gte], vars[foo][gt], vars[foo][lt], vars[foo][lte],
                    vars[foo][min], vars[foo][max]
            
            - sort
                * Sort the results based on a field
                * Allowed values: default, name, phone_number, last_message_time
                * Default: default
            
            - sort_dir
                * Sort the results in ascending or descending order
                * Allowed values: asc, desc
                * Default: asc
            
            - page_size (int)
                * Number of results returned per page (max 200)
                * Default: 50
            
            - offset (int)
                * Number of items to skip from beginning of result set
                * Default: 0
          
        Returns:
            Telerivet_APICursor (of Telerivet_Contact)
    */
    function queryContacts($options = null)
    {
        return $this->_api->newApiCursor('Telerivet_Contact', "{$this->getBaseApiPath()}/contacts", $options);
    }

    /**
        $project->getContactById($id)
        
        Retrieves the contact with the given ID.
        
        Arguments:
          - $id
              * ID of the contact
              * Required
          
        Returns:
            Telerivet_Contact
    */
    function getContactById($id)
    {
        return new Telerivet_Contact($this->_api, $this->_api->doRequest("GET", "{$this->getBaseApiPath()}/contacts/{$id}"));
    }

    /**
        $project->initContactById($id)
        
        Initializes the Telerivet contact with the given ID without making an API request.
        
        Arguments:
          - $id
              * ID of the contact
              * Required
          
        Returns:
            Telerivet_Contact
    */
    function initContactById($id)
    {
        return new Telerivet_Contact($this->_api, array('project_id' => $this->id, 'id' => $id), false);
    }

    /**
        $project->queryPhones($options)
        
        Queries phones within the given project.
        
        Arguments:
          - $options (associative array)
            
            - name
                * Filter phones by name
                * Allowed modifiers: name[ne], name[prefix], name[not_prefix], name[gte], name[gt],
                    name[lt], name[lte]
            
            - phone_number
                * Filter phones by phone number
                * Allowed modifiers: phone_number[ne], phone_number[prefix],
                    phone_number[not_prefix], phone_number[gte], phone_number[gt], phone_number[lt],
                    phone_number[lte]
            
            - last_active_time (UNIX timestamp)
                * Filter phones by last active time
                * Allowed modifiers: last_active_time[exists], last_active_time[ne],
                    last_active_time[min], last_active_time[max]
            
            - sort
                * Sort the results based on a field
                * Allowed values: default, name, phone_number
                * Default: default
            
            - sort_dir
                * Sort the results in ascending or descending order
                * Allowed values: asc, desc
                * Default: asc
            
            - page_size (int)
                * Number of results returned per page (max 200)
                * Default: 50
            
            - offset (int)
                * Number of items to skip from beginning of result set
                * Default: 0
          
        Returns:
            Telerivet_APICursor (of Telerivet_Phone)
    */
    function queryPhones($options = null)
    {
        return $this->_api->newApiCursor('Telerivet_Phone', "{$this->getBaseApiPath()}/phones", $options);
    }

    /**
        $project->getPhoneById($id)
        
        Retrieves the phone with the given ID.
        
        Arguments:
          - $id
              * ID of the phone - see <https://telerivet.com/dashboard/api>
              * Required
          
        Returns:
            Telerivet_Phone
    */
    function getPhoneById($id)
    {
        return new Telerivet_Phone($this->_api, $this->_api->doRequest("GET", "{$this->getBaseApiPath()}/phones/{$id}"));
    }

    /**
        $project->initPhoneById($id)
        
        Initializes the phone with the given ID without making an API request.
        
        Arguments:
          - $id
              * ID of the phone - see <https://telerivet.com/dashboard/api>
              * Required
          
        Returns:
            Telerivet_Phone
    */
    function initPhoneById($id)
    {
        return new Telerivet_Phone($this->_api, array('project_id' => $this->id, 'id' => $id), false);
    }

    /**
        $project->queryMessages($options)
        
        Queries messages within the given project.
        
        Arguments:
          - $options (associative array)
            
            - direction
                * Filter messages by direction
                * Allowed values: incoming, outgoing
            
            - message_type
                * Filter messages by message_type
                * Allowed values: sms, mms, ussd, call
            
            - source
                * Filter messages by source
                * Allowed values: phone, provider, web, api, service, webhook, scheduled
            
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
            
            - sort
                * Sort the results based on a field
                * Allowed values: default
                * Default: default
            
            - sort_dir
                * Sort the results in ascending or descending order
                * Allowed values: asc, desc
                * Default: asc
            
            - page_size (int)
                * Number of results returned per page (max 200)
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
        $project->getMessageById($id)
        
        Retrieves the message with the given ID.
        
        Arguments:
          - $id
              * ID of the message
              * Required
          
        Returns:
            Telerivet_Message
    */
    function getMessageById($id)
    {
        return new Telerivet_Message($this->_api, $this->_api->doRequest("GET", "{$this->getBaseApiPath()}/messages/{$id}"));
    }

    /**
        $project->initMessageById($id)
        
        Initializes the Telerivet message with the given ID without making an API request.
        
        Arguments:
          - $id
              * ID of the message
              * Required
          
        Returns:
            Telerivet_Message
    */
    function initMessageById($id)
    {
        return new Telerivet_Message($this->_api, array('project_id' => $this->id, 'id' => $id), false);
    }

    /**
        $project->queryBroadcasts($options)
        
        Queries broadcasts within the given project.
        
        Arguments:
          - $options (associative array)
            
            - time_created[min] (UNIX timestamp)
                * Filter broadcasts created on or after a particular time
            
            - time_created[max] (UNIX timestamp)
                * Filter broadcasts created before a particular time
            
            - last_message_time[min] (UNIX timestamp)
                * Filter broadcasts with most recent message on or after a particular time
            
            - last_message_time[max] (UNIX timestamp)
                * Filter broadcasts with most recent message before a particular time
            
            - sort
                * Sort the results based on a field
                * Allowed values: default, last_message_time
                * Default: default
            
            - sort_dir
                * Sort the results in ascending or descending order
                * Allowed values: asc, desc
                * Default: asc
            
            - page_size (int)
                * Number of results returned per page (max 200)
                * Default: 50
            
            - offset (int)
                * Number of items to skip from beginning of result set
                * Default: 0
          
        Returns:
            Telerivet_APICursor (of Telerivet_Broadcast)
    */
    function queryBroadcasts($options = null)
    {
        return $this->_api->newApiCursor('Telerivet_Broadcast', "{$this->getBaseApiPath()}/broadcasts", $options);
    }

    /**
        $project->getBroadcastById($id)
        
        Retrieves the broadcast with the given ID.
        
        Arguments:
          - $id
              * ID of the broadcast
              * Required
          
        Returns:
            Telerivet_Broadcast
    */
    function getBroadcastById($id)
    {
        return new Telerivet_Broadcast($this->_api, $this->_api->doRequest("GET", "{$this->getBaseApiPath()}/broadcasts/{$id}"));
    }

    /**
        $project->initBroadcastById($id)
        
        Initializes the Telerivet broadcast with the given ID without making an API request.
        
        Arguments:
          - $id
              * ID of the broadcast
              * Required
          
        Returns:
            Telerivet_Broadcast
    */
    function initBroadcastById($id)
    {
        return new Telerivet_Broadcast($this->_api, array('project_id' => $this->id, 'id' => $id), false);
    }

    /**
        $project->queryGroups($options)
        
        Queries groups within the given project.
        
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
                * Number of results returned per page (max 200)
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
        $project->getOrCreateGroup($name)
        
        Retrieves or creates a group by name.
        
        Arguments:
          - name
              * Name of the group
              * Required
          
        Returns:
            Telerivet_Group
    */
    function getOrCreateGroup($name)
    {
        return new Telerivet_Group($this->_api, $this->_api->doRequest("POST", "{$this->getBaseApiPath()}/groups", array('name' => $name)));
    }

    /**
        $project->getGroupById($id)
        
        Retrieves the group with the given ID.
        
        Arguments:
          - $id
              * ID of the group
              * Required
          
        Returns:
            Telerivet_Group
    */
    function getGroupById($id)
    {
        return new Telerivet_Group($this->_api, $this->_api->doRequest("GET", "{$this->getBaseApiPath()}/groups/{$id}"));
    }

    /**
        $project->initGroupById($id)
        
        Initializes the group with the given ID without making an API request.
        
        Arguments:
          - $id
              * ID of the group
              * Required
          
        Returns:
            Telerivet_Group
    */
    function initGroupById($id)
    {
        return new Telerivet_Group($this->_api, array('project_id' => $this->id, 'id' => $id), false);
    }

    /**
        $project->queryLabels($options)
        
        Queries labels within the given project.
        
        Arguments:
          - $options (associative array)
            
            - name
                * Filter labels by name
                * Allowed modifiers: name[ne], name[prefix], name[not_prefix], name[gte], name[gt],
                    name[lt], name[lte]
            
            - sort
                * Sort the results based on a field
                * Allowed values: default, name
                * Default: default
            
            - sort_dir
                * Sort the results in ascending or descending order
                * Allowed values: asc, desc
                * Default: asc
            
            - page_size (int)
                * Number of results returned per page (max 200)
                * Default: 50
            
            - offset (int)
                * Number of items to skip from beginning of result set
                * Default: 0
          
        Returns:
            Telerivet_APICursor (of Telerivet_Label)
    */
    function queryLabels($options = null)
    {
        return $this->_api->newApiCursor('Telerivet_Label', "{$this->getBaseApiPath()}/labels", $options);
    }

    /**
        $project->getOrCreateLabel($name)
        
        Gets or creates a label by name.
        
        Arguments:
          - name
              * Name of the label
              * Required
          
        Returns:
            Telerivet_Label
    */
    function getOrCreateLabel($name)
    {
        return new Telerivet_Label($this->_api, $this->_api->doRequest("POST", "{$this->getBaseApiPath()}/labels", array('name' => $name)));
    }

    /**
        $project->getLabelById($id)
        
        Retrieves the label with the given ID.
        
        Arguments:
          - $id
              * ID of the label
              * Required
          
        Returns:
            Telerivet_Label
    */
    function getLabelById($id)
    {
        return new Telerivet_Label($this->_api, $this->_api->doRequest("GET", "{$this->getBaseApiPath()}/labels/{$id}"));
    }

    /**
        $project->initLabelById($id)
        
        Initializes the label with the given ID without making an API request.
        
        Arguments:
          - $id
              * ID of the label
              * Required
          
        Returns:
            Telerivet_Label
    */
    function initLabelById($id)
    {
        return new Telerivet_Label($this->_api, array('project_id' => $this->id, 'id' => $id), false);
    }

    /**
        $project->queryDataTables($options)
        
        Queries data tables within the given project.
        
        Arguments:
          - $options (associative array)
            
            - name
                * Filter data tables by name
                * Allowed modifiers: name[ne], name[prefix], name[not_prefix], name[gte], name[gt],
                    name[lt], name[lte]
            
            - sort
                * Sort the results based on a field
                * Allowed values: default, name
                * Default: default
            
            - sort_dir
                * Sort the results in ascending or descending order
                * Allowed values: asc, desc
                * Default: asc
            
            - page_size (int)
                * Number of results returned per page (max 200)
                * Default: 50
            
            - offset (int)
                * Number of items to skip from beginning of result set
                * Default: 0
          
        Returns:
            Telerivet_APICursor (of Telerivet_DataTable)
    */
    function queryDataTables($options = null)
    {
        return $this->_api->newApiCursor('Telerivet_DataTable', "{$this->getBaseApiPath()}/tables", $options);
    }

    /**
        $project->getOrCreateDataTable($name)
        
        Gets or creates a data table by name.
        
        Arguments:
          - name
              * Name of the data table
              * Required
          
        Returns:
            Telerivet_DataTable
    */
    function getOrCreateDataTable($name)
    {
        return new Telerivet_DataTable($this->_api, $this->_api->doRequest("POST", "{$this->getBaseApiPath()}/tables", array('name' => $name)));
    }

    /**
        $project->getDataTableById($id)
        
        Retrieves the data table with the given ID.
        
        Arguments:
          - $id
              * ID of the data table
              * Required
          
        Returns:
            Telerivet_DataTable
    */
    function getDataTableById($id)
    {
        return new Telerivet_DataTable($this->_api, $this->_api->doRequest("GET", "{$this->getBaseApiPath()}/tables/{$id}"));
    }

    /**
        $project->initDataTableById($id)
        
        Initializes the data table with the given ID without making an API request.
        
        Arguments:
          - $id
              * ID of the data table
              * Required
          
        Returns:
            Telerivet_DataTable
    */
    function initDataTableById($id)
    {
        return new Telerivet_DataTable($this->_api, array('project_id' => $this->id, 'id' => $id), false);
    }

    /**
        $project->queryScheduledMessages($options)
        
        Queries scheduled messages within the given project.
        
        Arguments:
          - $options (associative array)
            
            - message_type
                * Filter scheduled messages by message_type
                * Allowed values: sms, mms, ussd, call
            
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
                * Number of results returned per page (max 200)
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
        $project->getScheduledMessageById($id)
        
        Retrieves the scheduled message with the given ID.
        
        Arguments:
          - $id
              * ID of the scheduled message
              * Required
          
        Returns:
            Telerivet_ScheduledMessage
    */
    function getScheduledMessageById($id)
    {
        return new Telerivet_ScheduledMessage($this->_api, $this->_api->doRequest("GET", "{$this->getBaseApiPath()}/scheduled/{$id}"));
    }

    /**
        $project->initScheduledMessageById($id)
        
        Initializes the scheduled message with the given ID without making an API request.
        
        Arguments:
          - $id
              * ID of the scheduled message
              * Required
          
        Returns:
            Telerivet_ScheduledMessage
    */
    function initScheduledMessageById($id)
    {
        return new Telerivet_ScheduledMessage($this->_api, array('project_id' => $this->id, 'id' => $id), false);
    }

    /**
        $project->queryServices($options)
        
        Queries services within the given project.
        
        Arguments:
          - $options (associative array)
            
            - name
                * Filter services by name
                * Allowed modifiers: name[ne], name[prefix], name[not_prefix], name[gte], name[gt],
                    name[lt], name[lte]
            
            - active (bool)
                * Filter services by active/inactive state
            
            - context
                * Filter services that can be invoked in a particular context
                * Allowed values: message, call, contact, project
            
            - sort
                * Sort the results based on a field
                * Allowed values: default, priority, name
                * Default: default
            
            - sort_dir
                * Sort the results in ascending or descending order
                * Allowed values: asc, desc
                * Default: asc
            
            - page_size (int)
                * Number of results returned per page (max 200)
                * Default: 50
            
            - offset (int)
                * Number of items to skip from beginning of result set
                * Default: 0
          
        Returns:
            Telerivet_APICursor (of Telerivet_Service)
    */
    function queryServices($options = null)
    {
        return $this->_api->newApiCursor('Telerivet_Service', "{$this->getBaseApiPath()}/services", $options);
    }

    /**
        $project->getServiceById($id)
        
        Retrieves the service with the given ID.
        
        Arguments:
          - $id
              * ID of the service
              * Required
          
        Returns:
            Telerivet_Service
    */
    function getServiceById($id)
    {
        return new Telerivet_Service($this->_api, $this->_api->doRequest("GET", "{$this->getBaseApiPath()}/services/{$id}"));
    }

    /**
        $project->initServiceById($id)
        
        Initializes the service with the given ID without making an API request.
        
        Arguments:
          - $id
              * ID of the service
              * Required
          
        Returns:
            Telerivet_Service
    */
    function initServiceById($id)
    {
        return new Telerivet_Service($this->_api, array('project_id' => $this->id, 'id' => $id), false);
    }

    /**
        $project->queryReceipts($options)
        
        Queries mobile money receipts within the given project.
        
        Arguments:
          - $options (associative array)
            
            - tx_id
                * Filter receipts by transaction ID
            
            - tx_type
                * Filter receipts by transaction type
                * Allowed values: receive_money, send_money, pay_bill, deposit, withdrawal,
                    airtime_purchase, balance_inquiry, reversal
            
            - tx_time (UNIX timestamp)
                * Filter receipts by transaction time
                * Allowed modifiers: tx_time[ne], tx_time[min], tx_time[max]
            
            - name
                * Filter receipts by other person's name
                * Allowed modifiers: name[ne], name[prefix], name[not_prefix], name[gte], name[gt],
                    name[lt], name[lte]
            
            - phone_number
                * Filter receipts by other person's phone number
                * Allowed modifiers: phone_number[ne], phone_number[prefix],
                    phone_number[not_prefix], phone_number[gte], phone_number[gt], phone_number[lt],
                    phone_number[lte]
            
            - sort
                * Sort the results based on a field
                * Allowed values: default
                * Default: default
            
            - sort_dir
                * Sort the results in ascending or descending order
                * Allowed values: asc, desc
                * Default: asc
            
            - page_size (int)
                * Number of results returned per page (max 200)
                * Default: 50
            
            - offset (int)
                * Number of items to skip from beginning of result set
                * Default: 0
          
        Returns:
            Telerivet_APICursor (of Telerivet_MobileMoneyReceipt)
    */
    function queryReceipts($options = null)
    {
        return $this->_api->newApiCursor('Telerivet_MobileMoneyReceipt', "{$this->getBaseApiPath()}/receipts", $options);
    }

    /**
        $project->getReceiptById($id)
        
        Retrieves the mobile money receipt with the given ID.
        
        Arguments:
          - $id
              * ID of the mobile money receipt
              * Required
          
        Returns:
            Telerivet_MobileMoneyReceipt
    */
    function getReceiptById($id)
    {
        return new Telerivet_MobileMoneyReceipt($this->_api, $this->_api->doRequest("GET", "{$this->getBaseApiPath()}/receipts/{$id}"));
    }

    /**
        $project->initReceiptById($id)
        
        Initializes the mobile money receipt with the given ID without making an API request.
        
        Arguments:
          - $id
              * ID of the mobile money receipt
              * Required
          
        Returns:
            Telerivet_MobileMoneyReceipt
    */
    function initReceiptById($id)
    {
        return new Telerivet_MobileMoneyReceipt($this->_api, array('project_id' => $this->id, 'id' => $id), false);
    }

    /**
        $project->queryRoutes($options)
        
        Queries custom routes that can be used to send messages (not including Phones).
        
        Arguments:
          - $options (associative array)
            
            - name
                * Filter routes by name
                * Allowed modifiers: name[ne], name[prefix], name[not_prefix], name[gte], name[gt],
                    name[lt], name[lte]
            
            - sort
                * Sort the results based on a field
                * Allowed values: default, name
                * Default: default
            
            - sort_dir
                * Sort the results in ascending or descending order
                * Allowed values: asc, desc
                * Default: asc
            
            - page_size (int)
                * Number of results returned per page (max 200)
                * Default: 50
            
            - offset (int)
                * Number of items to skip from beginning of result set
                * Default: 0
          
        Returns:
            Telerivet_APICursor (of Telerivet_Route)
    */
    function queryRoutes($options = null)
    {
        return $this->_api->newApiCursor('Telerivet_Route', "{$this->getBaseApiPath()}/routes", $options);
    }

    /**
        $project->getRouteById($id)
        
        Gets a custom route by ID
        
        Arguments:
          - $id
              * ID of the route
              * Required
          
        Returns:
            Telerivet_Route
    */
    function getRouteById($id)
    {
        return new Telerivet_Route($this->_api, $this->_api->doRequest("GET", "{$this->getBaseApiPath()}/routes/{$id}"));
    }

    /**
        $project->initRouteById($id)
        
        Initializes a custom route by ID without making an API request.
        
        Arguments:
          - $id
              * ID of the route
              * Required
          
        Returns:
            Telerivet_Route
    */
    function initRouteById($id)
    {
        return new Telerivet_Route($this->_api, array('project_id' => $this->id, 'id' => $id), false);
    }

    /**
        $project->getUsers()
        
        Returns an array of user accounts that have access to this project. Each item in the array
        is an object containing `id`, `email`, and `name` properties. (The id corresponds to the
        `user_id` property of the Message object.)
        
        Returns:
            array
    */
    function getUsers()
    {
        return $this->_api->doRequest("GET", "{$this->getBaseApiPath()}/users");
    }

    /**
        $project->save()
        
        Saves any fields or custom variables that have changed for the project.
    */
    function save()
    {
        parent::save();
    }

    function getBaseApiPath()
    {
        return "/projects/{$this->id}";
    }
}