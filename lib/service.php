<?php

/**
    Telerivet_Service
    
    Represents an automated service on Telerivet, for example a poll, auto-reply, webhook
    service, etc.
    
    A service, generally, defines some automated behavior that can be
    invoked/triggered in a particular context, and may be invoked either manually or when a
    particular event occurs.
    
    Most commonly, services work in the context of a particular message, when
    the message is originally received by Telerivet.
    
    Fields:
    
      - id (string, max 34 characters)
          * ID of the service
          * Read-only
      
      - name
          * Name of the service
          * Updatable via API
      
      - service_type
          * Type of the service.
          * Read-only
      
      - active (bool)
          * Whether the service is active or inactive. Inactive services are not automatically
              triggered and cannot be invoked via the API.
          * Updatable via API
      
      - priority (int)
          * A number that determines the order that services are triggered when a particular
              event occurs (smaller numbers are triggered first). Any service can determine whether
              or not execution "falls-through" to subsequent services (with larger priority values)
              by setting the return_value variable within Telerivet's Rules Engine.
          * Updatable via API
      
      - contexts (associative array)
          * A key/value map where the keys are the names of contexts supported by this service
              (e.g. message, contact), and the values are themselves key/value maps where the keys
              are event names and the values are all true. (This structure makes it easy to test
              whether a service can be invoked for a particular context and event.)
          * Read-only
      
      - vars (associative array)
          * Custom variables stored for this service. Variable names may be up to 32 characters
              in length and can contain the characters a-z, A-Z, 0-9, and _.
              Values may be strings, numbers, or boolean (true/false).
              String values may be up to 4096 bytes in length when encoded as UTF-8.
              Up to 100 variables are supported per object.
              Setting a variable to null will delete the variable.
          * Updatable via API
      
      - project_id
          * ID of the project this service belongs to
          * Read-only
      
      - response_table_id
          * ID of the data table where responses to this service will be stored
          * Updatable via API
      
      - phone_ids
          * IDs of phones (basic routes) associated with this service, or null if the service is
              associated with all routes. Only applies for service types that handle incoming
              messages, voice calls, or USSD sessions.
          * Updatable via API
      
      - apply_mode
          * If apply_mode is `unhandled`, the service will not be triggered if another service
              has already handled the incoming message. If apply_mode is `always`, the service will
              always be triggered regardless of other services. Only applies to services that handle
              incoming messages.
          * Allowed values: always, unhandled
          * Updatable via API
      
      - contact_number_filter
          * If contact_number_filter is `long_number`, this service will only be triggered if
              the contact phone number has at least 7 digits (ignoring messages from shortcodes and
              alphanumeric senders). If contact_number_filter is `all`, the service will be
              triggered for all contact phone numbers.  Only applies to services that handle
              incoming messages.
          * Allowed values: long_number, all
          * Updatable via API
      
      - show_action (bool)
          * Whether this service is shown in the 'Actions' menu within the Telerivet web app
              when the service is active. Only provided for service types that are manually
              triggered.
          * Updatable via API
      
      - direction
          * Determines whether the service handles incoming voice calls, outgoing voice calls,
              or both. Only applies to services that handle voice calls.
          * Allowed values: incoming, outgoing, both
          * Updatable via API
      
      - webhook_url
          * URL that a third-party can invoke to trigger this service. Only provided for
              services that are triggered by a webhook request.
          * Read-only
 */
class Telerivet_Service extends Telerivet_Entity
{
    /**
        $service->invoke($options)
        
        Manually invoke this service in a particular context.
        
        For example, to send a poll to a particular contact (or resend the
        current question), you can invoke the poll service with context=contact, and `contact_id` as
        the ID of the contact to send the poll to. (To trigger a service to multiple contacts, use
        [project.sendBroadcast](#Project.sendBroadcast). To schedule a service in the future, use
        [project.scheduleMessage](#Project.scheduleMessage).)
        
        Or, to manually apply a service for an incoming message, you can
        invoke the service with `context`=`message`, `event`=`incoming_message`, and `message_id` as
        the ID of the incoming message. (This is normally not necessary, but could be used if you
        want to override Telerivet's standard priority-ordering of services.)
        
        Arguments:
          - $options (associative array)
              * Required
            
            - context
                * The name of the context in which this service is invoked
                * Allowed values: message, call, ussd_session, row, contact, project
                * Required
            
            - event
                * The name of the event that is triggered (must be supported by this service)
                * Default: default
            
            - message_id
                * The ID of the message this service is triggered for
                * Required if context is 'message'
            
            - contact_id
                * The ID of the contact this service is triggered for (either `contact_id` or
                    `phone_number` is required if `context` is 'contact')
            
            - phone_number
                * The phone number of the contact this service is triggered for (either `contact_id`
                    or `phone_number` is required if `context` is 'contact'). If no  contact exists with
                    this phone number, a new contact will be created.
            
            - variables (associative array)
                * Object containing up to 25 temporary variable names and their corresponding values
                    to set when invoking the service. Values may be strings, numbers, or boolean
                    (true/false). String values may be up to 4096 bytes in length. Arrays and objects
                    are not supported. Within Custom Actions, each variable can be used like `[[$name]]`
                    (with a leading `$` character and surrounded by double square brackets). Within a
                    Cloud Script API service or JavaScript action, each variable will be available as a
                    global JavaScript variable like `$name` (with a leading `$` character).
            
            - route_id
                * The ID of the phone or route that the service will use for sending messages by
                    default
            
            - async (bool)
                * If set to true, the service will be invoked asynchronously. By default, queued
                    services will be invoked one at a time for each project.
          
        Returns:
            (associative array)
              - return_value (any)
                  * Return value of the service. May be any JSON type (boolean, number, string,
                      array, object, or null). (Undefined if async=true.)
              
              - log_entries (array)
                  * Array of log entry strings generated by the service. (Undefined if async=true.)
              
              - errors (array)
                  * Array of error message strings generated by the service. (Undefined if
                      async=true.)
              
              - sent_messages (array of objects)
                  * Array of messages sent by the service.
              
              - airtime_transactions (array of objects)
                  * Array of airtime transactions sent by the service (Undefined if async=true.)
     */
    function invoke($options)
    {
        $invoke_result = $this->_api->doRequest('POST', $this->getBaseApiPath() . '/invoke', $options);
        
        if ($invoke_result['sent_messages'])
        {
            $sent_messages = array();
            foreach ($invoke_result['sent_messages'] as $sent_message_data)
            {
                $sent_messages[] = new Telerivet_Message($this->api, $sent_message_data);
            }
            $invoke_result['sent_messages'] = $sent_messages;
        }        
        return $invoke_result;
    }    
    
