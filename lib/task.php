<?php
/**
    Telerivet_Task
    
    Represents an asynchronous task that is applied to all entities matching a filter.
    
    Tasks include services applied to contacts, messages, or data rows; adding
    or removing contacts from a group; blocking or unblocking sending messages to a contact;
    updating a custom variable; deleting contacts, messages, or data rows; or
    exporting data to CSV.
    
    Fields:
    
      - id (string, max 34 characters)
          * ID of the task
          * Read-only
      
      - task_type (string)
          * The task type
          * Read-only
      
      - task_params (associative array)
          * Parameters applied to all matching rows (specific to `task_type`). See
              [project.createTask](#Project.createTask).
          * Read-only
      
      - filter_type
          * Type of filter defining the rows that the task is applied to
          * Read-only
      
      - filter_params (associative array)
          * Parameters defining the rows that the task is applied to (specific to
              `filter_type`). See [project.createTask](#Project.createTask).
          * Read-only
      
      - time_created (UNIX timestamp)
          * Time the task was created in Telerivet
          * Read-only
      
      - time_active (UNIX timestamp)
          * Time Telerivet started executing the task
          * Read-only
      
      - time_complete (UNIX timestamp)
          * Time Telerivet finished executing the task
          * Read-only
      
      - total_rows (int)
          * The total number of rows matching the filter (null if not known)
          * Read-only
      
      - current_row (int)
          * The number of rows that have been processed so far
          * Read-only
      
      - status (string)
          * The current status of the task
          * Allowed values: created, queued, active, complete, failed, cancelled
          * Read-only
      
      - vars (associative array)
          * Custom variables stored for this task
          * Read-only
      
      - table_id (string, max 34 characters)
          * ID of the data table this task is applied to (if applicable)
          * Read-only
      
      - user_id (string, max 34 characters)
          * ID of the Telerivet user who created the task (if applicable)
          * Read-only
      
      - project_id
          * ID of the project this task belongs to
          * Read-only
*/
class Telerivet_Task extends Telerivet_Entity
{
    /**
        $task->cancel()
        
        Cancels a task that is not yet complete.
        
        Returns:
            Telerivet_Task
    */
    function cancel()
    {
        return new Telerivet_Task($this->_api, $this->_api->doRequest("POST", "{$this->getBaseApiPath()}/cancel"));
    }

    function getBaseApiPath()
    {
        return "/projects/{$this->project_id}/tasks/{$this->id}";
    }
}
