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

//Save if changed
if($_REQUEST['SubmitAction'] == 'Commit Version' && isset($_REQUEST['name']) && isset($_REQUEST['data'])){
    $newData = new Page();
    $newData->name = $_REQUEST['name'];
    $newData->type = $_REQUEST['mimetype'];
    $newData->value = $_REQUEST['data'];
    $newData->active = (array_key_exists('active', $_REQUEST) && $_REQUEST['active'] == 'on');
    $newData->username = $_SERVER['PHP_AUTH_USER'];

    $success = $repository->save($newData);
    if(!$success){
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
var currentUsername = '<?php echo($_SERVER['PHP_AUTH_USER']); ?>';

function setValues(item){
    document.getElementById('mimetype').value = item.type;
    document.getElementById('data').value = item.value;
    document.getElementById('active').checked = item.active;
    document.getElementById('username').innerHTML = item.username;
}

function onBaseVersionChange(el){
    i = data.length - el[el.selectedIndex].value;
    setValues(data[i]);
}

function onLoad(){
    setValues(data[0]);
    document.getElementById('currentUsername').innerHTML = currentUsername;
}

</script>
</head>
<body style="background-color:gray;" onload="onLoad()">
<form method="post">
<table align="center" width="100%">
<tr>
    <td align="left">
<?php if(count($data) > 1){ ?>      <label for="baseVersion">Version</label>:&nbsp;
        <select id="baseVersion" onChange="onBaseVersionChange(this)">
            <?php for($i = count($data); $i > 0; $i--) { ?><option value="<?php echo($i) ?>"><?php echo($i) ?></option><?php } ?>
        </select><?php } ?>
        <label for="mimetype">Mime Type</label>:&nbsp;<input type="text" id="mimetype" name="mimetype" value=""/>&nbsp;
        <label for="active">Active</label>:&nbsp;<input type="checkbox" id="active" name="active" />&nbsp;
        <label for="">Username</label>:&nbsp;<span id="username" />
    </td>
</tr>
<tr><td align="center" colspan="5">
<textarea cols="65" id="data" name="data" rows="50" style="width:100%;"></textarea>
</td></tr>
<tr><td align="left">
<input type="submit" name="SubmitAction" value="Commit Version" />&nbsp;
<input type="submit" name="SubmitAction" value="Cancel" />&nbsp;
Username:&nbsp;<span id="currentUsername"/>
</td></tr>
</table>
</form>
</body>
</html>
