<?php

// Tools for qiki

require_once('SteinPDO.php');
require_once('alldigits.php');

class Tool {
	static public /* SteinPDO */ $pdo;
	public $row;
	public function __construct($nameorid) {
		if (alldigits($nameorid)) {
			$this->row = Comment::$pdo->row("
				SELECT *
				FROM Tools 
				WHERE tool_id = ?
			", array($nameorid));
		} else {
			$this->row = Comment::$pdo->row("
				SELECT *
				FROM Tools 
				WHERE tool_name = ?
			", array($nameorid));
		}
	}
	public function id() {
		return $this->row['tool_id'];
	}
	public function name() {
		return $this->row['tool_name'];
	}
	static public function all() {
		$ids = Tool::$pdo->column("SELECT tool_id FROM Tools ORDER BY tool_id ASC");
		$retval = array();
		foreach ($ids as $id) {
			$retval[] = new Tool($id);
		}
		return $retval;
	}
	public function img($opts = array()) {
		$opts += array(
			'title' => $this->name(),
			'src' => $this->row['url'],
			'classes' => '',
		);
		return "<img class='tool-qiki $opts[classes]' src='$opts[src]' title='$opts[title]' />";
	}
}

?>