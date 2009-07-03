<?php
//Get Http Authorization Username and Password from mod_rewrite
list($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']) = explode(':', base64_decode(substr($_SERVER['HTTP_AUTHORIZATION'], 6)));

require_once('steelyardwiki.inc.php');
$repository = new SqliteRepository('db.sqlite');

//Validate User
if (!isset($_SERVER['PHP_AUTH_USER'])
    || $_SERVER['PHP_AUTH_USER'] == ''
    || ($repository->hasUsers() && !$repository->isValidUser($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']))
) {
   header('WWW-Authenticate: Basic realm="Your Realm"');
   header('HTTP/1.0 401 Unauthorized');
   echo 'Text to send if Cancel button is used';
   exit;
} 

$criteria = new UserCriteria();
$criteria->name[] = $_REQUEST['name'];
$user = $repository->find($criteria);
if(count($user)) $user = $user[0];
else $user = new User(array('name'=>$_REQUEST['name']));

//Save if changed
if($_REQUEST['SubmitAction'] == 'Commit' && $_REQUEST['username'] != null){
    $user->name = $_REQUEST['username'];
    $user->password = $_REQUEST['password'];
    $user->active = (isset($_REQUEST['active']) && $_REQUEST['active'] == 'on');
    
    $success = $repository->save($user);
    if(!$success){
        //Todo: show error messages or exceptions
    }
}

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>User</title>
</head>
<body style="background-color:gray;">
<form method="post">
<table align="center" width="100%">
<tr><td><label for="username">Name</label><input type="text" id="username" name="username" value="<?php echo($user->name); ?>" /></td></tr>
<tr><td><label for="password">Password</label><input type="text" id="password" name="password" value="" /></td></tr>
<tr><td><label for="active">Active</label><input type="checkbox" id="active" name="active" <?php echo($user->active?'checked="checked"':''); ?> /></td></tr>
<tr><td align="left">
<input type="submit" name="SubmitAction" value="Commit" />
<input type="submit" name="SubmitAction" value="Cancel" />
</td></tr>
</table>
</form>
</body>
</html>