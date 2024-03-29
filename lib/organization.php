<?php
/**
    Telerivet_Organization
    
    Represents a Telerivet organization.
    
    Fields:
    
      - id (string, max 34 characters)
          * ID of the organization
          * Read-only
      
      - name
          * Name of the organization
          * Updatable via API
      
      - timezone_id
          * Billing quota time zone ID; see [List of tz database time zones Wikipedia
              article](http://en.wikipedia.org/wiki/List_of_tz_database_time_zones).
          * Updatable via API
*/
class Telerivet_Organization extends Telerivet_Entity
{
    /**
        $organization->createProject($options)
        
        Creates a new project.
        
        Some project settings are not currently possible to configure via
        the API, and can only be edited via the web app after the project is created.
        
        Arguments:
          - $options (associative array)
              * Required
            
            - name (string)
                * Name of the project to create, which must be unique in the organization.
                * Required
            
            - timezone_id
                * Default TZ database timezone ID; see [List of tz database time zones Wikipedia
                    article](http://en.wikipedia.org/wiki/List_of_tz_database_time_zones). This timezone
                    is used when computing statistics by date.
            
            - url_slug
                * Unique string used as a component of the project's URL in the Telerivet web app.
                    If not provided, a URL slug will be generated automatically.
            
            - auto_create_contacts (bool)
                * If true, a contact will be automatically created for each unique phone number that
                    a message is sent to or received from. If false, contacts will not automatically be
                    created (unless contact information is modified by an automated service). The
                    Conversations tab in the web app will only show messages that are associated with a
                    contact.
                * Default: 1
            
            - vars
                * Custom variables and values to set for this project
          
        Returns:
            Telerivet_Project
    */
    function createProject($options)
    {
        return new Telerivet_Project($this->_api, $this->_api->doRequest("POST", "{$this->getBaseApiPath()}/projects", $options));
    }

    /**
        $organization->save()
        
        Saves any fields that have changed for this organization.
    */
    function save()
    {
        parent::save();
    }

    /**
        $organization->getBillingDetails()
        
        Retrieves information about the organization's service plan and account balance.
        
        Returns:
            (associative array)
              - balance (string)
                  * Prepaid account balance
              
              - balance_currency (string)
                  * Currency of prepaid account balance
              
              - plan_name (string)
                  * Name of service plan
              
              - plan_price (string)
                  * Price of service plan
              
              - plan_currency (string)
                  * Currency of service plan price
              
              - plan_rrule (string)
                  * Service plan recurrence rule (e.g. FREQ=MONTHLY or FREQ=YEARLY)
              
              - plan_paid (boolean)
                  * true if the service plan has been paid for the current billing interval; false
                      if it is unpaid (free plans are considered paid)
              
              - plan_start_time (UNIX timestamp)
                  * Time when the current billing interval started
              
              - plan_end_time (UNIX timestamp)
                  * Time when the current billing interval ends
              
              - plan_suspend_time (UNIX timestamp)
                  * Time when the account will be suspended, if the plan remains unpaid after
                      `plan_end_time` (may be null)
              
              - plan_limits (associative array)
                  * Object describing the limits associated with the current service plan. The
                      object contains the following keys: `phones`, `projects`, `active_services`,
                      `users`, `contacts`, `messages_day`, `stored_messages`, `data_rows`,
                      `api_requests_day`. The values corresponding to each key are integers, or null.
              
              - recurring_billing_enabled (boolean)
                  * True if recurring billing is enabled, false otherwise
              
              - auto_refill_enabled (boolean)
                  * True if auto-refill is enabled, false otherwise
    */
    function getBillingDetails()
    {
        $data = $this->_api->doRequest("GET", "{$this->getBaseApiPath()}/billing");
        return $data;
    }

