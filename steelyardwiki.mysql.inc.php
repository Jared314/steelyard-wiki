<?php

class MySqlRepository implements IRepository {
    private $connection;
	private $table_prefix = '';
    function __construct($host, $dbname, $user, $password, $prefix = '') {
		$this->connection = new PDO("mysql:host=$host;dbname=$dbname", $user, $password);
		$this->table_prefix = $prefix;
    }
    function __destruct() {
        $this->connection = null;
    }

    public function find($criteria, $currentOnly = true){
        return ($criteria instanceof UserCriteria)? $this->findUser($criteria) : $this->findPage($criteria, $currentOnly);
    }
    private function findPage(PageCriteria $criteria, $currentOnly = true){
        //Todo: use all the criteria fields
        $sql = "SELECT * FROM {$this->table_prefix}CurrentPage WHERE name LIKE '{$criteria->name[0]}' ORDER BY created DESC;";
        if(!$currentOnly) $sql = "SELECT Page.name as name, Page.value as value, Page.created as created, Page.inactive as inactive, User.name as username, Page.type as type FROM {$this->table_prefix}Page as Page LEFT JOIN {$this->table_prefix}User as User ON User.id = Page.user_id WHERE Page.name LIKE '{$criteria->name[0]}' ORDER BY Page.created DESC;";
        $q = $this->connection->query($sql);
		if(!($this->connection->errorCode() === '00000')){
			$error = $this->connection->errorInfo();
			throw new Exception($error[2]);
		}
		
        $result = array();
        foreach($q as $row){ $result[] = new Page($row); }
        return $result;
    }
    private function findUser(UserCriteria $criteria){
        //Todo: use all the criteria fields
        $sql = "SELECT id, name, inactive FROM {$this->table_prefix}User WHERE name LIKE '{$criteria->name[0]}';";
        $q = $this->connection->query($sql);
		if(!($this->connection->errorCode() === '00000')){
			$error = $this->connection->errorInfo();
			throw new Exception($error[2]);
		}
		
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
        $value = addslashes($page->value);
        $sql = "INSERT INTO {$this->table_prefix}Page (name, value, user_id, inactive, type, is_binary) VALUES ('{$page->name}','{$value}', {$user_id}, {$inactive}, '{$page->type}', {$is_binary});";
        return $this->connection->exec($sql) > 0;
    }
    private function saveUser(User $user){
        $inactive = $user->active ? 0 : 1;
        $password = 'NULL';
        if($user->password != null) $password = "'".hash('sha256', $user->password)."'";
        $id = $user->getId();
        if($id == null) $id = $this->getUserId($user->name);

        $sql = '';
        if($id == null) $sql = "INSERT INTO {$this->table_prefix}User (name, password, inactive) VALUES ('{$user->name}', {$password}, {$inactive});";
        else $sql = "UPDATE {$this->table_prefix}User SET name = '{$user->name}', password = {$password}, inactive = {$inactive} WHERE id = {$id}";
        
        return $this->connection->exec($sql) > 0;
    }

    public function isValidUser($username, $password){
        if($password == null) $password = 'IS NULL';
        else $password = "= '".hash('sha256', $password)."'";
		$username = addslashes($username);
		
        $sql = "SELECT COUNT(id) FROM {$this->table_prefix}User WHERE name LIKE '{$username}' AND password {$password} AND inactive = 0;";
        $q = $this->connection->query($sql);
        return $q != false && $q->fetchColumn() > 0;
    }
    public function hasUsers(){
        $sql = "SELECT COUNT(id) FROM {$this->table_prefix}User;";
        $q = $this->connection->query($sql);
        return $q != false && $q->fetchColumn() > 0;
    }

    public function create(){
    	//TODO: fill in create sql
    	return false;
        $result = false;
        $user = "CREATE TABLE IF NOT EXISTS `{$this->table_prefix}User` (`id` INTEGER PRIMARY KEY  auto_increment NOT NULL , `name` VARCHAR(512) NOT NULL , `password` VARCHAR(512), `inactive` INTEGER DEFAULT 0) ENGINE=InnoDB DEFAULT CHARSET=latin1;";
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
		//TODO: Fill in destroy sql
    	return false;
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
        $user = "DELETE FROM {$this->table_prefix}User;";
        $page = "DELETE FROM {$this->table_prefix}Page;";
        $success = ($this->connection->exec($user) > 0);
        if($success) $success = ($this->connection->exec($page) > 0);
        return $success;
    }
    public function isValidRepository(){
		//TODO: fill in repository check
    	return true;
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
        $sql = "SELECT id FROM {$this->table_prefix}User WHERE name LIKE '{$username}';";
        $result = $this->connection->query($sql)->fetchColumn();
        if($result == false) $result = 0;
        return $result;
    }
}

?>
