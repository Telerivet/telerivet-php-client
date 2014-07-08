<?php

$api_key = "YOUR_API_KEY_HERE"; // see https://telerivet.com/dashboard/api
$project_id = "YOUR_PROJECT_ID_HERE";

require_once dirname(dirname(__FILE__)) . '/telerivet.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST')
{
    $to_number = $_POST['to_number'];
    $content = $_POST['content'];
    
    $api = new Telerivet_API($api_key);
    
    $project = $api->initProjectById($project_id);
    
    try
    {
        $contact = $project->sendMessage(array(
            'to_number' => $to_number,
            'content' => $content
        ));
        
        $status_html = "<div class='success'>Message sent successfully.</div>";
    }
    catch (Telerivet_Exception $ex)
    {
        $status_html = "<div class='error'>".htmlentities($ex->getMessage())."</div>";
    }
}
else
{
    $to_number = $content = '';
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

.sample_form .input-textarea
{
    padding:3px;
    width:250px;
    height:60px;
}

</style>
</head>
<body class='sample_form'>
<h2>Send an SMS message</h2>
<form method='POST'>

<div class='field'>
<label>Recipient Phone Number</label>
<input class='input-text' type='text' name='to_number' value='<?php echo htmlentities($to_number); ?>' />
</div>

<div class='field'>
<label>SMS Content</label>
<textarea class='input-textarea' type='text' name='content'><?php echo htmlentities($content); ?></textarea>
</div>

<input type='submit' value='Send' />
<br /><br />
<?php echo $status_html; ?>
</form>
</body>
</html>