<?php

/**
    PHP client library for Telerivet's REST API.
    
    Example Usage:
    --------------
       
    $API_KEY = 'YOUR_API_KEY';           // from https://telerivet.com/dashboard/api
    $PROJECT_ID = 'YOUR_PROJECT_ID';
   
    $telerivet = new Telerivet_API($API_KEY);
   
    $project = $telerivet->getProjectById($PROJECT_ID);
   
    // Send a SMS message
    $project->sendMessage(array(
        'to_number' => '555-0001',
        'content' => 'Hello world!'
    ));   
   
    // Query contacts  
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
  
    // Import a contact
    $contact = $project->getOrCreateContact(array(
        'name' => 'John Smith',
        'phone_number' => '555-0001',
        'vars' => array(
            'birthdate' => '1981-03-04',
            'network' => 'Vodacom'
        )
    ));
    
    // Add a contact to a group    
    $group = $project->getOrCreateGroup('Subscribers');
    $contact->addToGroup($group);
       
 */

 
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
    
    /**     
        Queries projects accessible to the current user account.
     
        Arguments:
            $options (associative array)
                - name
                - name_prefix
                - sort ("default")
                - sort_dir: ("asc", "desc")
                - page_size (int)
         
        Returns:
            Telerivet_APICursor (of Telerivet_Project)
     */
    function queryProjects($options = null)
    {
        return $this->newApiCursor('Telerivet_Project', '/projects', $options);
    }
    
    /**
        Gets a project by ID 
        
        Note: This does not make any API requests until you access a property of the project.
     
        Arguments:
            $id (string -- see https://telerivet.com/dashboard/api) 
         
        Returns:
            Telerivet_Project
     */    
    function getProjectById($id)
    {
        return new Telerivet_Project($this, array('id' => $id), false);
    }
    
    function doRequest($method, $path, $params = null)
    {
        $curl = $this->curl;
        if (!$curl)
        {
            $curl = $this->curl = curl_init();
        }
        
        $url = "{$this->api_url}{$path}";

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
            curl_setopt($curl, CURLOPT_CAINFO, $cacert_file);        
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
 
$tr_lib_dir = dirname(__FILE__) . '/lib';
 
require_once "{$tr_lib_dir}/entity.php";
require_once "{$tr_lib_dir}/apicursor.php";
require_once "{$tr_lib_dir}/project.php";
require_once "{$tr_lib_dir}/contact.php";
require_once "{$tr_lib_dir}/message.php";
require_once "{$tr_lib_dir}/phone.php";
require_once "{$tr_lib_dir}/group.php";
require_once "{$tr_lib_dir}/label.php";
require_once "{$tr_lib_dir}/service.php";
require_once "{$tr_lib_dir}/contactservicestate.php";
require_once "{$tr_lib_dir}/scheduledmessage.php";
require_once "{$tr_lib_dir}/datatable.php";
require_once "{$tr_lib_dir}/datarow.php";
require_once "{$tr_lib_dir}/exceptions.php";
