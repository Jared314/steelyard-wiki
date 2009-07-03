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

$wiki = new SteelyardWiki(
    $repository,
    new CustomRequest($criteria)
);

//Save if changed
if($_REQUEST['SubmitAction'] == 'Commit Version' && $_REQUEST['data'] != null){
    $wiki->data->name = $_REQUEST['name'];
    $wiki->data->type = $_REQUEST['mimetype'];
    $wiki->data->value = $_REQUEST['data'];
    $success = $wiki->repository->save($wiki->data);
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
<tr><td align="left"><label for="mimetype">Mime Type</label>: <input type="text" id="mimetype" name="mimetype" value="<?php echo($wiki->data->type); ?>"/></td></tr>
<tr><td align="center">
<textarea cols="65" id="data" name="data" rows="50" style="width:100%;">
<?php
echo(htmlentities($wiki->data->value));
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
