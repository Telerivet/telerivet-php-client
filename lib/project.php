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
        
        Sends one message (SMS, MMS, voice call, or USSD request).
        
        Arguments:
          - $options (associative array)
              * Required
            
            - message_type
                * Type of message to send. If `text`, will use the default text message type for the
                    selected route.
                * Allowed values: sms, mms, ussd, call, text
                * Default: text
            
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
                * Default: default sender route ID for your project
            
            - status_url
                * Webhook callback URL to be notified when message status changes
            
            - status_secret
                * POST parameter 'secret' passed to status_url
            
            - is_template (bool)
                * Set to true to evaluate variables like [[contact.name]] in message content. [(See
                    available variables)](#variables)
                * Default: false
            
            - track_clicks (boolean)
                * If true, URLs in the message content will automatically be replaced with unique
                    short URLs.
                * Default: false
            
            - short_link_params (associative array)
                *
                    If `track_clicks` is true, `short_link_params` may be used to specify
                    custom parameters for each short link in the message. The following parameters are
                    supported:
                    
                    `domain` (string): A custom short domain name to use for the short
                    links. The domain name must already be registered for your project or organization.
                    
                    `expiration_sec` (integer): The number of seconds after the message is
                    created (queued to send) when the short links will stop forwarding to the
                    destination URL.
                    If null, the short links will not expire.
            
            - media_urls (array)
                * URLs of media files to attach to the text message. If `message_type` is `sms`,
                    short links to each media URL will be appended to the end of the content (separated
                    by a new line).
            
            - route_params (associative array)
                * Route-specific parameters for the message. The parameters object should have one
                    or more keys matching the `phone_type` field of a phone (basic route) that may be
                    used to send the message. The corresponding value should be an object with
                    route-specific parameters to use if the message is sent by that type of route.
            
            - label_ids (array)
                * List of IDs of labels to add to this message
            
            - vars (associative array)
                * Custom variables to store with the message
            
            - priority (int)
                * Priority of the message. Telerivet will attempt to send messages with higher
                    priority numbers first (for example, so you can prioritize an auto-reply ahead of a
                    bulk message to a large group).
                * Allowed values: 1, 2
                * Default: 1
            
            - simulated (bool)
                * Set to true to test the Telerivet API without actually sending a message from the
                    route
                * Default: false
            
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
        
        With `message_type`=`service`, schedules an automated service (such
        as a poll) to be invoked for a group or list of phone numbers. Any service that can be
        triggered for a contact can be scheduled via this method, whether or not the service
        actually sends a message.
        
        Arguments:
          - $options (associative array)
              * Required
            
            - message_type
                * Type of message to send
                * Allowed values: sms, ussd, call, service
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
                * Default: default sender route ID
            
            - service_id
                * Service to invoke for each recipient (when `message_type` is `call` or `service`)
                * Required if message_type is service
            
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
            
            - track_clicks (boolean)
                * If true, URLs in the message content will automatically be replaced with unique
                    short URLs.
                * Default: false
            
            - short_link_params (associative array)
                *
                    If `track_clicks` is true, `short_link_params` may be used to specify
                    custom parameters for each short link in the message. The following parameters are
                    supported:
                    
                    `domain` (string): A custom short domain name to use for the short
                    links. The domain name must already be registered for your project or organization.
                    
                    `expiration_sec` (integer): The number of seconds after the message is
                    created (queued to send) when the short links will stop forwarding to the
                    destination URL.
                    If null, the short links will not expire.
            
            - is_template (bool)
                * Set to true to evaluate variables like [[contact.name]] in message content
                * Default: false
            
            - media_urls (array)
                * URLs of media files to attach to the text message. If `message_type` is `sms`,
                    short links to each media URL will be appended to the end of the content (separated
                    by a new line).
            
            - route_params (associative array)
                * Route-specific parameters to use when sending the message. The parameters object
                    may have keys matching the `phone_type` field of a phone (basic route) that may be
                    used to send the message. The corresponding value is an object with route-specific
                    parameters to use when sending a message with that type of route.
            
            - label_ids (array)
                * Array of IDs of labels to add to the sent messages (maximum 5). Does not apply
                    when `message_type`=`service`, since the labels are determined by the service
                    itself.
            
            - timezone_id
                * TZ database timezone ID; see
                    <http://en.wikipedia.org/wiki/List_of_tz_database_time_zones>
                * Default: project default timezone
            
            - end_time (UNIX timestamp)
                * Time after which a recurring message will stop (not applicable to non-recurring
                    scheduled messages)
            
            - end_time_offset (int)
                * Number of seconds from now until the recurring message will stop
            
            - vars (associative array)
                * Custom variables to set for this scheduled message, which will be copied to each
                    message sent from this scheduled message
          
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
        $project->sendBroadcast($options)
        
        Sends a text message (optionally with mail-merge templates) or voice call to a group or a
        list of up to 500 phone numbers.
        
        With `message_type`=`service`, invokes an automated service (such as
        a poll) for a group or list of phone numbers. Any service that can be triggered for a
        contact can be invoked via this method, whether or not the service actually sends a message.
        
        Arguments:
          - $options (associative array)
              * Required
            
            - message_type
                * Type of message to send. If `text`, will use the default text message type for the
                    selected route.
                * Allowed values: sms, mms, call, service, text
                * Default: text
            
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
                * Default: default sender route ID
            
            - title (string)
                * Title of the broadcast. If a title is not provided, a title will automatically be
                    generated from the recipient group name or phone numbers.
            
            - status_url
                * Webhook callback URL to be notified when message status changes
            
            - status_secret
                * POST parameter 'secret' passed to status_url
            
            - label_ids (array)
                * Array of IDs of labels to add to all messages sent (maximum 5). Does not apply
                    when `message_type`=`service`, since the labels are determined by the service
                    itself.
            
            - exclude_contact_id
                * Optionally excludes one contact from receiving the message (only when group_id is
                    set)
            
            - is_template (bool)
                * Set to true to evaluate variables like [[contact.name]] in message content [(See
                    available variables)](#variables)
                * Default: false
            
            - track_clicks (boolean)
                * If true, URLs in the message content will automatically be replaced with unique
                    short URLs.
                * Default: false
            
            - short_link_params (associative array)
                *
                    If `track_clicks` is true, `short_link_params` may be used to specify
                    custom parameters for each short link in the message. The following parameters are
                    supported:
                    
                    `domain` (string): A custom short domain name to use for the short
                    links. The domain name must already be registered for your project or organization.
                    
                    `expiration_sec` (integer): The number of seconds after the message is
                    created (queued to send) when the short links will stop forwarding to the
                    destination URL.
                    If null, the short links will not expire.
            
            - media_urls (array)
                * URLs of media files to attach to the text message. If `message_type` is `sms`,
                    short links to each URL will be appended to the end of the content (separated by a
                    new line).
            
            - vars (associative array)
                * Custom variables to set for each message
            
            - route_params (associative array)
                * Route-specific parameters for the messages in the broadcast. The parameters object
                    may have keys matching the `phone_type` field of a phone (basic route) that may be
                    used to send messages in this broadcast. The corresponding value is an object with
                    route-specific parameters to use when sending messages with that type of route.
            
            - service_id
                * Service to invoke for each recipient (when `message_type` is `call` or `service`)
                * Required if message_type is service
            
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
          
        Returns:
            Telerivet_Broadcast
    */
    function sendBroadcast($options)
    {
        return new Telerivet_Broadcast($this->_api, $this->_api->doRequest("POST", "{$this->getBaseApiPath()}/send_broadcast", $options));
    }

    /**
        $project->sendMulti($options)
        
        Sends up to 100 different messages in a single API request. This method is significantly
        faster than sending a separate API request for each message.
        
        Arguments:
          - $options (associative array)
              * Required
            
            - messages (array)
                * Array of up to 100 objects with `content` and `to_number` properties. Each object
                    may also contain the optional properties `status_url`, `status_secret`, `vars`,
                    and/or `priority`, which override the parameters of the same name defined below, to
                    allow passing different values for each message.
                * Required
            
            - message_type
                * Type of message to send. If `text`, will use the default text message type for the
                    selected route.
                * Allowed values: sms, mms, chat, text
                * Default: text
            
            - route_id
                * ID of the phone or route to send the messages from
                * Default: default sender route ID
            
            - broadcast_id (string)
                * ID of an existing broadcast to associate the messages with
            
            - broadcast_title (string)
                * Title of broadcast to create (when `broadcast_id` is not provided).
                    When sending more than 100 messages over multiple API
                    requests, you can associate all messages with the same broadcast by providing a
                    `broadcast_title` parameter in the first
                    API request, then retrieving the `broadcast_id` property
                    from the API response, and passing it as the `broadcast_id` parameter in subsequent
                    API requests.
            
            - status_url
                * Webhook callback URL to be notified when message status changes
            
            - status_secret
                * POST parameter 'secret' passed to status_url
            
            - label_ids (array)
                * Array of IDs of labels to add to each message (maximum 5)
            
            - is_template (bool)
                * Set to true to evaluate variables like [[contact.name]] in message content [(See
                    available variables)](#variables)
                * Default: false
            
            - track_clicks (boolean)
                * If true, URLs in the message content will automatically be replaced with unique
                    short URLs.
                * Default: false
            
            - short_link_params (associative array)
                *
                    If `track_clicks` is true, `short_link_params` may be used to specify
                    custom parameters for each short link in the message. The following parameters are
                    supported:
                    
                    `domain` (string): A custom short domain name to use for the short
                    links. The domain name must already be registered for your project or organization.
                    
                    `expiration_sec` (integer): The number of seconds after the message is
                    created (queued to send) when the short links will stop forwarding to the
                    destination URL.
                    If null, the short links will not expire.
            
            - media_urls (array)
                * URLs of media files to attach to the text message. If `message_type` is `sms`,
                    short links to each media URL will be appended to the end of the content (separated
                    by a new line).
            
            - route_params (associative array)
                * Route-specific parameters to apply to all messages. The parameters object may have
                    keys matching the `phone_type` field of a phone (basic route) that may be used to
                    send messages. The corresponding value is an object with route-specific parameters
                    to use when sending messages with that type of route.
            
            - vars (associative array)
                * Custom variables to store with the message
            
            - priority (int)
                * Priority of the message. Telerivet will attempt to send messages with higher
                    priority numbers first (for example, so you can prioritize an auto-reply ahead of a
                    bulk message to a large group).
                * Allowed values: 1, 2
                * Default: 1
            
            - simulated (bool)
                * Set to true to test the Telerivet API without actually sending a message from the
                    route
                * Default: false
          
        Returns:
            (associative array)
              - messages (array)
                  * List of objects representing each newly created message, with the same length
                      and order as provided in the `messages` parameter in the API request.
                      Each object has the `id` and `status` properties,
                      and may have the property `error_message`.
                      (Other properties of the Message object are
                      omitted in order to reduce the amount of redundant data sent in each API
                      response.)
                      If the `messages` parameter in the API request
                      contains items with `to_number` values that are associated with blocked contacts,
                      the `id` and `status` properties corresponding to those items will be null, and no
                      messages will be sent to those numbers.
              
              - broadcast_id
                  * ID of broadcast that these messages are associated with, if `broadcast_id` or
                      `broadcast_title` parameter is provided in the API request.
    */
    function sendMulti($options)
    {
        $data = $this->_api->doRequest("POST", "{$this->getBaseApiPath()}/send_multi", $options);
        return $data;
    }

    /**
        $project->sendMessages($options)
        
        (Deprecated) Send a message a to group or a list of phone numbers.
        This method is only needed to maintain backward compatibility with
        code developed using previous versions of the client library.
        Use `sendBroadcast` or `sendMulti` instead.
        
        Arguments:
          - $options (associative array)
              * Required
            
            - message_type
            
            - content
                * Required
            
            - group_id
            
            - to_numbers
          
        Returns:
            (associative array)
              - count_queued (int)
                  * Number of messages queued to send
              
              - broadcast_id
                  * ID of broadcast created for this message batch.
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
        $project->importContacts($options)
        
        Creates and/or updates up to 200 contacts in a single API call. When creating or updating a
        large number of contacts, this method is significantly faster than sending a separate API
        request for each contact.
        
        By default, if the phone number for any contact matches an existing
        contact, the existing contact will be updated with any information provided. This behavior
        can be modified by setting the `lookup_key` parameter to look up contacts by another field,
        including a custom variable.
        
        If any contact was not found matching the provided `lookup_key`, a
        new contact will be created.
        
        Arguments:
          - $options (associative array)
              * Required
            
            - contacts (array)
                * Array of up to 200 objects which may contain the properties `name` (string),
                    `phone_number` (string), `vars` (object), and `send_blocked` (boolean). All
                    properties are optional, unless used as a lookup key; however, either a `name` or
                    `phone_number` property must be provided for new contacts.
                * Required
            
            - lookup_key
                * The field used to search for a matching contact, or 'none' to always create a new
                    contact. To search by a custom variable, precede the variable name with 'vars.'.
                * Allowed values: phone_number, id, vars.variable_name, none
                * Default: phone_number
            
            - add_group_ids (array)
                * ID of one or more groups to add these contacts as members (max 5)
            
            - remove_group_ids (array)
                * ID of one or more groups to remove these contacts as members (max 5)
            
            - default_route_id
                * ID of the route to use by default to send messages to these contacts
          
        Returns:
            (associative array)
              - contacts (array)
                  * List of objects representing each contact, with the same length and order as
                      provided in the `contacts` parameter in the API request. Each object has a string
                      `id` property.
    */
    function importContacts($options)
    {
        $data = $this->_api->doRequest("POST", "{$this->getBaseApiPath()}/import_contacts", $options);
        return $data;
    }

    /**
        $project->queryContacts($options)
        
        Queries contacts within the given project.
        
        Arguments:
          - $options (associative array)
            
            - group_id
                * Filter contacts within a group
            
            - name
                * Filter contacts by name
                * Allowed modifiers: name[ne], name[prefix], name[not_prefix], name[gte], name[gt],
                    name[lt], name[lte]
            
            - phone_number
                * Filter contacts by phone number
                * Allowed modifiers: phone_number[ne], phone_number[prefix],
                    phone_number[not_prefix], phone_number[gte], phone_number[gt], phone_number[lt],
                    phone_number[lte], phone_number[exists]
            
            - time_created (UNIX timestamp)
                * Filter contacts by time created
                * Allowed modifiers: time_created[min], time_created[max]
            
            - last_message_time (UNIX timestamp)
                * Filter contacts by last time a message was sent or received
                * Allowed modifiers: last_message_time[min], last_message_time[max],
                    last_message_time[exists]
            
            - last_incoming_message_time (UNIX timestamp)
                * Filter contacts by last time a message was received
                * Allowed modifiers: last_incoming_message_time[min],
                    last_incoming_message_time[max], last_incoming_message_time[exists]
            
            - last_outgoing_message_time (UNIX timestamp)
                * Filter contacts by last time a message was sent
                * Allowed modifiers: last_outgoing_message_time[min],
                    last_outgoing_message_time[max], last_outgoing_message_time[exists]
            
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
                * Allowed modifiers: vars[foo][ne], vars[foo][prefix], vars[foo][not_prefix],
                    vars[foo][gte], vars[foo][gt], vars[foo][lt], vars[foo][lte], vars[foo][min],
                    vars[foo][max], vars[foo][exists]
            
            - sort
                * Sort the results based on a field
                * Allowed values: default, name, phone_number, last_message_time
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
                * Allowed modifiers: last_active_time[min], last_active_time[max],
                    last_active_time[exists]
            
            - sort
                * Sort the results based on a field
                * Allowed values: default, name, phone_number
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
            
            - label_id
                * Filter messages with a label
            
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
                * Number of results returned per page (max 500)
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
        $project->createTask($options)
        
        Creates and starts an asynchronous task that is applied to all entities matching a filter
        (e.g. contacts, messages, or data rows).
        Tasks are designed to efficiently process a large number of
        entities. When processing a large number of entities,
        tasks are much faster than using the API to query and loop over
        all objects matching a filter.
        
        Several different types of tasks are supported, including
        applying services to contacts, messages, or data rows;
        adding or removing contacts from a group; blocking or unblocking
        sending messages to a contact; updating a custom variable;
        deleting contacts, messages, or data rows; or exporting data to
        CSV.
        
        When using a task to apply a Custom Actions or Cloud Script API
        service (`apply_service_to_contacts`, `apply_service_to_rows`, or
        `apply_service_to_messages`),
        the `task` variable will be available within the service. The
        service can use custom variables on the task object (e.g. `task.vars.example`), such as
        to store aggregate statistics for the rows matching the filter.
        
        Arguments:
          - $options (associative array)
              * Required
            
            - task_type
                * Type of task to create. Each `task_type` applies to a certain type of entity (such
                    as a contact, message, or data row).
                    
                    Tasks for contacts:
                    
                    - `update_contact_var`
                    - `add_group_members`
                    - `remove_group_members`
                    - `set_conversation_status`
                    - `set_send_blocked`
                    - `apply_service_to_contacts`
                    - `delete_contacts`
                    - `export_contacts`
                    
                    Tasks for data rows:
                    
                    - `update_row_var`
                    - `apply_service_to_rows`
                    - `delete_rows`
                    - `export_rows`
                    
                    Tasks for messages:
                    
                    - `cancel_messages`
                    - `resend_messages`
                    - `retry_message_services`
                    - `apply_service_to_messages`
                    - `add_label`
                    - `remove_label`
                    - `update_message_var`
                    - `delete_messages`
                    - `export_messages`
                * Allowed values: update_contact_var, delete_contacts, add_group_members,
                    remove_group_members, set_conversation_status, set_send_blocked,
                    apply_service_to_contacts, update_row_var, delete_rows, apply_service_to_rows,
                    delete_messages, cancel_messages, resend_messages, retry_message_services,
                    apply_service_to_messages, add_label, remove_label, update_message_var,
                    export_messages, export_contacts, export_rows
                * Required
            
            - task_params (associative array)
                * Parameters applied to all matching rows (specific to `task_type`).
                    
                    **`apply_service_to_contacts`**,
                    **`apply_service_to_messages`**, **`apply_service_to_rows`**:
                    <table>
                    <tr><td> `service_id` </td> <td> The ID of the
                    service to apply (string) </td></tr>
                    <tr><td> `variables` </td> <td> Optional object
                    containing up to 25 temporary variable names and their corresponding values to set
                    when invoking the service. Values may be strings, numbers, or boolean (true/false).
                    String values may be up to 4096 bytes in length. Arrays and objects are not
                    supported. Within Custom Actions, each variable can be used like [[$name]] (with a
                    leading $ character and surrounded by double square brackets). Within a Cloud Script
                    API service or JavaScript action, each variable will be available as a global
                    JavaScript variable like $name (with a leading $ character). (object) </td></tr>
                    </table>
                    <br />
                    **`update_contact_var`**, **`update_message_var`**,
                    **`update_row_var`**:
                    <table>
                    <tr><td> `variable` </td> <td> The custom variable
                    name (string) </td></tr>
                    <tr><td> `value` </td> <td> The value to set
                    (string, boolean, float, null) </td></tr>
                    </table>
                    <br />
                    **`add_group_members`**, **`remove_group_members`**:
                    <table>
                    <tr><td> `group_id` </td> <td> The ID of the group
                    (string) </td></tr>
                    </table>
                    <br />
                    **`add_label`**, **`remove_label`**:
                    <table>
                    <tr><td> `label_id` </td> <td> The ID of the label
                    (string) </td></tr>
                    </table>
                    <br />
                    **`resend_messages`**:
                    <table>
                    <tr><td> `route_id` </td> <td> ID of the new route
                    to use, or null to use the original route (string) </td></tr>
                    </table>
                    <br />
                    **`set_send_blocked`**:
                    <table>
                    <tr><td> `send_blocked` </td> <td> `true` to block
                    sending messages, `false` to unblock sending messages (boolean) </td></tr>
                    </table>
                    <br />
                    **`set_conversation_status`**:
                    <table>
                    <tr><td> `conversation_status` </td> <td> "active",
                    "handled", or "closed" (string) </td></tr>
                    </table>
                    <br />
                    **`export_contacts`**, **`export_messages`**,
                    **`export_rows`**:
                    <table>
                    <tr><td>`storage_id` </td> <td> ID of a storage
                    backend where the CSV file will be saved. (string)
                    
                    Currently only AWS S3 is supported as a storage
                    backend.
                    This requires creating a S3 bucket in your own
                    AWS account, as well as an IAM user with access key and secret that has permission
                    to write to that bucket.
                    To configure your own S3 bucket as a storage
                    backend, contact support.
                    
                    Direct downloads are not supported when
                    exporting data via the API.
                    (string) </td></tr>
                    <tr><td>`filename` </td> <td> Path within the
                    storage backend where the CSV file will be saved </td></tr>
                    <tr><td>`column_ids` </td> <td> IDs of columns to
                    save in the CSV file. If not provided, all default columns will be saved. (array of
                    strings, optional) </td></tr>
                    </table>
                    <br />
                    **`delete_contacts`**, **`delete_messages`**,
                    **`delete_rows`**, **`cancel_messages`**, **`retry_message_services`**: <br />
                    No parameters.
            
            - filter_type
                * Type of filter defining the rows that the task is applied to.
                    
                    Each `filter_type` queries a certain type of
                    entity (such as contacts, messages, or data rows).
                    
                    In general, the `task_type` and the
                    `filter_type` must return the same type of entity; however, tasks applied to
                    contacts (other than `export_contacts`) can also be applied
                    when the filter returns entities that are
                    associated with a contact, such as messages or data rows. (Note that in this case,
                    it is possible for the task to be applied multiple times to an individual contact if
                    multiple messages or data rows are associated with the same contact.)
                * Allowed values: query_contacts, contact_ids, query_rows, row_ids, query_messages,
                    message_ids
                * Required
            
            - filter_params (associative array)
                * Parameters defining the rows that the task is applied to (specific to
                    `filter_type`).
                    
                    **`query_contacts`**: <br />
                    The same filter parameters as used by
                    [project.queryContacts](#Project.queryContacts). If you want to apply the task to
                    all contacts, use the parameters {"all": true}.
                    
                    **`contact_ids`**:
                    <table>
                    <tr><td> `contact_ids` </td> <td> IDs of up to 100
                    contacts to apply this task to (array of strings) </td></tr>
                    </table>
                    
                    **`query_messages`**: <br />
                    The same filter parameters as used by
                    [project.queryMessages](#Project.queryMessages). If you want to apply the task to
                    all messages, use the parameters {"all": true}.
                    
                    **`message_ids`**:
                    <table>
                    <tr><td> `message_ids` </td> <td> IDs of up to 100
                    messages to apply this task to (array of strings) </td></tr>
                    </table>
                    
                    **`query_rows`**: <br />
                    The same filter parameters as used by
                    [table.queryRows](#DataTable.queryRows). If you want to apply the task to all rows
                    in the table, use the parameters {"all": true}.
                    
                    **`row_ids`**:
                    <table>
                    <tr><td> `row_ids` </td> <td> IDs of up to 100 data
                    rows to apply this task to (array of strings) </td></tr>
                    </table>
                * Required
            
            - table_id (string, max 34 characters)
                * ID of the data table this task is applied to (if applicable).
                    
                    Required if filter_type is `query_rows` or `row_ids`.
            
            - vars (associative array)
                * Initial custom variables to set for the task.
                    
                    If the task applies a service, the service can read
                    and write custom variables on the task object (e.g. `task.vars.example`), such as
                    to store aggregate statistics for the rows matching
                    the filter.
          
        Returns:
            Telerivet_Task
    */
    function createTask($options)
    {
        return new Telerivet_Task($this->_api, $this->_api->doRequest("POST", "{$this->getBaseApiPath()}/tasks", $options));
    }

    /**
        $project->queryTasks($options)
        
        Queries batch tasks within the given project.
        
        Arguments:
          - $options (associative array)
            
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
            Telerivet_APICursor (of Telerivet_Task)
    */
    function queryTasks($options = null)
    {
        return $this->_api->newApiCursor('Telerivet_Task', "{$this->getBaseApiPath()}/tasks", $options);
    }

    /**
        $project->getTaskById($id)
        
        Retrieves the task with the given ID.
        
        Arguments:
          - $id
              * ID of the task
              * Required
          
        Returns:
            Telerivet_Task
    */
    function getTaskById($id)
    {
        return new Telerivet_Task($this->_api, $this->_api->doRequest("GET", "{$this->getBaseApiPath()}/tasks/{$id}"));
    }

    /**
        $project->initTaskById($id)
        
        Initializes the task with the given ID without making an API request.
        
        Arguments:
          - $id
              * ID of the task
              * Required
          
        Returns:
            Telerivet_Task
    */
    function initTaskById($id)
    {
        return new Telerivet_Task($this->_api, array('project_id' => $this->id, 'id' => $id), false);
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
                * Number of results returned per page (max 500)
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
                * Number of results returned per page (max 500)
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
                * Allowed values: sms, mms, ussd, call, service
            
            - time_created (UNIX timestamp)
                * Filter scheduled messages by time_created
                * Allowed modifiers: time_created[min], time_created[max]
            
            - next_time (UNIX timestamp)
                * Filter scheduled messages by next_time
                * Allowed modifiers: next_time[min], next_time[max], next_time[exists]
            
            - sort
                * Sort the results based on a field
                * Allowed values: default, next_time
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
                * Allowed values: message, call, ussd_session, row, contact, project
            
            - sort
                * Sort the results based on a field
                * Allowed values: default, priority, name
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
                * Number of results returned per page (max 500)
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
        $project->queryAirtimeTransactions($options)
        
        Returns information about each airtime transaction.
        
        Arguments:
          - $options (associative array)
            
            - time_created[min] (UNIX timestamp)
                * Filter transactions created on or after a particular time
            
            - time_created[max] (UNIX timestamp)
                * Filter transactions created before a particular time
            
            - contact_id
                * Filter transactions sent to a particular contact
            
            - to_number
                * Filter transactions sent to a particular phone number
            
            - service_id
                * Filter transactions sent by a particular service
            
            - status
                * Filter transactions by status
                * Allowed values: pending, queued, processing, successful, failed, cancelled,
                    pending_payment, pending_approval
            
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
            Telerivet_APICursor (of Telerivet_AirtimeTransaction)
    */
    function queryAirtimeTransactions($options = null)
    {
        return $this->_api->newApiCursor('Telerivet_AirtimeTransaction', "{$this->getBaseApiPath()}/airtime_transactions", $options);
    }

    /**
        $project->getAirtimeTransactionById($id)
        
        Gets an airtime transaction by ID
        
        Arguments:
          - $id
              * ID of the airtime transaction
              * Required
          
        Returns:
            Telerivet_AirtimeTransaction
    */
    function getAirtimeTransactionById($id)
    {
        return new Telerivet_AirtimeTransaction($this->_api, $this->_api->doRequest("GET", "{$this->getBaseApiPath()}/airtime_transactions/{$id}"));
    }

    /**
        $project->initAirtimeTransactionById($id)
        
        Initializes an airtime transaction by ID without making an API request.
        
        Arguments:
          - $id
              * ID of the airtime transaction
              * Required
          
        Returns:
            Telerivet_AirtimeTransaction
    */
    function initAirtimeTransactionById($id)
    {
        return new Telerivet_AirtimeTransaction($this->_api, array('project_id' => $this->id, 'id' => $id), false);
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