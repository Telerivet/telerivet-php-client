<?php

/**
    Telerivet_APICursor
    
    An easy-to-use interface for interacting with API methods that return collections of objects
    that may be split into multiple pages of results.
    
    Using the APICursor, you can easily iterate over query results without
    having to manually fetch each page of results.
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
        'name[prefix]' => $name_prefix,
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
        if (!isset($params))
        {
            $params = array();
        }
        if (isset($params['count']))
        {
            throw new Telerivet_Exception("Cannot construct Telerivet_ApiCursor with 'count' parameter. Call the count() method instead.");
        }

        $this->api = $api;
        $this->item_cls = $item_cls;
        $this->path = $path;
        $this->params = $params;
    }

    private $_limit;
    private $offset = 0;
    private $_count = -1;
    private $pos;
    private $data;
    private $truncated;
    private $next_marker;

    /*
        $cursor->count()
        
        Returns the total count of entities matching the current query, without actually fetching
        the entities themselves.
        
        This is much more efficient than all() if you only need the count,
        as it only results in one API call, regardless of the number of entities matched by the
        query.
        
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
        $cursor->limit($limit)
        
        Limits the maximum number of entities fetched by this query.
        
        By default, iterating over the cursor will automatically fetch
        additional result pages as necessary. To prevent fetching more objects than you need, call
        this method to set the maximum number of objects retrieved from the API.
        
        Arguments:
          - $limit (int)
              * The maximum number of entities to fetch from the server (may require multiple API
                  calls if greater than 200)
              * Required
          
        Returns:
            the current APICursor object
     */
    function limit($limit)
    {
        $this->_limit = $limit;
        return $this;
    }

    /*
        $cursor->all()
        
        Get all entities matching the current query in an array.
        
        Warning: This may result in an unbounded number of API calls! If the
        result set may be large (e.g., contacts or messages), consider using hasNext() / next()
        instead.
        
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
        $cursor->hasNext()
        
        Returns true if there are any more entities in the result set, false otherwise
        
        Returns:
            bool
     */
    function hasNext()
    {
        if (isset($this->_limit) && $this->offset >= $this->_limit)
        {
            return false;
        }

        if (!isset($this->data))
        {
            $this->loadNextPage();
        }

        if ($this->pos < sizeof($this->data))
        {
            return true;
        }

        if (!$this->truncated)
        {
            return false;
        }

        $this->loadNextPage();
        return $this->pos < sizeof($this->data);
    }

    /*
        $cursor->next()
        
        Returns the next entity in the result set.
        
        Returns:
            Telerivet_Entity
     */
    function next()
    {
        if (isset($this->_limit) && $this->offset >= $this->_limit)
        {
            return null;
        }

        if (!isset($this->data) || ($this->pos >= sizeof($this->data) && $this->truncated))
        {
            $this->loadNextPage();
        }

        if ($this->pos < sizeof($this->data))
        {
            $item_data = $this->data[$this->pos];
            $this->pos++;
            $this->offset++;
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

        if (isset($this->_limit) && !isset($request_params['page_size']))
        {
            $request_params['page_size'] = min($this->_limit, 200);
        }

        $response = $this->api->doRequest("GET", $this->path, $request_params);

        $this->data = $response['data'];
        $this->truncated = $response['truncated'];
        $this->next_marker = $response['next_marker'];
        $this->pos = 0;
    }
}
