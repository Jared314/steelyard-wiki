<?php
require_once('steelyardwiki.inc.php');

$criteria = new PageCriteria();
$criteria->name[] = $_REQUEST['name'];

$wiki = new SteelyardWiki(
    new SqliteRepository('db.sqlite'),
    new CustomRequest($criteria)
);

if($_REQUEST['SubmitAction'] == 'Commit Version' && $_REQUEST['data'] != null){
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
