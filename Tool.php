<?php

// Tools for qiki

require_once('SteinPDO.php');
require_once('alldigits.php');

final class ToolObjects {   // for the Tool_associations.object field
	const Tool    = 'Tool';      
	const Comment = 'Comment';   
	const User    = 'User';
}

class ToolAssociation extends SteinTable {
	static public $table = "ToolAssociation";  // accessible to Tool or ToolAssociation
	protected $row;
	public $tool;
	public function __construct($id) {
		// ToolAssociation::checkPdo();
		$this->row = ToolAssociation::$pdo->row("
			SELECT *
			FROM ".ToolAssociation::$table." 
			WHERE toolAssociation_id = ?
		", array($nameorid));
		$this->tool = new Tool($row['tool_id']);
	}
	public function id() { return $this->row['toolAssociation_id']; }
	public function value() { return doubleval($this->row['value']); }
	public function icon($opts) {
		if ($this->value() == 0.0) {
			return "";
		} else if ($this->value() == 1.0) {
			return $this->tool->img($opts);
		} else {
			return $this->tool->img($opts) . "&times;" . strval($this->value());
		}
	}
}
class Tool extends SteinTable {
	static public $table = "Tool";  // accessible to Tool or ToolAssociation
	//static protected /* SteinPDO */ $pdo;
	protected $row;
	public function __construct($nameorid) {
		// Tool::checkPdo();
		if (alldigits($nameorid)) {
			$this->row = Tool::$pdo->row("
				SELECT *
				FROM ".Tool::$table." 
				WHERE tool_id = ?
			", array($nameorid));
		} else {
			$this->row = Tool::$pdo->row("
				SELECT *
				FROM ".Tool::$table." 
				WHERE tool_name = ?
			", array($nameorid));
		}
	}
	// static public function pdo($newpdo = NULL) {
		// if (!is_null($newpdo)) {
			// Tool::$pdo = $newpdo;
		// }
		// return Tool::$pdo;
	// }
	public function associate($obj, $objid, $qontributor) {
		try {
			$toolid = $this->id();
			$stmt = Tool::$pdo->prepare("
				INSERT INTO ". ToolAssociation::$table." (    tool_id, object, object_id,  qontributor, value)
							                      VALUES (          ?,      ?,         ?,            ?,   1.0) ON DUPLICATE KEY UPDATE value = value + 1.0");
			$stmt->execute(array                         (    $toolid,   $obj,    $objid, $qontributor));
		} catch (PDOException $e) {
			die("Error associating {$this->name()} with a $obj - " . $e->getMessage());
		}
	}
	// static public function checkPdo() {
		// if (!isset(Tool::$pdo) || !(Tool::$pdo instanceof SteinPDO)) {
			// die('Before using the Tool class you must set Tool::$pdo.');
		// }
	// }
	public function id() {
		return $this->row['tool_id'];
	}
	public function name() {
		return $this->row['tool_name'];
	}
	static public function all($clause = ' ORDER BY tool_id ASC ') {
		Tool::checkPdo();
		$ids = Tool::$pdo->column("SELECT tool_id FROM ".Tool::$table." $clause");
		$retval = array();
		foreach ($ids as $id) {
			$retval[] = new Tool($id);
		}
		return $retval;
	}
	static public function associations($obj, $objid) {   // array(toolname => value, ...)
		$retval = Tool::$pdo->column("
			SELECT 
				t.tool_name,
				sum(a.value)
			FROM ".ToolAssociation::$table." AS a
			JOIN ".Tool::$table." AS t
				USING(tool_id)
			WHERE a.object = ?
				AND a.object_id = ?
			GROUP BY tool_id
			ORDER BY tool_id ASC
		", array($obj, $objid));
		return $retval;
	}
	public function img($opts = array()) {
		$opts += array(
			'title' => $this->name(),
			'src' => $this->row['url'],
			'class' => '',
			'id' => NULL,
			'attr' => '',
		);
		if (is_null($opts['id'])) {
			$idattr = '';
		} else {
			$idattr = "id='$opts[id]'";
		}
		return 
			"<img "
				."$idattr "
				."class='tool-qiki tool-{$this->name()} $opts[class]' "
				."data-tool='{$this->name()}' "
				."src='$opts[src]' "
				."title='$opts[title]' "
				."$opts[attr] "
			."/>";
	}
	
	static public function htmlThanks($opts = array()) {
		$opts += array(
			'item' => '<li>',
		);
		foreach (Tool::all() as $tool) {
			echo $opts['item'];
			echo "Thanks to ";
			echo "<a href=\"{$tool->row['urlDesigner']}\" target='_blank'>";
				echo $tool->row['nameDesigner'];
			echo "</a>";
			echo " for the ";
			echo $tool->img();
			echo ' ';
			echo "<a href=\"{$tool->row['urlSource']}\" target='_blank'>";
				echo $tool->name();
			echo "</a>";
			echo " icon.";
			echo "\n";
		}
	}
}

?>