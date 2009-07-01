<?php
require_once('steelyardwiki.inc.php');

$wiki = new SteelyardWiki(
    new SqliteRepository('db.sqlite'),
    new HttpRequest()
);

echo($wiki->data->value);
?>