    /**
        $organization->getUsage($usage_type)
        
        Retrieves the current usage count associated with a particular service plan limit. Available
        usage types are `phones`, `projects`, `users`, `contacts`, `messages_day`,
        `stored_messages`, `data_rows`, and `api_requests_day`.
        
        Arguments:
          - $usage_type
              * Usage type.
              * Required
          
        Returns:
            int
    */
    function getUsage($usage_type)
    {
        return $this->_api->doRequest("GET", "{$this->getBaseApiPath()}/usage/{$usage_type}");
    }

    /**
        $organization->getMessageStats($options)
        
        Retrieves statistics about messages sent or received via Telerivet. This endpoint returns
        historical data that is computed shortly after midnight each day in the project's time zone,
        and does not contain message statistics for the current day.
        
        Arguments:
          - $options (associative array)
              * Required
            
            - start_date (string)
                * Start date of message statistics, in YYYY-MM-DD format
                * Required
            
            - end_date (string)
                * End date of message statistics (inclusive), in YYYY-MM-DD format
                * Required
            
            - rollup (string)
                * Date interval to group by
                * Allowed values: day, week, month, year, all
                * Default: day
            
            - properties (string)
                * Comma separated list of properties to group by
                * Allowed values: org_id, org_name, org_industry, project_id, project_name, user_id,
                    user_email, user_name, phone_id, phone_name, phone_type, direction, source, status,
                    network_code, network_name, message_type, service_id, service_name, simulated, link
            
            - metrics (string)
                * Comma separated list of metrics to return (summed for each distinct value of the
                    requested properties)
                * Allowed values: count, num_parts, duration, price
                * Required
            
            - currency (string)
                * Three-letter ISO 4217 currency code used when returning the 'price' field. If the
                    original price was in a different currency, it will be converted to the requested
                    currency using the approximate current exchange rate.
                * Default: USD
            
            - filters (associative array)
                * Key-value pairs of properties and corresponding values; the returned statistics
                    will only include messages where the property matches the provided value. Only the
                    following properties are supported for filters: `user_id`, `phone_id`, `direction`,
                    `source`, `status`, `service_id`, `simulated`, `message_type`, `network_code`
          
        Returns:
            (associative array)
              - intervals (array)
                  * List of objects representing each date interval containing at least one message
                      matching the filters.
                      Each object has the following properties:
                      
                      <table>
                      <tr><td> start_time </td> <td> The UNIX timestamp of the start
                      of the interval (int) </td></tr>
                      <tr><td> end_time </td> <td> The UNIX timestamp of the end of
                      the interval, exclusive (int) </td></tr>
                      <tr><td> start_date </td> <td> The date of the start of the
                      interval in YYYY-MM-DD format (string) </td></tr>
                      <tr><td> end_date </td> <td> The date of the end of the
                      interval in YYYY-MM-DD format, inclusive (string) </td></tr>
                      <tr><td> groups </td> <td> Array of groups for each
                      combination of requested property values matching the filters (array)
                      <br /><br />
                      Each object has the following properties:
                      <table>
                      <tr><td> properties </td> <td> An object of key/value
                      pairs for each distinct value of the requested properties (object) </td></tr>
                      <tr><td> metrics </td> <td> An object of key/value pairs
                      for each requested metric (object) </td></tr>
                      </table>
                      </td></tr>
                      </table>
    */
    function getMessageStats($options)
    {
        $data = $this->_api->doRequest("GET", "{$this->getBaseApiPath()}/message_stats", $options);
        return $data;
    }

    /**
        $organization->queryProjects($options)
        
        Queries projects in this organization.
        
        Arguments:
          - $options (associative array)
            
            - name
                * Filter projects by name
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
            Telerivet_APICursor (of Telerivet_Project)
    */
    function queryProjects($options = null)
    {
        return $this->_api->newApiCursor('Telerivet_Project', "{$this->getBaseApiPath()}/projects", $options);
    }

    function getBaseApiPath()
    {
        return "/organizations/{$this->id}";
    }
}
