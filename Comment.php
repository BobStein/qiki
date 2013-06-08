<?php

// Comments for qiki

require_once('User.php');
require_once('mysqli_read_column.php');
require_once('mysqli_read_row.php');
require_once('qikilink.php');

class Comment {
	static public $pdo;
	public $row;
	public function __construct($id) {
		$this->row = mysqli_read_row(Comment::$pdo, "
			SELECT 
				*, 
				TIMESTAMPDIFF(SECOND, created, NOW()) as seconds_ago 
			FROM Comments 
			WHERE id=?
		", array($id));
	}
	static public function byRecency($kontext, $n) {
		$ids = mysqli_read_column(Comment::$pdo, "
			SELECT id 
			FROM Comments 
			ORDER BY created DESC
			LIMIT ?
		", array($n));
		$retval = array();
		foreach($ids as $id) {
			$retval[] = new Comment($id);
		}
		return $retval;
	}
	static public function byKontext($kontext, $orderclause = '') {
		$ids = mysqli_read_column(Comment::$pdo, "
			SELECT id 
			FROM Comments 
			WHERE kontext=? 
			$orderclause
		", array($kontext));
		$retval = array();
		foreach($ids as $id) {
			$retval[] = new Comment($id);
		}
		return $retval;
	}
	public function htmlQomment() {
		return qikilink(nl2br(htmlspecialchars(trim($this->row['qomment']))));
	}
	public function htmlKontext() {
		return qikilink("qiki:" . $this->row['kontext']);
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
		// TODO: subsume the "by " prepended by caller into the span-title hover-hint (somehow)
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

?>