    /**
        $service->queryContactStates($options)
        
        Query the current states of contacts for this service.
        
        Arguments:
          - $options (associative array)
            
            - id
                * Filter states by id
                * Allowed modifiers: id[ne], id[prefix], id[not_prefix], id[gte], id[gt], id[lt],
                    id[lte]
            
            - vars (associative array)
                * Filter states by value of a custom variable (e.g. vars[email], vars[foo], etc.)
                * Allowed modifiers: vars[foo][ne], vars[foo][prefix], vars[foo][not_prefix],
                    vars[foo][gte], vars[foo][gt], vars[foo][lt], vars[foo][lte], vars[foo][min],
                    vars[foo][max], vars[foo][exists]
            
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
    function queryContactStates($options = null)
    {
        return $this->_api->newApiCursor('Telerivet_ContactServiceState', "{$this->getBaseApiPath()}/states", $options);
    }

    /**
        $service->getContactState($contact)
        
        Gets the current state for a particular contact for this service.
        
        If the contact doesn't already have a state, this method will return
        a valid state object with id=null. However this object would not be returned by
        queryContactStates() unless it is saved with a non-null state id.
        
        Arguments:
          - $contact (Telerivet_Contact)
              * The contact whose state you want to retrieve.
              * Required
          
        Returns:
            Telerivet_ContactServiceState
     */
    function getContactState($contact)
    {
        return new Telerivet_ContactServiceState($this->_api, $this->_api->doRequest('GET', $this->getBaseApiPath() . '/states/' . $contact->id));        
    }
    
    /**
        $service->setContactState($contact, $options)
        
        Initializes or updates the current state for a particular contact for the given service. If
        the state id is null, the contact's state will be reset.
        
        Arguments:
          - $contact (Telerivet_Contact)
              * The contact whose state you want to update.
              * Required
          
          - $options (associative array)
              * Required
            
            - id (string, max 63 characters)
                * Arbitrary string representing the contact's current state for this service, e.g.
                    'q1', 'q2', etc.
                * Required
            
            - vars (associative array)
                * Custom variables stored for this contact's state. Variable names may be up to 32
                    characters in length and can contain the characters a-z, A-Z, 0-9, and _.
                    Values may be strings, numbers, or boolean (true/false).
                    String values may be up to 4096 bytes in length when encoded as UTF-8.
                    Up to 100 variables are supported per object.
                    Setting a variable to null will delete the variable.
          
        Returns:
            Telerivet_ContactServiceState
     */
    function setContactState($contact, $options)
    {
        return new Telerivet_ContactServiceState($this->_api, $this->_api->doRequest('POST', $this->getBaseApiPath() . '/states/' . $contact->id, $options));
    }    

    /**
        $service->resetContactState($contact)
        
        Resets the current state for a particular contact for the given service.
        
        Arguments:
          - $contact (Telerivet_Contact)
              * The contact whose state you want to reset.
              * Required
          
        Returns:
            Telerivet_ContactServiceState
     */
    function resetContactState($contact)
    {
        return new Telerivet_ContactServiceState($this->_api, $this->_api->doRequest('DELETE', $this->getBaseApiPath() . '/states/' . $contact->id));        
    }

    /**
        $service->getConfig()
        
        Gets configuration specific to the type of automated service.
        
        Only certain types of services provide their configuration via the
        API.
        
        Returns:
            object
    */
    function getConfig()
    {
        return $this->_api->doRequest("GET", "{$this->getBaseApiPath()}/config");
    }

    /**
        $service->setConfig($options)
        
        Updates configuration specific to the type of automated service.
        
        Only certain types of services support updating their configuration
        via the API.
        
        Note: when updating a service of type custom_template_instance,
        the validation script will be invoked when calling this method.
        
        Arguments:
          - $options (associative array)
              * Configuration for this service type. See
                  [project.createService](#Project.createService) for available configuration options.
              * Required
          
        Returns:
            object
    */
    function setConfig($options)
    {
        return $this->_api->doRequest("POST", "{$this->getBaseApiPath()}/config", $options);
    }

    /**
        $service->save()
        
        Saves any fields or custom variables that have changed for this service.
    */
    function save()
    {
        parent::save();
    }

    /**
        $service->delete()
        
        Deletes this service.
    */
    function delete()
    {
        $this->_api->doRequest("DELETE", "{$this->getBaseApiPath()}");
    }

    function getBaseApiPath()
    {
        return "/projects/{$this->project_id}/services/{$this->id}";
    }
}
