<?php
/*
 * Created By: Jared Lobberecht
 *
 */

class SteelyardWiki{
    public $repository;
    public $request;
    public $data;

    public function __construct(IRepository &$repository = NULL, IRequest &$request = NULL){
        $this->repository = $repository;
        $this->request = $request;
        if($repository != null && $request != null){
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
        if(isset($values['name'])) $this->name = $values['name'];
        if(isset($values['value'])) $this->value = $values['value'];
        if(isset($values['created'])) $this->created = $values['created'];
        if(isset($values['username'])) $this->username = $values['username'];
        if(isset($values['inactive'])) $this->active = ($values['inactive'] == 0);
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

class User {
    public $name = '';
    public $password = '';
    public $active = true;

    function __construct(array $values = NULL) {
        if($values != null) $this->SetValues($values);
    }

    private function SetValues(array $values){
        if(isset($values['name'])) $this->name = $values['name'];
        if(isset($values['inactive'])) $this->active = ($values['inactive'] == 0);
    }
}

class UserCriteria{
    public $name = array();
    public $active = true;
}

/*
 * Repository classes
 */
interface IRepository {
    public function find($criteria);
    public function save($entity);
    public function isValidUser($username, $password);
}

class SqliteRepository implements IRepository {

    private $connection;

    function __construct($filename) {
        $this->connection = new PDO('sqlite:'.realpath($filename));
    }

    function __destruct() {
        $this->connection = null;
    }

    public function find($criteria, $currentOnly = true){
        return ($criteria instanceof UserCriteria)? $this->findUser($criteria) : $this->findPage($criteria, $currentOnly);
    }

    private function findPage(PageCriteria $criteria, $currentOnly = true){
        //Todo: use all the criteria fields
        $table = $currentOnly ? 'CurrentPage' : 'Page';
        $sql = "SELECT * FROM {$table} WHERE {$table}.name LIKE '{$criteria->name[0]}';";
        $q = $this->connection->query($sql);

        $result = array();
        foreach($q as $row){ $result[] = new Page($row); }
        return $result;
    }

    private function findUser(UserCriteria $criteria){
        //Todo: use all the criteria fields
        $sql = "SELECT name, inactive FROM User WHERE name LIKE '{$criteria->name[0]}';";
        $q = $this->connection->query($sql);

        $result = array();
        foreach($q as $row){ $result[] = new User($row); }
        return $result;
    }

    public function save($entity){
        return ($entity instanceof User)? $this->saveUser($entity) : $this->savePage($entity);
    }

    private function savePage(Page $page){
        $user_id = $this->getUserId($page->username);
        $inactive = $page->active ? 0 : 1;
        $sql = "INSERT INTO Page (name, value, user_id, inactive) VALUES ('{$page->name}','{$page->value}', {$user_id}, {$inactive});";
        return $this->connection->exec($sql) > 0;
    }

    private function saveUser(User $user){
        $inactive = $user->active ? 0 : 1;
        $password = 'NULL';
        if($user->password != null) $password = "'".hash('sha256', $user->password)."'";
        $sql = "INSERT OR REPLACE INTO User (name, password, inactive) VALUES ('{$page->name}', {$password}, {$inactive});";
        return $this->connection->exec($sql) > 0;
    }

    public function isValidUser($username, $password){
        if($password == null) $password = 'IS NULL';
        else $password = "= '".hash('sha256', $password)."'";

        $sql = "SELECT COUNT(*) FROM User WHERE name LIKE '{$username}' AND password {$password};";
        $q = $this->connection->query($sql);
        return $q->fetchColumn() > 0;
    }

    private function getUserId($username){
        $sql = "SELECT id FROM User WHERE name LIKE '{$username}';";
        $result = $this->connection->query($sql)->fetchColumn();
        if($result == false) $result = null;
        return $result;
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
