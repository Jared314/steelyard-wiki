<?php
/*
 * Created By: Jared Lobberecht
 *
 */

class SteelyardWiki{
    public $repository;
    public $binaryRepository;
    public $request;
    public $data;

    public function __construct(IRepository &$repository = NULL, IRequest &$request = NULL, IBinaryDataRepository &$binaryRepository = NULL){
        $this->repository = $repository;
        $this->request = $request;
        $this->binaryRepository = $binaryRepository;
        if($repository != null && $request != null && $binaryRepository != null){
            if($repository->isValidRepository() || $repository->create()){
                $this->data = $this->processRequest($repository, $request, $binaryRepository);
            }
        }
    }

    function __destruct() {
        $this->request = null;
        $this->repository = null;
    }

    public function processRequest(IRepository &$repository, IRequest &$request, IBinaryDataRepository &$binaryRepository){
        $criteria = $request->getCriteria();
        $result = $repository->find($criteria);
        return ($result != null && count($result) > 0) ? $result[0] : new Page();
    }

    public function destroy(){
        return ($this->repository != null) ? $this->repository->destroy() : false;
    }

    public function create(){
        return ($this->repository != null) ? $this->repository->create() : false;
    }

    public function clear(){
        return ($this->repository != null) ? $this->repository->clear() : false;
    }

    public function isValid(){
        return ($this->repository != null) ? $this->repository->isValidRepository() : false;
    }

    public function getBinaryData(IBinaryDataRepository &$binaryRepository = null, Page $page = null){
        if($binaryRepository == null) $binaryRepository = $this->binaryRepository;
        if($page == null) $page = $this->data;
        if(!$page->isBinary) return null;
        return $binaryRepository->find($page->name);
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
    public $type = 'text/html';
    public $isBinary = false;

    function __construct(array $values = NULL) {
        if($values != null) $this->SetValues($values);
    }

    private function SetValues(array $values){
        if(isset($values['name'])) $this->name = $values['name'];
        if(isset($values['value'])) $this->value = $values['value'];
        if(isset($values['created'])) $this->created = $values['created'];
        if(isset($values['username'])) $this->username = $values['username'];
        if(isset($values['inactive'])) $this->active = ($values['inactive'] == 0);
        if(isset($values['type'])) $this->type = $values['type'];
        if(isset($values['is_binary'])) $this->isBinary = ($values['is_binary'] == 1);
    }
}

class PageCriteria {
    public $name = array();
    public $value = array();
    public $created = array();
    public $username = array();
    public $version = NULL;
    public $active = NULL;
}

class User {
    private $id;
    public $name = '';
    public $password = '';
    public $active = true;

    function __construct(array $values = NULL) {
        if($values != null) $this->SetValues($values);
    }

    private function SetValues(array $values){
        if(isset($values['id'])) $this->id = $values['id'];
        if(isset($values['name'])) $this->name = $values['name'];
        if(isset($values['inactive'])) $this->active = ($values['inactive'] == 0);
    }
    public function getId(){ return $this->id; }
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
    public function hasUsers();
    public function create();
    public function destroy();
    public function clear();
    public function isValidRepository();
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
        $sql = "SELECT * FROM CurrentPage WHERE name LIKE '{$criteria->name[0]}' ORDER BY created DESC;";
        if(!$currentOnly) $sql = "SELECT Page.name as name, Page.value as value, Page.created as created, Page.inactive as inactive, User.name as username, Page.type as type FROM Page LEFT JOIN User ON User.id = Page.user_id WHERE Page.name LIKE '{$criteria->name[0]}' ORDER BY Page.created DESC;";
        $q = $this->connection->query($sql);

