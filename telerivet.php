<?php

class Telerivet_API
{
    private $api_key;
    private $api_url;
    
    private $curl;
    
    public function __construct($api_key, $api_url = 'https://api.telerivet.com/v1')
    {
        $this->api_key = $api_key;
        $this->api_url = $api_url;
    }
    
    function doRequest($method, $path, $params = null)
    {
        $curl = $this->curl;
        if (!$curl)
        {
            $curl = $this->curl = curl_init();
        }
        
        $url = "{$this->api_url}{$path}";
                
        //error_log("$method $url");
        //error_log(var_export($params, true));
                
        $headers = array();
        if ($method === 'POST' || $method == 'PUT')
        {
            $headers[] = "Content-Type: application/json";
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($params));                       
        }
        else
        {
            if ($params)
            {
                $url .= "?" . http_build_query($params, '', '&');
            }        
            curl_setopt($curl, CURLOPT_POSTFIELDS, '');
        }

        curl_setopt($curl, CURLOPT_URL, $url);        
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);         
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);        
        curl_setopt($curl, CURLOPT_BUFFERSIZE, 4096);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($curl, CURLOPT_TIMEOUT, 60);
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC); 
        
        $cacert_file = dirname(__FILE__) . "/cacert.pem";
        if (file_exists($cacert_file))
        {
            curl_setopt($curl, CURLOPT_CAINFO, dirname(__FILE__) . "/cacert.pem");        
        }
        curl_setopt($curl, CURLOPT_USERPWD, "{$this->api_key}:");        
        
        $response_json = curl_exec($curl);        
        $network_error = curl_error($curl);                
        
        if ($network_error)
        {
            throw new Telerivet_IOException("Error connecting to Telerivet API: $error");
        }
        else
        {
            $response = json_decode($response_json, true);
            
            if (isset($response['error']))
            {
                $error = $response['error'];
                $error_code = $error['code'];
                if ($error_code == 'invalid_param')
                {
                    throw new Telerivet_InvalidParameterException($error['message'], $error['code'], $error['param']);
                }
                else
                {
                    throw new Telerivet_APIException($error['message'], $error['code']);
                }
            }
            else
            {            
                return $response;
            }
        }
    }
    
    function __destruct()
    {
        if ($this->curl)
        {
            curl_close($this->curl);
        }
    }
    
    function queryProjects($options = null)
    {
        return $this->newApiCursor('Telerivet_Project', '/projects', $options);
    }
    
    function getProjectById($id)
    {
        return new Telerivet_Project($this, array('id' => $id), false);
    }
    
    function newApiCursor($item_cls, $path, $options)
    {
        return new Telerivet_ApiCursor($this, $item_cls, $path, $options);
    }
}

abstract class Telerivet_Entity
{
    protected $_is_loaded;
    protected $_data;
    protected $_api;
    protected $_has_custom_vars = false;
    protected $_vars;
    
    protected $_dirty = array();
    
    public $id;
    
    function __construct($api, $data, $is_loaded = true)
    {
        $this->_api = $api;
        $this->_setData($data);
        $this->_is_loaded = $is_loaded;
    }
    
    protected function _setData($data)
    {
        $this->_data = $data;
        $this->id = $data['id'];
        
        if ($this->_has_custom_vars)
        {
            $this->_vars = new Telerivet_CustomVars(isset($data['vars']) ? $data['vars'] : array());
        }
    }
    
    protected function _loadData()
    {
        if (!$this->_is_loaded)
        {
            $this->_setData($this->_api->doRequest('GET', $this->getBaseApiPath()));
            $this->_is_loaded = true;
        }
    }
        
    function __get($name)
    {
        if ($name == 'vars')
        {
            $this->_loadData();
            return $this->_vars;
        }
    
        $data = $this->_data;
        if (isset($data[$name]))
        {
            return $data[$name];
        }
        else if ($this->_is_loaded || array_key_exists($name, $data))
        {
            return null;
        }
        
        $this->_loadData();
        $data = $this->_data;
        
        return isset($data[$name]) ? $data[$name] : null;
    }
    
