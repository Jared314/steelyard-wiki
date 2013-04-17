<?php
class Settings{
	public static function getRepository(){
		return new SqliteRepository('db.sqlite');
	}
	
	public static function getBinaryDataRepository(){
		return new FileBinaryDataRepository();
	}
}
?>
