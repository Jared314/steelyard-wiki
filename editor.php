<?php
//Get Http Authorization Username and Password from mod_rewrite
list($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']) = explode(':', base64_decode(substr($_SERVER['HTTP_AUTHORIZATION'], 6)));

require_once('steelyardwiki.inc.php');
$repository = new SqliteRepository('db.sqlite');

//Validate User
if (!isset($_SERVER['PHP_AUTH_USER']) 
    || $_SERVER['PHP_AUTH_USER'] == ''
    || !$repository->isValidUser($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'])
) {
   header('WWW-Authenticate: Basic realm="Your Realm"');
   header('HTTP/1.0 401 Unauthorized');
   echo 'Text to send if Cancel button is used';
   exit;
} 
 
$criteria = new PageCriteria();
$criteria->name[] = $_REQUEST['name'];
$data = $repository->find($criteria, false);
if(count($data) > 0) $data = $data[0];
else $data = new Page(array('name'=>$_REQUEST['name']));


//Save if changed
if($_REQUEST['SubmitAction'] == 'Commit Version' && $_REQUEST['data'] != null){
    $data->type = $_REQUEST['mimetype'];
    $data->value = $_REQUEST['data'];
    $data->active = (array_key_exists('active', $_REQUEST) && $_REQUEST['active'] == 'on');
    $success = $repository->save($data);
    if(!$success){
        //Todo: show error messages or exceptions
    }
}

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>Editor</title>
</head>
<body style="background-color:gray;">
<form method="post">
<table align="center" width="100%">
<tr><td align="left"><label for="mimetype">Mime Type</label>: <input type="text" id="mimetype" name="mimetype" value="<?php echo($data->type); ?>"/></td></tr>
<tr><td><label for="active">Active</label>: <input type="checkbox" id="active" name="active" <?php echo($data->active?'checked="checked"':''); ?> /></td></tr>
<tr><td align="center">
<textarea cols="65" id="data" name="data" rows="50" style="width:100%;">
<?php
echo(htmlentities($data->value));
?>
</textarea>
</td></tr>
<tr><td align="left">
<input type="submit" name="SubmitAction" value="Commit Version" />
<input type="submit" name="SubmitAction" value="Cancel" />
</td></tr>
</table>
</form>
</body>
</html>