    function __set($name, $value)
    {
        if (!$this->_is_loaded)
        {
            $this->_loadData();
        }
        $this->_data[$name] = $value;
        
        $this->_dirty[$name] = $value;
        // todo track dirty
    }
    
    function save()
    {
        $dirty_props = $this->_dirty; 

        if ($this->_vars)
        {   
            $dirty_vars = $this->_vars->getDirtyVariables();
            if ($dirty_vars)
            {
                $dirty_props['vars'] = $dirty_vars;
            }
        }
        
        $this->_api->doRequest('POST', $this->getBaseApiPath(), $dirty_props);        
        $this->_dirty = array();
        
        if ($this->_vars)
        {
            $this->_vars->clearDirtyVariables();
        }
    }
    
    abstract function getBaseApiPath();
}

class Telerivet_CustomVars implements Iterator
{
    private $_dirty = array();
    private $_vars;

    function __construct($vars)
    {
        $this->_vars = $vars;
    }
    
    function all()
    {
        return $this->_vars;
    }
    
    function getDirtyVariables()
    {
        return $this->_dirty;
    }
    
    function clearDirtyVariables()
    {
        $this->_dirty = array();
    }   
    
    function rewind() 
    {
        return reset($this->_vars);
    }
  
    function current() 
    {
        return current($this->_vars);
    }
    
    function key() 
    {
        return key($this->_vars);
    }
  
    function next() 
    {
        return next($this->_vars);
    }
  
    function valid() 
    {
        return key($this->_vars) !== null;
    }
    
    function __isset($name)
    {
        return isset($this->_vars[$name]);
    }
    
    function __unset($name)
    {
        unset($this->_vars[$name]);
    }
    
    function __get($name)
    {
        return isset($this->_vars[$name]) ? $this->_vars[$name] : null;
    }
    
    function __set($name, $value)
    {
        $this->_vars[$name] = $value;
        $this->_dirty[$name] = $value;
    }
}

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
    
    function hasNext()
    {
        if (!isset($this->data))
        {
            $this->loadNextPage();
        }

        return $this->pos < sizeof($this->data) || $this->truncated;
    }
    
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
    
}

class Telerivet_Project extends Telerivet_Entity
{
    protected $_has_custom_vars = true;

    function queryPhones($options = null)
    {
        return $this->_api->newApiCursor('Telerivet_Phone', "{$this->getBaseApiPath()}/phones", $options);
    }

    function getPhoneById($id)
    {
        return new Telerivet_Phone($this->_api, array('id' => $id, 'project_id' => $this->id), false);
    }
    
    function queryContacts($options = null)
    {
        return $this->_api->newApiCursor('Telerivet_Contact', "{$this->getBaseApiPath()}/contacts", $options);
    }
    
    function getContactById($id)
    {
        return new Telerivet_Contact($this->_api, array('id' => $id, 'project_id' => $this->id), false);
    }
    
    function sendMessage($options)
    {
        return $this->_api->doRequest('POST', $this->getBaseApiPath() . '/messages/outgoing', $options);        
    }
    
    function sendMessages($options)
    {
        return $this->_api->doRequest('POST', $this->getBaseApiPath() . '/messages/outgoing_batch', $options);        
    }
    
    function scheduleMessage($options)
    {
        return $this->_api->doRequest('POST', $this->getBaseApiPath() . '/scheduled', $options);        
    }
    
    function queryGroups($options = null)
    {
        return $this->_api->newApiCursor('Telerivet_Group', "{$this->getBaseApiPath()}/groups", $options);
    }
    
    function queryScheduledMessages($options = null)
    {
        return $this->_api->newApiCursor('Telerivet_ScheduledMessage', "{$this->getBaseApiPath()}/scheduled", $options);
    }
    
    function getGroupById($id)
    {
        return new Telerivet_Group($this->_api, array('id' => $id, 'project_id' => $this->id), false);
    }
    
