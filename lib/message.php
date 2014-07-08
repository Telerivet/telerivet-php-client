<?php
/**
    Telerivet_Message
    
    Represents a single message.
    
    Fields:
    
      - id (string, max 34 characters)
          * ID of the message
          * Read-only
      
      - direction
          * Direction of the message: incoming messages are sent from one of your contacts to
              your phone; outgoing messages are sent from your phone to one of your contacts
          * Allowed values: incoming, outgoing
          * Read-only
      
      - status
          * Current status of the message
          * Allowed values: ignored, processing, received, sent, queued, failed, failed_queued,
              cancelled, delivered, not_delivered
          * Read-only
      
      - message_type
          * Type of the message
          * Allowed values: sms, mms, ussd, call
          * Read-only
      
      - source
          * How the message originated within Telerivet
          * Allowed values: phone, provider, web, api, service, webhook, scheduled
          * Read-only
      
      - time_created (UNIX timestamp)
          * The time that the message was created on Telerivet's servers
          * Read-only
      
      - time_sent (UNIX timestamp)
          * The time that the message was reported to have been sent (null for incoming messages
              and messages that have not yet been sent)
          * Read-only
      
      - from_number (string)
          * The phone number that the message originated from (your number for outgoing
              messages, the contact's number for incoming messages)
          * Read-only
      
      - to_number (string)
          * The phone number that the message was sent to (your number for incoming messages,
              the contact's number for outgoing messages)
          * Read-only
      
      - content (string)
          * The text content of the message (null for USSD messages and calls)
          * Read-only
      
      - starred (bool)
          * Whether this message is starred in Telerivet
          * Updatable via API
      
      - simulated (bool)
          * Whether this message is was simulated within Telerivet for testing (and not actually
              sent to or received by a real phone)
          * Read-only
      
      - label_ids (array)
          * List of IDs of labels applied to this message
          * Read-only
      
      - vars (associative array)
          * Custom variables stored for this message
          * Updatable via API
      
      - error_message
          * A description of the error encountered while sending a message. (This field is
              omitted from the API response if there is no error message.)
          * Updatable via API
      
      - external_id
          * The ID of this message from an external SMS gateway provider (e.g. Twilio or Nexmo),
              if available.
          * Read-only
      
      - price (number)
          * The price of this message, if known. By convention, message prices are negative.
          * Read-only
      
      - price_currency
          * The currency of the message price, if applicable.
          * Read-only
      
      - mms_parts (array)
          * A list of parts in the MMS message, the same as returned by the
              [getMMSParts](#Message.getMMSParts) method.
              
              Note: This property is only present when retrieving an individual
              MMS message by ID, not when querying a list of messages. In other cases, use
              [getMMSParts](#Message.getMMSParts).
          * Read-only
      
      - phone_id (string, max 34 characters)
          * ID of the phone that sent or received the message
          * Read-only
      
      - contact_id (string, max 34 characters)
          * ID of the contact that sent or received the message
          * Read-only
      
      - project_id
          * ID of the project this contact belongs to
          * Read-only
 */
class Telerivet_Message extends Telerivet_Entity
{
    private $_label_ids_set = array();
       
    /**
        $message->hasLabel($label)
        
        Returns true if this message has a particular label, false otherwise.
        
        Arguments:
          - $label (Telerivet_Label)
              * Required
          
        Returns:
            bool
     */
    function hasLabel($label)
    {
        $this->load();
        return isset($this->_label_ids_set[$label->id]);
    }
    
    /**
        $message->addLabel($label)
        
        Adds a label to the given message.
        
        Arguments:
          - $label (Telerivet_Label)
              * Required
     */
    function addLabel($label)
    {
        $this->_api->doRequest("PUT", "{$label->getBaseApiPath()}/messages/{$this->id}");               
        $this->_label_ids_set[$label->id] = true;        
    }
    
    /**
        $message->removeLabel($label)
        
        Removes a label from the given message.
        
        Arguments:
          - $label (Telerivet_Label)
              * Required
     */
    function removeLabel($label)
    {        
        $this->_api->doRequest("DELETE", "{$label->getBaseApiPath()}/messages/{$this->id}");               
        unset($this->_label_ids_set[$label->id]);
    }
    
    /**
        $message->getMMSParts()
        
        Retrieves a list of MMS parts for this message (empty for non-MMS messages).
        
        Each MMS part in the list is an object with the following
        properties:
        
        - cid: MMS content-id
        - type: MIME type
        - filename: original filename
        - size (int): number of bytes
        - url: URL where the content for this part is stored (secret but
        publicly accessible, so you could link/embed it in a web page without having to re-host it
        yourself)
        
        Returns:
            array
    */
    function getMMSParts()
    {
        return $this->_api->doRequest("GET", "{$this->getBaseApiPath()}/mms_parts");
    }

    /**
        $message->save()
        
        Saves any fields that have changed for this message.
    */
    function save()
    {
        parent::save();
    }

    /**
        $message->delete()
        
        Deletes this message.
    */
    function delete()
    {
        $this->_api->doRequest("DELETE", "{$this->getBaseApiPath()}");
    }

    function getBaseApiPath()
    {
        return "/projects/{$this->project_id}/messages/{$this->id}";
    }
    
    protected function _setData($data)
    {
        parent::_setData($data);
        
        if (isset($data['label_ids']) && is_array($data['label_ids']))
        {
            foreach ($data['label_ids'] as $label_id)
            {
                $this->_label_ids_set[$label_id] = true;
            }
        }
    }
}
