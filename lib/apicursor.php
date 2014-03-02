<?php

/**
    An easy-to-use interface for interacting with API methods that return lists of entities,
    which may be split into multiple pages.
    
    Any method in the Telerivet PHP client library starting with the word 'query' returns a 
    Telerivet_APICursor object, which exposes the following methods:
    
        $cursor->count();
        $cursor->all();
        $cursor->hasNext();
        $cursor->next();
    
    Example Usage:
    -------------
    
    $groups = $project->queryGroups()->all();
    
    $num_contacts = $project->queryContacts()->count();
    
    $name_prefix = 'John';
    $cursor = $project->queryContacts(array(
        'name_prefix' => $name_prefix,
        'sort' => 'name',
    ));   
    
    echo "{$cursor->count()} contacts matching $name_prefix:\n";   
    while ($cursor->hasNext())
    {
        $contact = $cursor->next();
        echo "{$contact->name} {$contact->phone_number} {$contact->vars->birthdate}\n";        
    }
    
 */
class Telerivet_ApiCursor
{
    protected $api;
    protected $item_cls;
    protected $path;
    protected $params;
    
    function __construct($api, $item_cls, $path, $params)
    {
        $this->api = $api;
        $this->item_cls = $item_cls;
        $this->path = $path;
        $this->params = $params;
    }
    
    private $_count = -1;
    private $pos;
    private $data;
    private $truncated;
    private $next_marker;
    
    /* 
        Get the total count of entities matching the current query, without actually fetching the entities themselves.
        (much more efficient this way if you just need the count, as it only results in one API call)
        
        Returns:    
            int
     */
    function count()
    {
        if ($this->_count == -1)
        {
            $params = $this->params;
            $params['count'] = '1';
            
            $res = $this->api->doRequest("GET", $this->path, $params);
            $this->_count = (int)$res['count'];
        }
        return $this->_count;
    }
    
    /* 
        Get all entities matching the current query in an array
        
        Warning: This may result in an unbounded number of API calls!
        If the result set may be large (e.g., contacts or messages), consider using        
        hasNext() / next() instead.
        
        Returns:
            array
     */
    function all()
    {
        $items = array();
        
        while (true)
        {
            $item = $this->next();
            if (!isset($item))
            {
                break;
            }
            $items[] = $item;
        }        
        return $items;        
    }
    
    /* 
        Returns true if there are any more entities in the result set, false otherwise
        
        Returns:
            bool
     */
    function hasNext()
    {
        if (!isset($this->data))
        {
            $this->loadNextPage();
        }

        return $this->pos < sizeof($this->data) || $this->truncated;
    }
    
    /* 
        Returns the next entity in the result set
        
        Returns:
            Telerivet_Entity
     */
    function next()
    {
        if (!isset($this->data) || ($this->pos >= sizeof($this->data) && $this->truncated))
        {
            $this->loadNextPage();
        }
        
        if ($this->pos < sizeof($this->data))
        {
            $item_data = $this->data[$this->pos];
            $this->pos++;            
            $cls = $this->item_cls;
            return new $cls($this->api, $item_data, true);
        }
        else
        {
            return null;
        }
    }
    
    private function loadNextPage()
    {
        $request_params = $this->params;
                
        if (isset($this->next_marker))
        {
            $request_params['marker'] = $this->next_marker;
        }
        
        $response = $this->api->doRequest("GET", $this->path, $request_params);
        
        $this->data = $response['data'];
        $this->truncated = $response['truncated'];
        $this->next_marker = $response['next_marker'];
        $this->pos = 0;
    }    
}
