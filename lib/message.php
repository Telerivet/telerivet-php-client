<?php
/**
    Telerivet_Message
    
    Represents a single message.
    
    Fields:
    
      - id (string, max 34 characters)
          * ID of the message
          * Read-only
      
      - phone_id (string, max 34 characters)
          * ID of the phone that sent or received the message
          * Read-only
      
      - contact_id (string, max 34 characters)
          * ID of the contact that sent or received the message
          * Read-only
      
      - direction
          * Direction of the message: incoming messages are sent from one of your contacts to your
              phone; outgoing messages are sent from your phone to one of your contacts
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
          * The time that the message was reported to have been sent (null for incoming messages and
              messages that have not yet been sent)
          * Read-only
      
      - from_number (string)
          * The phone number that the message originated from (your number for outgoing messages,
              the contact's number for incoming messages)
          * Read-only
      
      - to_number (string)
          * The phone number that the message was sent to (your number for incoming messages, the
              contact's number for outgoing messages)
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
      
      - mms_parts (array)
          * List of MMS parts for this message (null for non-MMS messages).
              Only included when retrieving an individual message, not when
              querying a list of messages.
              
              Each MMS part in the list has the following properties:
              
              - cid: MMS content-id
              - type: MIME type
              - filename: original filename
              - size (int): number of bytes
              - url: URL where the content for this part is stored (secret but
              publicly accessible, so you could link/embed it in a web page without having to re-host it
              yourself)
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
        $this->_loadData();
        return isset($this->_label_ids_set[$label->id]);
    }
    
    /**
        $message->addLabel($label)
        
        Adds a label to this message.
        
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
        
        Removes a label from this message.
        
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
