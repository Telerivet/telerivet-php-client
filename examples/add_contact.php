<?php

$api_key = "YOUR_API_KEY_HERE"; // see https://telerivet.com/dashboard/api
$project_id = "YOUR_PROJECT_ID_HERE";

require_once dirname(dirname(__FILE__)) . '/telerivet.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST')
{
    $name = $_POST['name'];
    $phone_number = $_POST['phone_number'];
    $email = $_POST['email'];    
    
    $api = new Telerivet_API($api_key);
    
    $project = $api->initProjectById($project_id);
    
    try
    {
        $contact = $project->getOrCreateContact(array(
            'name' => $name,
            'phone_number' => $phone_number,
            'vars' => array('email' => $email),
        ));
        
        $group = $project->getOrCreateGroup("Subscribers");
        $contact->addToGroup($group);
        
        $status_html = "<div class='success'>Contact saved successfully.</div>";
    }
    catch (Telerivet_Exception $ex)
    {
        $status_html = "<div class='error'>".htmlentities($ex->getMessage())."</div>";
    }
}
else
{
    $name = $phone_number = $email = '';
    $status_html = '';
}

?>
<html>
<head>
<style type='text/css'>

body.sample_form
{
    font-family:Verdana, sans-serif;
    padding:20px;
}

.sample_form label
{
    display:block;
    font-weight:bold;
}
.sample_form .field
{
    padding:8px 0px;
}

.sample_form .input-text
{
    padding:3px;
}

</style>
</head>
<body class='sample_form'>
<h2>Add or update a Telerivet contact</h2>
<form method='POST'>

<div class='field'>
<label>Name</label>
<input class='input-text' type='text' name='name' value='<?php echo htmlentities($name); ?>' />
</div>

<div class='field'>
<label>Phone Number</label>
<input class='input-text' type='text' name='phone_number' value='<?php echo htmlentities($phone_number); ?>' />
</div>

<div class='field'>
<label>Email Address</label>
<input class='input-text' type='text' name='email' value='<?php echo htmlentities($email); ?>' />
</div>

<input type='submit' value='Save' />
<br /><br />
<?php echo $status_html; ?>
</form>
</body>
</html>