    function getOrCreateContact($options)
    {                                       
        if (is_string($options))
        {
            $options = array('phone_number' => $options);
        }
    
        $data = $this->_api->doRequest("POST", "{$this->getBaseApiPath()}/contacts", $options);
        return new Telerivet_Group($this->_api, $data);
    }
    
    function getOrCreateGroup($options)
    {                                          
        $data = $this->_api->doRequest("POST", "{$this->getBaseApiPath()}/groups", array('name' => $name));
        return new Telerivet_Group($this->_api, $data);
    }
    
    function getOrCreateLabel($name)
    {                                       
        $data = $this->_api->doRequest("POST", "{$this->getBaseApiPath()}/labels", array('name' => $name));
        return new Telerivet_Label($this->_api, $data);
    }
    
    function queryMessages($options = null)
    {
        return $this->_api->newApiCursor('Telerivet_Message', "{$this->getBaseApiPath()}/messages", $options);
    }
    
    function getMessageById($id)
    {
        return new Telerivet_Message($this->_api, array('id' => $id, 'project_id' => $this->id), false);
    }
    
    function queryLabels($options = null)
    {
        return $this->_api->newApiCursor('Telerivet_Label', "{$this->getBaseApiPath()}/labels", $options);
    }
    
    function getLabelById($id)
    {
        return new Telerivet_Label($this->_api, array('id' => $id, 'project_id' => $this->id), false);
    }
    
    function queryDataTables($options = null)
    {
        return $this->_api->newApiCursor('Telerivet_DataTable', "{$this->getBaseApiPath()}/tables", $options);
    }
    
    function getDataTableById($id)
    {
        return new Telerivet_DataTable($this->_api, array('id' => $id, 'project_id' => $this->id), false);
    }
    
    function getScheduledMessageById($id)
    {
        return new Telerivet_ScheduledMessage($this->_api, array('id' => $id, 'project_id' => $this->id), false);
    }
    
    function getBaseApiPath()
    {
        return "/projects/{$this->id}";
    }
}

class Telerivet_Phone extends Telerivet_Entity
{
    protected $_has_custom_vars = true;

    function queryMessages($options = null)
    {
        return $this->_api->newApiCursor('Telerivet_Message', "{$this->getBaseApiPath()}/messages", $options);
    }

    function getBaseApiPath()
    {
        return "/projects/{$this->project_id}/phones/{$this->id}";
    }
}

class Telerivet_ScheduledMessage extends Telerivet_Entity
{
    function delete()
    {        
        $this->_api->doRequest("DELETE", $this->getBaseApiPath());               
    }            

    function getBaseApiPath()
    {
        return "/projects/{$this->project_id}/scheduled/{$this->id}";
    }
}

class Telerivet_Contact extends Telerivet_Entity
{
    protected $_has_custom_vars = true;
    
    private $_group_ids_set = array();

    function queryMessages($options = null)
    {
        return $this->_api->newApiCursor('Telerivet_Message', "{$this->getBaseApiPath()}/messages", $options);
    }
    
    function queryGroups($options = null)
    {
        return $this->_api->newApiCursor('Telerivet_Group', "{$this->getBaseApiPath()}/groups", $options);
    }

    function queryScheduledMessages($options = null)
    {
        return $this->_api->newApiCursor('Telerivet_ScheduledMessage', "{$this->getBaseApiPath()}/scheduled", $options);
    }
    
    function isInGroup($group)
    {
        $this->_loadData();
        return isset($this->_group_ids_set[$group->id]);
    }
    
    function addToGroup($group)
    {
        $this->_api->doRequest("PUT", "{$group->getBaseApiPath()}/contacts/{$this->id}");               
        $this->_group_ids_set[$group->id] = true;        
    }
    
    function removeFromGroup($group)
    {        
        $this->_api->doRequest("DELETE", "{$group->getBaseApiPath()}/contacts/{$this->id}");               
        unset($this->_group_ids_set[$group->id]);
    }
    
    function delete()
    {        
        $this->_api->doRequest("DELETE", $this->getBaseApiPath());               
    }    
    
