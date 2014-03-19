<?php          
/**
    Telerivet_MobileMoneyReceipt
    
    Fields:
    
      - id (string, max 34 characters)
          * Telerivet's internal ID for the receipt
          * Read-only
      
      - tx_id
          * Transaction ID from the receipt
          * Read-only
      
      - tx_type
          * Type of mobile money transaction
          * Read-only
      
      - currency
          * ISO 4217 Currency code for transaction (amount, balance, and fee are expressed in units
              of this currency); see <http://en.wikipedia.org/wiki/ISO_4217>
          * Read-only
      
      - amount (number)
          * Amount of this transaction; positive numbers indicate money added to your account,
              negative numbers indicate money removed from your account
          * Read-only
      
      - balance (number)
          * The current balance of your mobile money account (null if not available)
          * Read-only
      
      - fee (number)
          * The transaction fee charged by the mobile money system (null if not available)
          * Read-only
      
      - name
          * The name of the other person in the transaction (null if not available)
          * Read-only
      
      - phone_number
          * The phone number of the other person in the transaction (null if not available)
          * Read-only
      
      - time_created (UNIX timestamp)
          * The time this receipt was created in Telerivet
          * Read-only
      
      - other_tx_id
          * The other transaction ID listed in the receipt (e.g. the transaction ID for a reversed
              transaction)
          * Read-only
      
      - content
          * The raw content of the mobile money receipt
          * Read-only
      
      - provider_id
          * Telerivet's internal ID for the mobile money provider
          * Read-only
      
      - contact_id
          * ID of the contact associated with the name/phone_number on the receipt
          * Read-only
      
      - phone_id
          * ID of the phone that received the receipt
          * Read-only
      
      - message_id
          * ID of the message corresponding to the receipt
          * Read-only
      
      - project_id
          * ID of the project this receipt belongs to
          * Read-only
      
*/
class Telerivet_MobileMoneyReceipt extends Telerivet_Entity
{
    /**
        $receipt->delete()
        
        Deletes this receipt.
        
    */
    function delete()
    {
        $this->_api->doRequest("DELETE", "{$this->getBaseApiPath()}");
    }

    function getBaseApiPath()
    {
        return "/projects/{$this->project_id}/receipts/{$this->receipt_id}";
    }
}
