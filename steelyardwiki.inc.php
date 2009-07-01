<?php
class SteelyardWiki{
    public $repository;
    public $request;
    public $data;

    public function __construct(IRepository &$repository = NULL, IRequest &$request = NULL){
        if($repository != null && $request != null){
            $this->repository = $repository;
            $this->request = $request;
            $this->data = $this->processRequest($repository, $request);
        }
    }

    function __destruct() {
        $this->request = null;
        $this->repository = null;
    }

    public function processRequest(IRepository &$repository, IRequest &$request){
        $criteria = $request->getCriteria();
        $result = $repository->find($criteria);
        return ($result != null && count($result) > 0) ? $result[0] : new Page();
    }
}

/*
 * Entities
 */
class Page {
    public $name = "";
    public $value = "";
    public $created;
    public $username = "";
    public $version = 1;
    public $active = true;

    function __construct(array $values = NULL) {
        if($values != null) $this->SetValues($values);
    }

    private function SetValues(array $values){
        if($values['name'] != null) $this->name = $values['name'];
        if($values['value'] != null) $this->value = $values['value'];
        if($values['created'] != null) $this->created = $values['created'];
        if($values['username'] != null) $this->username = $values['username'];
        if($values['inactive'] != null) $this->active = ($values['inactive'] == 0);
    }
}

class PageCriteria {
    public $name = array();
    public $value = array();
    public $created = array();
    public $username = array();
    public $version = NULL;
    public $active = true;
}

/*
 * Repository classes
 */
interface IRepository {
    public function find(PageCriteria $criteria);
    public function save(Page $page);
}

class SqliteRepository implements IRepository {

    private $connection;

    function __construct($filename) {
        $this->connection = new PDO('sqlite:'.realpath($filename));
    }

    function __destruct() {
        $this->connection = null;
    }

    public function find(PageCriteria $criteria){
        $sql = "SELECT * FROM CurrentPage WHERE CurrentPage.name LIKE '".$criteria->name[0]."';";
        $q = $this->connection->query($sql);

        $result = array();
        foreach($q as $row){ $result[] = new Page($row); }
        return $result;
    }

    public function save(Page $page){
        $inactive = $page->active ? 0 : 1;
        $sql = "INSERT INTO Page (name, value, user_id, inactive) VALUES ('{$page->name}','{$page->value}', 1, {$inactive})";
        $count = $this->connection->exec($sql);
        return ($count > 0);
    }
}

/*
 * Request classes
 */
interface IRequest{
    public function getCriteria();
}

class CustomRequest implements IRequest{
    private $criteria;

    function __construct(PageCriteria &$newCriteria){
        $this->criteria = $newCriteria;
    }

    public function getCriteria(){
        return $this->criteria;
    }
}

class HttpRequest implements IRequest{
    public $url;
    public $baseUrl;

    function __construct($scriptUri = NULL){
        if($scriptUri == null) $scriptUri = $_SERVER['SCRIPT_URI'];
        $this->url = $scriptUri;
        $this->baseUrl = $this->generateBaseUrl($scriptUri);
    }

    public function getCriteria(){
        $result = new PageCriteria();
        $result->name[] = $this->parseUrl($this->baseUrl, $this->url);
        return $result;
    }

    private function generateBaseUrl($scriptUri){
        $uri = parse_url($scriptUri);
        $info = pathinfo($uri['path']);
        return $uri['scheme'].'://'.$uri['host'].$info['dirname'];
    }

    private function parseUrl($baseUrl, $url){
        return trim(str_replace($baseUrl, '', $url), '/');
    }
}
?>
