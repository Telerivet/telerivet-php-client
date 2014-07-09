<?php

/**
    PHP client library for Telerivet's REST API.
    
    Example Usage:
    --------------
       
    $API_KEY = 'YOUR_API_KEY';           // from https://telerivet.com/dashboard/api
    $PROJECT_ID = 'YOUR_PROJECT_ID';
   
    $telerivet = new Telerivet_API($API_KEY);
   
    $project = $telerivet->initProjectById($PROJECT_ID);
   
    // Send a SMS message
    $project->sendMessage(array(
        'to_number' => '555-0001',
        'content' => 'Hello world!'
    ));
 */
class Telerivet_API
{
    private $api_key;
    private $api_url;
    public $num_requests = 0;
    private $client_version = '1.1.0';    
    
    private $curl;
    public $debug = false;
    
    /**     
        $tr = new Telerivet_API($api_key)
        
        Initializes a client handle to the Telerivet REST API.
        
        Each API key is associated with a Telerivet user account, and all
        API actions are performed with that user's permissions. If you want to restrict the
        permissions of an API client, simply add another user account at
        <https://telerivet.com/dashboard/users> with the desired permissions.
        
        Arguments:
          - $api_key (Your Telerivet API key; see <https://telerivet.com/dashboard/api>)
              * Required
     */
    public function __construct($api_key, $api_url = 'https://api.telerivet.com/v1')
    {
        $this->api_key = $api_key;
        $this->api_url = $api_url;
    }    
    
    /**
        $tr->getProjectById($id)
        
        Retrieves the Telerivet project with the given ID.
        
        Arguments:
          - $id
              * ID of the project -- see <https://telerivet.com/dashboard/api>
              * Required
          
        Returns:
            Telerivet_Project
    */
    function getProjectById($id)
    {
        return new Telerivet_Project($this, $this->doRequest("GET", "{$this->getBaseApiPath()}/projects/{$id}"));
    }

    /**
        $tr->initProjectById($id)
        
        Initializes the Telerivet project with the given ID without making an API request.
        
        Arguments:
          - $id
              * ID of the project -- see <https://telerivet.com/dashboard/api>
              * Required
          
        Returns:
            Telerivet_Project
    */
    function initProjectById($id)
    {
        return new Telerivet_Project($this, array('id' => $id), false);
    }

    /**
        $tr->queryProjects($options)
        
        Queries projects accessible to the current user account.
        
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
                * Number of results returned per page (max 200)
                * Default: 50
            
            - offset (int)
                * Number of items to skip from beginning of result set
                * Default: 0
          
        Returns:
            Telerivet_APICursor (of Telerivet_Project)
    */
    function queryProjects($options = null)
    {
        return $this->newApiCursor('Telerivet_Project', "{$this->getBaseApiPath()}/projects", $options);
    }

    function getBaseApiPath()
    {
        return "";
    }
    
    function doRequest($method, $path, $params = null)
    {
        $curl = $this->curl;
        if (!$curl)
        {
            $curl = $this->curl = curl_init();
        }
        
        $url = "{$this->api_url}{$path}";

        $headers = array(
            "User-Agent: Telerivet PHP Client/{$this->client_version} PHP/" . PHP_VERSION . " OS/" . PHP_OS
        );
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
        
        if ($this->debug)
        {
            error_log("$method $url");
        }
        
        $cacert_file = dirname(__FILE__) . "/cacert.pem";
        if (file_exists($cacert_file))
        {
            curl_setopt($curl, CURLOPT_CAINFO, $cacert_file);        
        }
        curl_setopt($curl, CURLOPT_USERPWD, "{$this->api_key}:");        
        
        $this->num_requests++;
        
        $response_json = curl_exec($curl);        
        $network_error = curl_error($curl);                
        
        if ($network_error)
        {
            throw new Telerivet_IOException("Error connecting to Telerivet API: {$network_error}");
        }
        else
        {
            $response = json_decode($response_json, true);
            
            if (isset($response['error']))
            {
                $error = $response['error'];
                $error_code = $error['code'];
                switch ($error_code)
                {
                    case 'invalid_param':
                        throw new Telerivet_InvalidParameterException($error['message'], $error['code'], $error['param']);
                    case 'not_found':
                        throw new Telerivet_NotFoundException($error['message'], $error['code']);
                    default:
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
   
    function newApiCursor($item_cls, $path, $options)
    {
        return new Telerivet_ApiCursor($this, $item_cls, $path, $options);
    }
}

// base class for exceptions raised by this library
class Telerivet_Exception extends Exception {}

// exception corresponding to error returned in API response
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

class Telerivet_NotFoundException extends Telerivet_APIException
{
    function __construct($message, $error_code)
    {
        parent::__construct($message, $error_code);
    }
}

// exception raised when client could not connect to server
class Telerivet_IOException extends Telerivet_Exception {}

$tr_lib_dir = dirname(__FILE__) . '/lib';
require_once "{$tr_lib_dir}/entity.php";
require_once "{$tr_lib_dir}/apicursor.php";

require_once "{$tr_lib_dir}/message.php";
require_once "{$tr_lib_dir}/contact.php";
require_once "{$tr_lib_dir}/project.php";
require_once "{$tr_lib_dir}/label.php";
require_once "{$tr_lib_dir}/group.php";
require_once "{$tr_lib_dir}/phone.php";
require_once "{$tr_lib_dir}/datatable.php";
require_once "{$tr_lib_dir}/datarow.php";
require_once "{$tr_lib_dir}/scheduledmessage.php";
require_once "{$tr_lib_dir}/service.php";
require_once "{$tr_lib_dir}/contactservicestate.php";
require_once "{$tr_lib_dir}/route.php";
require_once "{$tr_lib_dir}/mobilemoneyreceipt.php";