<?php
class Settings{
	public static function getRepository(){
		$repository = new SqliteRepository('db.sqlite');
		return $repository;
	}
}
?>
