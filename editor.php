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
    || !$repository->isValidUser($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'])
) {
   header('WWW-Authenticate: Basic realm="Editor"');
   header('HTTP/1.0 401 Unauthorized');
   echo('HTTP/1.0 401 Unauthorized');
   exit;
} 

//Save if changed
if(!empty($_REQUEST['Commit']) && array_keys_exist(array('name', 'mimetype', 'data'), $_REQUEST)){


    $newData = new Page();
    $newData->name = $_REQUEST['name'];
    $newData->type = !empty($_FILES['filedata']['type']) ? $_FILES['filedata']['type'] : $_REQUEST['mimetype'];
    $newData->value = $_REQUEST['data']; 
    $newData->active = (array_key_exists('active', $_REQUEST) && $_REQUEST['active'] == 'on');
    $newData->username = $_SERVER['PHP_AUTH_USER'];
    $newData->isBinary = !empty($_FILES['filedata']);



    $success = $repository->save($newData);
    if($success){
        if(!empty($_FILES['filedata']) && !empty($_FILES['filedata']['tmp_name'])){
            $binaryRepository = new FileBinaryDataRepository();
            $success = $binaryRepository->save($_REQUEST['name'], $_FILES['filedata']['tmp_name']);
        }
    }

    if(!$success){
        echo('Failure');
        //Todo: show error messages or exceptions
    }
}

$criteria = new PageCriteria();
$criteria->name[] = $_REQUEST['name'];
$data = $repository->find($criteria, false);
if(count($data) < 1)
    $data = array(new Page(array('name'=>$_REQUEST['name'])));

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>Editor</title>
<script type="text/javascript">
var data = <?php echo(json_encode($data)); ?>;
function $(id){ return document.getElementById(id); }

function setValues(item){
    $('mimetype').value = item.type;
    $('data').value = (item.binaryname != null) ? '[Binary Data]' : item.value;
    $('active').checked = item.active;
    $('createdDate').innerHTML = item.created;
    $('username').innerHTML = item.username;
}

function onBaseVersionChange(el){
    setValues(data[el.selectedIndex]);
}
</script>
</head>
<body style="background-color:gray;" onload="setValues(data[0])">
<form method="post" enctype="multipart/form-data">
<table align="center" width="100%">
<tr>
    <td>
        <label for="baseVersion">Version</label>:&nbsp;<select id="baseVersion" onChange="onBaseVersionChange(this)">
            <?php for($i = count($data); $i > 0; $i--) { ?><option><?=$i ?></option><?php } ?>
        </select>
        <label for="mimetype">Mime Type</label>:&nbsp;<input type="text" id="mimetype" name="mimetype" value=""/>&nbsp;
        <label for="active">Active</label>:&nbsp;<input type="checkbox" id="active" name="active" />&nbsp;
        <label for="">Created</label>:&nbsp;<span id="createdDate"></span>&nbsp;
        <label for="">Username</label>:&nbsp;<span id="username"></span>
    </td>
</tr>
<tr><td align="center" colspan="5">
<textarea cols="65" id="data" name="data" rows="50" style="width:100%;"></textarea>
<input type="file" id="filedata" name="filedata" />
</td></tr>
<tr><td>
<input type="submit" name="Commit" value="Commit Version" />&nbsp;
<input type="submit" name="Cancel" value="Cancel" />&nbsp;
Username:&nbsp;<span id="currentUsername"><?=$_SERVER['PHP_AUTH_USER'] ?></span>
</td></tr>
</table>
</form>
</body>
</html>