    function getBaseApiPath()
    {
        return "/projects/{$this->project_id}/contacts/{$this->id}";
    }
    
    protected function _setData($data)
    {
        parent::_setData($data);
        
        if (isset($data['group_ids']) && is_array($data['group_ids']))
        {
            foreach ($data['group_ids'] as $group_id)
            {
                $this->_group_ids_set[$group_id] = true;
            }
        }
    }
}

class Telerivet_Message extends Telerivet_Entity
{
    private $_label_ids_set = array();

    function getBaseApiPath()
    {
        return "/projects/{$this->project_id}/messages/{$this->id}";
    }
    
    function delete()
    {        
        $this->_api->doRequest("DELETE", $this->getBaseApiPath());               
    }        
    
    function hasLabel($label)
    {
        $this->_loadData();
        return isset($this->_label_ids_set[$label->id]);
    }
    
    function addLabel($label)
    {
        $this->_api->doRequest("PUT", "{$label->getBaseApiPath()}/messages/{$this->id}");               
        $this->_label_ids_set[$label->id] = true;        
    }
    
    function removeLabel($label)
    {        
        $this->_api->doRequest("DELETE", "{$label->getBaseApiPath()}/messages/{$this->id}");               
        unset($this->_label_ids_set[$label->id]);
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

class Telerivet_Group extends Telerivet_Entity
{
    function getBaseApiPath()
    {
        return "/projects/{$this->project_id}/groups/{$this->id}";
    }

    function delete()
    {        
        $this->_api->doRequest("DELETE", $this->getBaseApiPath());               
    }        
          
    function queryContacts($options = null)
    {
        return $this->_api->newApiCursor('Telerivet_Contact', "{$this->getBaseApiPath()}/contacts", $options);
    }
    
    function queryScheduledMessages($options = null)
    {
        return $this->_api->newApiCursor('Telerivet_ScheduledMessage', "{$this->getBaseApiPath()}/scheduled", $options);
    }    
}

class Telerivet_Label extends Telerivet_Entity
{
    function getBaseApiPath()
    {
        return "/projects/{$this->project_id}/labels/{$this->id}";
    }

    function delete()
    {        
        $this->_api->doRequest("DELETE", $this->getBaseApiPath());               
    }    
        
    function queryMessages($options = null)
    {
        return $this->_api->newApiCursor('Telerivet_Message', "{$this->getBaseApiPath()}/messages", $options);
    }
}

class Telerivet_DataTable extends Telerivet_Entity
{
    function queryRows($options = null)
    {
        return $this->_api->newApiCursor('Telerivet_DataRow', "{$this->getBaseApiPath()}/rows", $options);
    }

    function getRowById($id)
    {                                          
        return new Telerivet_DataRow($this->_api, array('id' => $id, 'table_id' => $this->id, 'project_id' => $this->project_id), false);
    }
    
    function createRow($options)
    {                                          
        $data = $this->_api->doRequest("POST", "{$this->getBaseApiPath()}/rows", array('name' => $name));
        return new Telerivet_DataRow($this->_api, $data);
    }
    
    function getBaseApiPath()
    {
        return "/projects/{$this->project_id}/tables/{$this->id}";
    }
}

class Telerivet_DataRow extends Telerivet_Entity
{
    protected $_has_custom_vars = true;

    function delete()
    {        
        $this->_api->doRequest("DELETE", $this->getBaseApiPath());               
    }
    
    function getBaseApiPath()
    {
        return "/projects/{$this->project_id}/tables/{$this->table_id}/rows/{$this->id}";
    }
}

class Telerivet_Exception extends Exception
{
}

class Telerivet_APIException extends Telerivet_Exception
{
    public $error_code;

    function __construct($message, $error_code)
    {
        parent::__construct($message);
        $this->error_code = $error_code;
    }
}

class Telerivet_InvalidParameterException extends Telerivet_APIException
{
    public $param;    
    function __construct($message, $error_code, $param)
    {
        parent::__construct($message, $error_code);
        $this->param = $param;
    }
}

class Telerivet_IOException extends Telerivet_Exception
{
}