<?php
/**
    Telerivet_DataView
    
    Represents a view of a data table, defined by a saved filter on the table's rows.
    
    The view's filter conditions cannot be changed after the view is created
    (although the view can be renamed or deleted).
    
    Telerivet will automatically store the count of rows matching each data view
    once a day, making it possible to retrieve historical statistics for the number of rows
    matching the filter.
    
    Fields:
    
      - id (string, max 34 characters)
          * ID of the data view
          * Read-only
      
      - name
          * Name of the data view
          * Updatable via API
      
      - num_rows (int)
          * Number of rows matching the view filter. For performance reasons, this number may
              sometimes be out-of-date.
          * Read-only
      
      - filters (associative array)
          * Key-value pairs of conditions that rows must match to be included in the view, in
              the same format as the `filters` parameter of
              [table.createView](#DataTable.createView)
          * Read-only
      
      - table_id
          * ID of the table this view belongs to
          * Read-only
      
      - project_id
          * ID of the project this view belongs to
          * Read-only
      
      - url
          * URL to this view in the Telerivet web app
          * Read-only
*/
class Telerivet_DataView extends Telerivet_Entity
{
    /**
        $view->queryRows($options)
        
        Queries rows matching this view's filter conditions.
        
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
            Telerivet_APICursor (of Telerivet_DataRow)
    */
    function queryRows($options = null)
    {
        return $this->_api->newApiCursor('Telerivet_DataRow', "{$this->getBaseApiPath()}/rows", $options);
    }

    /**
        $view->save()
        
        Saves any fields that have changed for this view.
    */
    function save()
    {
        parent::save();
    }

    /**
        $view->delete()
        
        Permanently deletes the given view of a data table (does not delete any rows in the table)
    */
    function delete()
    {
        $this->_api->doRequest("DELETE", "{$this->getBaseApiPath()}");
    }

    function getBaseApiPath()
    {
        return "/projects/{$this->project_id}/tables/{$this->table_id}/views/{$this->id}";
    }
}
