<?php
/**
    Telerivet_DataTable
    
    Represents a custom data table that can store arbitrary rows.
    
    For example, poll services use data tables to store a row for each response.
    
    DataTables are schemaless -- each row simply stores custom variables. Each
    variable name is equivalent to a different "column" of the data table.
    Telerivet refers to these variables/columns as "fields", and automatically
    creates a new field for each variable name used in a row of the table.
    
    Fields:
    
      - id (string, max 34 characters)
          * ID of the data table
          * Read-only
      
      - name
          * Name of the data table
          * Updatable via API
      
      - num_rows (int)
          * Number of rows in the table
          * Read-only
      
      - show_add_row (bool)
          * Whether to allow adding or importing rows via the web app
          * Updatable via API
      
      - show_stats (bool)
          * Whether to show row statistics in the web app
          * Updatable via API
      
      - show_contact_columns (bool)
          * Whether to show 'Contact Name' and 'Phone Number' columns in the web app
          * Updatable via API
      
      - vars (associative array)
          * Custom variables stored for this data table
          * Updatable via API
      
      - project_id
          * ID of the project this data table belongs to
          * Read-only
*/
class Telerivet_DataTable extends Telerivet_Entity
{
    /**
        $table->queryRows($options)
        
        Queries rows in this data table.
        
        Arguments:
          - $options (associative array)
            
            - time_created (UNIX timestamp)
                * Filter data rows by the time they were created
                * Allowed modifiers: time_created[ne], time_created[min], time_created[max]
            
            - contact_id
                * Filter data rows associated with a particular contact
            
            - vars (associative array)
                * Filter data rows by value of a custom variable (e.g. vars[q1], vars[foo], etc.)
                * Allowed modifiers: vars[foo][exists], vars[foo][ne], vars[foo][prefix],
                    vars[foo][not_prefix], vars[foo][gte], vars[foo][gt], vars[foo][lt], vars[foo][lte],
                    vars[foo][min], vars[foo][max]
            
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
            Telerivet_APICursor (of Telerivet_DataRow)
    */
    function queryRows($options = null)
    {
        return $this->_api->newApiCursor('Telerivet_DataRow', "{$this->getBaseApiPath()}/rows", $options);
    }

    /**
        $table->createRow($options)
        
        Adds a new row to this data table.
        
        Arguments:
          - $options (associative array)
            
            - contact_id
                * ID of the contact that this row is associated with (if applicable)
            
            - from_number (string)
                * Phone number that this row is associated with (if applicable)
            
            - vars
                * Custom variables and values to set for this data row
          
        Returns:
            Telerivet_DataRow
    */
    function createRow($options = null)
    {
        return new Telerivet_DataRow($this->_api, $this->_api->doRequest("POST", "{$this->getBaseApiPath()}/rows", $options));
    }

    /**
        $table->getRowById($id)
        
        Retrieves the row in the given table with the given ID.
        
        Arguments:
          - $id
              * ID of the row
              * Required
          
        Returns:
            Telerivet_DataRow
    */
    function getRowById($id)
    {
        return new Telerivet_DataRow($this->_api, $this->_api->doRequest("GET", "{$this->getBaseApiPath()}/rows/{$id}"));
    }

    /**
        $table->initRowById($id)
        
        Initializes the row in the given table with the given ID, without making an API request.
        
        Arguments:
          - $id
              * ID of the row
              * Required
          
        Returns:
            Telerivet_DataRow
    */
    function initRowById($id)
    {
        return new Telerivet_DataRow($this->_api, array('project_id' => $this->project_id, 'table_id' => $this->id, 'id' => $id), false);
    }

    /**
        $table->getFields()
        
        Gets a list of all fields (columns) defined for this data table. The return value is an
        array of objects with the properties 'name', 'variable', 'type', 'order', 'readonly', and
        'lookup_key'. (Fields are automatically created any time a DataRow's 'vars' property is
        updated.)
        
        Returns:
            array
    */
    function getFields()
    {
        return $this->_api->doRequest("GET", "{$this->getBaseApiPath()}/fields");
    }

    /**
        $table->setFieldMetadata($variable, $options)
        
        Allows customizing how a field (column) is displayed in the Telerivet web app.
        
        Arguments:
          - $variable
              * The variable name of the field to create or update.
              * Required
          
          - $options (associative array)
            
            - name (string, max 64 characters)
                * Display name for the field
            
            - type (string)
                * Field type
                * Allowed values: text, long_text, number, boolean, email, url, audio, phone_number,
                    date, date_time, groups, route, select, buttons
            
            - order (int)
                * Order in which to display the field
            
            - readonly (bool)
                * Set to true to prevent editing the field in the Telerivet web app
          
        Returns:
            object
    */
    function setFieldMetadata($variable, $options = null)
    {
        return $this->_api->doRequest("POST", "{$this->getBaseApiPath()}/fields/{$variable}", $options);
    }

    /**
        $table->countRowsByValue($variable)
        
        Returns the number of rows for each value of a given variable. This can be used to get the
        total number of responses for each choice in a poll, without making a separate query for
        each response choice. The return value is an object mapping values to row counts, e.g.
        `{"yes":7,"no":3}`
        
        Arguments:
          - variable
              * Variable of field to count by.
              * Required
          
        Returns:
            object
    */
    function countRowsByValue($variable)
    {
        return $this->_api->doRequest("GET", "{$this->getBaseApiPath()}/count_rows_by_value", array('variable' => $variable));
    }

    /**
        $table->save()
        
        Saves any fields that have changed for this data table.
    */
    function save()
    {
        parent::save();
    }

    /**
        $table->delete()
        
        Permanently deletes the given data table, including all its rows
    */
    function delete()
    {
        $this->_api->doRequest("DELETE", "{$this->getBaseApiPath()}");
    }

    function getBaseApiPath()
    {
        return "/projects/{$this->project_id}/tables/{$this->id}";
    }
}
