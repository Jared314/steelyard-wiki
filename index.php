<?php
require_once('steelyardwiki.inc.php');
require_once('settings.inc.php');

$wiki = new SteelyardWiki(
    Settings::getRepository(),
    new HttpRequest(),
    Settings::getBinaryDataRepository()
);

header('Content-type: '.$wiki->data->type);

if($wiki->data->isBinary)
    echo $wiki->getBinaryData();
else
    echo($wiki->data->value);
?>
