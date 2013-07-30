<?php

// Comments for qiki

require_once('SteinPDO.php');
require_once('User.php');  // to get the name of who made a comment
require_once('mysqli_read_column.php');
require_once('mysqli_read_row.php');
require_once('qikilink.php');
require_once('Scorer.php');

class Comment extends SteinTable {
	static protected $table = __CLASS__;
	// static protected /* SteinPDO */ $pdo;
	protected $row;
	public function __construct($id) {
		try {
			$this->row = Comment::$pdo->row("
				SELECT 
					*, 
					TIMESTAMPDIFF(SECOND, created, NOW()) as seconds_ago 
				FROM ".Comment::$table."
				WHERE comment_id = ?
			", array($id));
		} catch (PDOException $e) {
			die("Error reading comment - " . $e->getMessage());
		}
	}
	// static public function pdo($newpdo = NULL) {
		// if (!is_null($newpdo)) {
			// Comment::$pdo = $newpdo;
		// }
		// return Comment::$pdo;
	// }
	static public function insert($qomment, $qontributor, $kontext) {   // TODO:  Comment.qontributor -> Sentence.subject_id?
		try {
			$stmt = Comment::$pdo->prepare("
				INSERT INTO ".Comment::$table." ( qomment,  qontributor,  kontext, created) 
							             VALUES (       ?,            ?,        ?,   NOW())");
			$stmt->execute(array                ($qomment, $qontributor, $kontext         ));
		} catch (PDOException $e) {
			die("Error writing comment - " . $e->getMessage());
		}
		return new Comment(Comment::$pdo->lastInsertId());
	}
	static public function byRecency($opts) {   // returns recent Comment objects, array(id => Comment, ...)
		// $ids = Comment::$pdo->column("
			// SELECT comment_id 
			// FROM ".Comment::$table."
			// $WHEREclause
			// ORDER BY created DESC
			// LIMIT ?
		// ", array($opts['limit']));   // Note: requires setting PDO::ATTR_EMULATE_PREPARES to FALSE
		$ids = Comment::fetchem($opts);
		$retval = array();
		foreach($ids as $id) {
			$retval[$id] = new Comment($id);
		}
		return $retval;
	}
	static public function byKontext($kontext, $opts = array()) {   // returns Comment objects about a qiki, array(id => Comment, ...)
		$opts += array(
			'kontext' => $kontext,
		);
		// $ids = Comment::$pdo->column("
			// SELECT comment_id 
			// FROM ".Comment::$table."
			// WHERE kontext=? 
			// ORDER BY created DESC
		// ", array($kontext));
		$ids = Comment::fetchem($opts);
		$retval = array();
		foreach($ids as $id) {
			$retval[$id] = new Comment($id);
		}
		return $retval;
	}
	static protected function fetchem($opts) {
		$opts += array(
			'limit' => '10',        // max number of comments to return
			'minlevel' => 'anon',	// 'anon' or 'user' -- minimum level of comment author to include
			'kontext' => '',        // e.g. 'php/strlen'
			'totalrows' => &$countRowsRegardlessOfLimit,
			'scorer' => NULL,
			'minscore' => NULL,
			'maxscore' => NULL,
			'client_id' => NULL,   // e.g. UserQiki::$client->id() for the scoring context
		);
		$vars = array();
		$wheres = array();
		switch ($opts['minlevel']) {
		case 'anon': 
			break;
		case 'user': 
			$ALLDIGITS = "^[[:digit:]]+\$";
			$wheres[] = "qontributor RLIKE '$ALLDIGITS'"; 
			break;  
		default: 
			die("Comment::fetchem(array('minlevel'=>'$opts[minlevel]'))");
		}
		if ($opts['kontext'] == '') {
			
		} else {
			$wheres[] = "kontext = ?";
			$vars[] = $opts['kontext'];
		}
		
		if ($wheres == array()) {
			$WHEREclause = '';
		} else {
			$WHEREclause = 'WHERE ' . join(' AND ', $wheres);
		}
		if ($opts['limit'] == '') {
			$LIMITclause = '';
		} else {
			$LIMITclause = "LIMIT ?";
			$vars[] = $opts['limit'];
		}
		$ids = Comment::$pdo->column("
			SELECT SQL_CALC_FOUND_ROWS 
				comment_id 
			FROM ".Comment::$table."
			$WHEREclause
			ORDER BY created DESC
			$LIMITclause
		", $vars);
		$opts['totalrows'] = intval(Comment::$pdo->cell("SELECT FOUND_ROWS()"));
		if (!is_null($opts['scorer'])) {
			$scorer = new Scorer($opts['scorer']);
			foreach ($ids as $k => $id) {
				$score = $scorer->score(array(
					NounClass::Comment => $id, 
					'client_id' => $opts['client_id'],
				));
				if ((!is_null($opts['minscore']) && $score < $opts['minscore'])
				 || (!is_null($opts['maxscore']) && $score > $opts['maxscore'])) {
					unset($ids[$k]);
				}
			}
		}
		return $ids;
	}
	public function htmlQomment() {
		return qikilinkifyText(nl2br(htmlspecialchars(trim($this->row['qomment']))));
	}
	public function htmlKontext() {
		return qikilinkWhole($this->row['kontext']);
	}
	public function whotype() {
		if (1 == preg_match('/^\\d+$/', $this->row['qontributor'])) {
			return 'user';
		} else if (1 == preg_match('/^\\d+\\.\\d+\\.\\d+\\.\\d+$/', $this->row['qontributor'])) {
			return 'ipaddress';
		} else {
			return 'unknown';
		}
	}
	public function id() {
		return $this->row['comment_id'];
	}
	public function kontext() {
		return $this->row['kontext'];
	}
	public function qomment() {
		return $this->row['qomment'];
	}
	public function who() {
		switch ($this->whotype()) {
		case 'user':
			$whouser = new User(Comment::$pdo);
			$whouser->byId($this->row['qontributor']);
			$retval = $whouser->name();
			break;
		case 'ipaddress':
			$retval = $this->row['qontributor'];
			break;
		default:
			$retval = "???";
			break;
		}
		return htmlspecialchars($retval);
	}
	public function whoshort() {
		// TODO: less tricky, ricketty reliance here on htmlspecialchars() being called by $this->who(): neither foiling processing here, nor needing more encoding
		// TODO: subsume the "by " text that is prepended by the caller, into the span-title hover-hint (somehow)
		$who = $this->who();
		switch ($this->whotype()) {
		case 'user':
			$whoshort = strtok($who, ' ');
			$wholong = "user $who";
			break;
		case 'ipaddress':
			$whoshort = preg_replace('/\\.\\d+\\.\\d+\\./', '&hellip;', $who);
			$wholong = "IP address $who";
			break;
		default:
			$whoshort = "?";
			$wholong = "contributor type '{$this->whotype()}'";
			break;
		}
		return "<span class='whoshort' title=\"$wholong\">$whoshort</span>";
	}
	public function created() {
		return htmlspecialchars($this->row['created']);
	}
	public function seconds_ago() {
		return $this->row['seconds_ago'];
	}
	public function ago() {
		$seconds_ago = $this->seconds_ago();
		if ($seconds_ago < 2) {
			return 'now';
		} else if ($seconds_ago <             1.5*60) {
			return $this->agormat($seconds_ago,                 's', 'second');
		} else if ($seconds_ago <          1.5*60*60) {
			return $this->agormat($seconds_ago/           (60), 'm',  'minute');
		} else if ($seconds_ago <       1.5*24*60*60) {
			return $this->agormat($seconds_ago/        (60*60), 'h', 'hour');
		} else if ($seconds_ago <     2.0*7*24*60*60) {
			return $this->agormat($seconds_ago/     (24*60*60), 'd', 'day');
		} else if ($seconds_ago <    2.0*30*24*60*60) {
			return $this->agormat($seconds_ago/   (7*24*60*60), 'w', 'week');
		} else if ($seconds_ago <   2.0*365*24*60*60) {
			return $this->agormat($seconds_ago/(30.5*24*60*60), 'mon', 'month');
		} else {
			return agormat($seconds_ago/ (365*24*60*60), 'y', 'year');
		}
	}
	public function agormat($x, $unit, $unitword) {
		$n = strval(round($x));
		$a = (($unitword == 'hour') ? 'an' : 'a');
		if ($n == 1) {
			return "<span class='agormat' title='about $a $unitword ago: {$this->created()}'>$n$unit ago</span>";
		} else {
			return "<span class='agormat' title='about $n {$unitword}s ago: {$this->created()}'>$n$unit ago</span>";
		}
	}
}
