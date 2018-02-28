<?php
/**
    Telerivet_DataRow
    
    Represents a row in a custom data table.
    
    For example, each response to a poll is stored as one row in a data table.
    If a poll has a question with ID 'q1', the verbatim response to that question would be
    stored in $row->vars->q1, and the response code would be stored in $row->vars->q1_code.
    
    Each custom variable name within a data row corresponds to a different
    column/field of the data table.
    
    Fields:
    
      - id (string, max 34 characters)
          * ID of the data row
          * Read-only
      
      - contact_id
          * ID of the contact this row is associated with (or null if not associated with any
              contact)
          * Updatable via API
      
      - from_number (string)
          * Phone number that this row is associated with (or null if not associated with any
              phone number)
          * Updatable via API
      
      - vars (associative array)
          * Custom variables stored for this data row
          * Updatable via API
      
      - time_created (UNIX timestamp)
          * The time this row was created in Telerivet
          * Read-only
      
      - time_updated (UNIX timestamp)
          * The time this row was last updated in Telerivet
          * Read-only
      
      - table_id
          * ID of the table this data row belongs to
          * Read-only
      
      - project_id
          * ID of the project this data row belongs to
          * Read-only
*/
class Telerivet_DataRow extends Telerivet_Entity
{
    /**
        $row->save()
        
        Saves any fields or custom variables that have changed for this data row.
    */
    function save()
    {
        parent::save();
    }

    /**
        $row->delete()
        
        Deletes this data row.
    */
    function delete()
    {
        $this->_api->doRequest("DELETE", "{$this->getBaseApiPath()}");
    }

    function getBaseApiPath()
    {
        return "/projects/{$this->project_id}/tables/{$this->table_id}/rows/{$this->id}";
    }
}