        $result = array();
        foreach($q as $row){ $result[] = new Page($row); }
        return $result;
    }
    private function findUser(UserCriteria $criteria){
        //Todo: use all the criteria fields
        $sql = "SELECT id, name, inactive FROM User WHERE name LIKE '{$criteria->name[0]}';";
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
        $is_binary = $page->isBinary ? 1 : 0;
        $sql = "INSERT INTO Page (name, value, user_id, inactive, type, is_binary) VALUES ('{$page->name}','{$page->value}', {$user_id}, {$inactive}, '{$page->type}', {$is_binary});";
        return $this->connection->exec($sql) > 0;
    }
    private function saveUser(User $user){
        $inactive = $user->active ? 0 : 1;
        $password = 'NULL';
        if($user->password != null) $password = "'".hash('sha256', $user->password)."'";
        $id = $user->getId();
        if($id == null) $id = $this->getUserId($user->name);

        $sql = '';
        if($id == null) $sql = "INSERT INTO User (name, password, inactive) VALUES ('{$user->name}', {$password}, {$inactive});";
        else $sql = "UPDATE User SET name = '{$user->name}', password = {$password}, inactive = {$inactive} WHERE id = {$id}";
        
        return $this->connection->exec($sql) > 0;
    }

    public function isValidUser($username, $password){
        if($password == null) $password = 'IS NULL';
        else $password = "= '".hash('sha256', $password)."'";

        $sql = "SELECT COUNT(id) FROM User WHERE name LIKE '{$username}' AND password {$password} AND inactive = 0;";
        $q = $this->connection->query($sql);
        return $q != false && $q->fetchColumn() > 0;
    }
    public function hasUsers(){
        $sql = 'SELECT COUNT(id) FROM User;';
        $q = $this->connection->query($sql);
        return $q != false && $q->fetchColumn() > 0;
    }

    public function create(){
        $result = false;
        $user = 'CREATE TABLE IF NOT EXISTS "User" ("id" INTEGER PRIMARY KEY  AUTOINCREMENT  NOT NULL , "name" VARCHAR NOT NULL , "password" VARCHAR(512), "inactive" INTEGER DEFAULT 0);';
        $userIndex = 'CREATE UNIQUE INDEX IF NOT EXISTS "Unique_User_name" ON "User" ("name" ASC);';
        $page = 'CREATE TABLE IF NOT EXISTS "Page" ("id" INTEGER PRIMARY KEY  AUTOINCREMENT  NOT NULL , "name" VARCHAR(512) NOT NULL , "value" BLOB, "user_id" INTEGER NOT NULL , "created" DATETIME DEFAULT CURRENT_TIMESTAMP, "inactive" INTEGER DEFAULT 0, "type" VARCHAR(255), "is_binary" INTEGER DEFAULT 0);';
        $currentpage = 'CREATE VIEW IF NOT EXISTS "CurrentPage" AS SELECT Page.name as name, Page.value as value, Page.created as created, Page.inactive as inactive, User.name as username, Page.type as type, Page.is_binary as is_binary FROM Page LEFT JOIN User ON User.id = Page.user_id GROUP BY Page.name HAVING MAX(created) and Page.inactive = 0;';

        try {
            $this->connection->beginTransaction();

            //Order important
            $this->connection->exec($user);
            $this->connection->exec($userIndex);
            $this->connection->exec($page);
            $this->connection->exec($currentpage);

            $this->connection->commit();
            $result = true;
        } catch(PDOException $e) {
            $this->connection->rollBack();
        }

        return $result;
    }
    public function destroy(){
        $result = false;
        $currentpage = 'DROP VIEW IF EXISTS CurrentPage';
        $page = 'DROP TABLE IF EXISTS Page';
        $userIndex = 'DROP INDEX IF EXISTS Unique_User_name';
        $user = 'DROP TABLE IF EXISTS User';

        try {
            $this->connection->beginTransaction();

            //Order important
            $this->connection->exec($currentpage);
            $this->connection->exec($page);
            $this->connection->exec($userIndex);
            $this->connection->exec($user);

            $this->connection->commit();
            $result = true;
        } catch(PDOException $e) {
            $this->connection->rollBack();
        }

        return $result;
    }
    public function clear(){
        $user = 'DELETE FROM User;';
        $page = 'DELETE FROM Page;';
        $success = ($this->connection->exec($user) > 0);
        if($success) $success = ($this->connection->exec($page) > 0);
        return $success;
    }
    public function isValidRepository(){
        $valid = false;
        $tables = "SELECT COUNT(*) FROM sqlite_master WHERE tbl_name IN ('User','Page','CurrentPage','Unique_User_name');";
        $user = 'PRAGMA table_info(User);';
        $page = 'PRAGMA table_info(Page);';
        $currentPage = 'PRAGMA table_info(CurrentPage);';

        //Test required tables
        $tableCount = $this->connection->query($tables);
        $valid = ($tableCount != false && $tableCount->fetchColumn() == 4);

        if($valid){
            //Test required columns
            $pageColumns = $this->connection->query($page);
            $userColumns = $this->connection->query($user);
            $currentPageColumns = $this->connection->query($currentPage);

            $valid = $this->containsColumnNames($pageColumns, array('name', 'value', 'user_id', 'inactive', 'type', 'is_binary'))
                     && $this->containsColumnNames($userColumns, array('name','password','inactive'))
                     && $this->containsColumnNames($currentPageColumns, array('name','value','created','inactive', 'type'));
        }

        return $valid;
    }

