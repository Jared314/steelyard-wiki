<?php
require_once('steelyardwiki.inc.php');

function array_keys_exist(array $keys, array $search){
    foreach($keys as $key) if(!array_key_exists($key, $search)) return false;
    return true;
}



list($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']) = explode(':', base64_decode(substr($_SERVER['HTTP_AUTHORIZATION'], 6)));

$repository = new SqliteRepository('db.sqlite');

//Validate User
if (!array_keys_exist(array('PHP_AUTH_USER', 'PHP_AUTH_PW'), $_SERVER)
    || ($repository->hasUsers() && !$repository->isValidUser($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']))
) {
   header('WWW-Authenticate: Basic realm="Users"');
   header('HTTP/1.0 401 Unauthorized');
   echo('HTTP/1.0 401 Unauthorized');
   exit;
}

//Save if changed
if(!empty($_REQUEST['Commit']) && array_keys_exist(array('username', 'password'), $_REQUEST)){
    $user = new User();
    $user->name = $_REQUEST['username'];
    $user->password = $_REQUEST['password'];
    $user->active = (isset($_REQUEST['active']) && $_REQUEST['active'] == 'on');

    $success = $repository->save($user);
    if(!$success){
        //Todo: show error messages or exceptions
    }
}

$criteria = new UserCriteria();
$criteria->name[] = $_REQUEST['name'];
$user = $repository->find($criteria);
if(count($user) < 1) $user = $user[0];
else $user = new User(array('name'=>$_REQUEST['name']));

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>User</title>
</head>
<body style="background-color:gray;">
<form method="post">
<table>
<tr><td><label for="username">Name</label>:&nbsp;<input type="text" id="username" name="username" value="<?=$user->name ?>" /></td></tr>
<tr><td><label for="password">Password</label>:&nbsp;<input type="text" id="password" name="password" value="" /></td></tr>
<tr><td><label for="active">Active</label>:&nbsp;<input type="checkbox" id="active" name="active" <?php echo($user->active?'checked="checked"':''); ?> /></td></tr>
<tr><td>
<input type="submit" name="Commit" value="Commit" />
<input type="submit" name="Cancel" value="Cancel" />
</td></tr>
</table>
</form>
</body>
</html>