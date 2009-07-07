<?php
require_once('steelyardwiki.inc.php');

$wiki = new SteelyardWiki(
    new SqliteRepository('db.sqlite'),
    new HttpRequest(),
    new FileBinaryDataRepository()
);

header('Content-type: '.$wiki->data->type);

if($wiki->data->isBinary)
    echo $wiki->getBinaryData();
else
    echo($wiki->data->value);
?>