    private function containsColumnNames(PDOStatement $data, array $expectedColumnNames){
        if($data == false) return false;
        
        $columns = array();
        foreach($data as $row) if(array_key_exists('name', $row)) $columns[] = $row['name'];

        $result = array_intersect($columns, $expectedColumnNames);
        return (count($result) == count($expectedColumnNames));
    }

    private function getUserId($username){
        $sql = "SELECT id FROM User WHERE name LIKE '{$username}';";
        $result = $this->connection->query($sql)->fetchColumn();
        if($result == false) $result = 0;
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
        $script = $_SERVER['SCRIPT_NAME'];
        $i = strrpos($script, '/');
        $basepath = substr($script, 0, $i + 1);
        $uri = parse_url($scriptUri);
        $info = pathinfo($uri['path']);
        return $uri['scheme'].'://'.$uri['host'].$basepath;
    }

    private function parseUrl($baseUrl, $url){
        return trim(str_replace($baseUrl, '', $url), '/');
    }
}

/*
 * Binary data classes
 */
interface IBinaryDataRepository{
    public function find($name);
    public function save($name, $value);
}

class FileBinaryDataRepository implements IBinaryDataRepository{
    private $basePath = './binaryData/';

    function __construct($basePath = NULL){
        if(!empty($basePath)){
            $this->basePath = rtrim(dirname($basePath), "/\\").'/';
        }
    }

    public function find($name){
    	$name = $this->formatName($name);
        $name = $this->findFile($this->basePath, $name);
        if(!empty($name) && is_file($this->basePath.$name)){
            return $this->getBinaryContents($this->basePath.$name);
        }
        return null;
    }

    public function save($name, $value){
        date_default_timezone_set('UTC');
        $name = $this->formatName($name);
        $name = $this->basePath.$name.date('YmdHis');
        $result = false;
        if(is_string($value) && is_file($value)){
            $result = move_uploaded_file($value, $name);
        }else{
            $result = $this->setBinaryContents($name, $value);
        }
        return $result;
    }

    private function findFile($base, $name){
        if (is_dir($base)) {
            $contents = scandir($base, 1);
            $filteredContents = array();
            foreach($contents as $item)
                if(eregi('^'.$name.'', $item))
                    $filteredContents[] = $item;
            
            if(count($filteredContents) > 0) return $filteredContents[0];
        }
        
        return null;
    }

	private function formatName($name){
		return str_replace( '/', '_', $name);
	}

    private function getBinaryContents($filename){
        $handle = fopen(realpath($filename), "rb");
        $contents = fread($handle, filesize(realpath($filename)));
        fclose($handle);
        return $contents;
    }
    private function setBinaryContents($filename, $value){
        $handle = fopen($filename, "wb");
        $bytesWritten = fwrite($handle, $value);
        fclose($handle);
        return $bytesWritten != false && $bytesWritten > 0;
    }
}
?>
