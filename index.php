<?php
require_once('steelyardwiki.inc.php');

$wiki = new SteelyardWiki(
    new SqliteRepository('db.sqlite'),
    new HttpRequest()
);

header('Content-type: '.$wiki->data->type);
echo($wiki->data->value);
?>
