<?php
/**
    Telerivet_Route
    
    Represents a custom route that can be used to send messages via one or more basic routes
    (phones).
    
    Custom Routes were formerly referred to simply as "Routes" within Telerivet. API methods,
    parameters, and properties related to Custom Routes continue to use the term "Route" to
    maintain backwards compatibility.
    
    Custom routing rules can currently only be configured via Telerivet's web
    UI.
    
    Fields:
    
      - id (string, max 34 characters)
          * Telerivet's internal ID for the route
          * Read-only
      
      - name
          * The name of the route
          * Updatable via API
      
      - vars (associative array)
          * Custom variables stored for this route
          * Updatable via API
      
      - project_id
          * ID of the project this route belongs to
          * Read-only
*/
class Telerivet_Route extends Telerivet_Entity
{
    /**
        $route->save()
        
        Saves any fields or custom variables that have changed for this route.
    */
    function save()
    {
        parent::save();
    }

    function getBaseApiPath()
    {
        return "/projects/{$this->project_id}/routes/{$this->id}";
    }
}
