<?php

// Comments for qiki

require_once('SteinPDO.php');
require_once('User.php');  // to get the name of who made a comment
// require_once('qikilink.php');
require_once('Verb.php');
require_once('Scorer.php');
require_once('QikiQuestion.php');
require_once('alldigits.php');

class Comment extends SteinTable {   // Represents not just a noun (class/info pair), but an entire sentence+clause with a Comment-noun in the clause
	// static protected $table = __CLASS__;
	// static protected /* SteinPDO */ $pdo;
	protected $row;
	protected $qikiQuestion;
	public function id() {
		return $this->row['sentence_id'];
	}
	public function kontext() {
		return $this->row['kontext'];
	}
	public function qomment() {
		return $this->row['qomment'];
	}
	public function __construct($id) {
		$this->row = self::$pdo->row("
			SELECT
				s.*,
				c.noun_info AS qomment,
				TIMESTAMPDIFF(SECOND, s.created, NOW()) as seconds_ago 
			FROM ".Sentence::$table." AS s
			JOIN ".Clause::$table." AS c
				ON c.sentence_id  = s.sentence_id
				AND c.noun_class = '".NounClass::Comment."'
			WHERE s.sentence_id = ?
		", array($id));
		
		switch ($this->row['object_class']) {
		case NounClass::QikiQuestion:
			$this->qikiQuestion = new QikiQuestion($this->row['object_id']);
			$this->row['kontext'] = $this->qikiQuestion->question();
			break;
			// TODO: support comments on other objects
		default:
			$this->qikiQuestion = NULL;
			$this->row['kontext'] = "(unknown context for {$this->row['object_class']})";
			break;
		}
		// try {
			// $this->row = Comment::$pdo->row("
				// SELECT 
					// *, 
					// TIMESTAMPDIFF(SECOND, created, NOW()) as seconds_ago 
				// FROM ".Comment::$table."
				// WHERE comment_id = ?
			// ", array($id));
		// } catch (PDOException $e) {
			// die("Error reading comment - " . $e->getMessage());
		// }
	}
	// static public function pdo($newpdo = NULL) {
		// if (!is_null($newpdo)) {
			// Comment::$pdo = $newpdo;
		// }
		// return Comment::$pdo;
	// }
	static public function insert($verbname, $qomment, $qontributor, $kontext) {   // TODO:  Comment.qontributor -> Sentence.subject_id?
		// No -- $question = preg_replace('#^/#', '', $kontext, 1);   // kontext starts with a slash, question doesn't, or do we call that a qontext.  This war will end at some other place and time.
		$qq = QikiQuestion::factoryBold($kontext);
		$verb = new Verb($verbname);
		if (alldigits($qontributor)) {   // TODO:  UserQiki::MurkyFactory($idorIP) makes a Noun object, User or IP, depending on content
			$subject = new Noun(NounClass::User, $qontributor);
		} else {
			$subject = new Noun(NounClass::IPAddress, sprintf("%u", ip2long($qontributor)));   //  in case PHP is 32-bit, make unsigned
		}
		$sentence_id = $verb->state(array(
			'subject' => $subject,
			'object' => $qq->noun(),
			'clause' => array(new Noun(NounClass::Comment, $qomment)),
			'op' => 'insert',
		));

			// try {
				// $stmt = Comment::$pdo->prepare("
					// INSERT INTO ".Comment::$table." ( qomment,  qontributor,  kontext, created) 
											 // VALUES (       ?,            ?,        ?,   NOW())");
				// $stmt->execute(array                ($qomment, $qontributor, $kontext         ));
			// } catch (PDOException $e) {
				// die("Error writing comment - " . $e->getMessage());
			// }

		return $sentence_id;    // Comment::$pdo->lastInsertId());
	}
	static public function byRecency($opts) {   // returns recent Comment objects, array(id => Comment, ...)
		$ids = Comment::fetchem($opts);
		$retval = array();
		foreach($ids as $id) {
			$retval[$id] = new Comment($id);
		}
		return $retval;
	}
	static public function byKontext($kontext, $opts = array()) {   // returns Comment objects about a qiki, array(id => Comment, ...)
		$opts['kontext'] = $kontext;
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
			'kontext' => '',        // QikiQuestion, e.g. 'php/strlen'
			'totalrows' => &$countRowsRegardlessOfLimit,
			'scorer' => NULL,
			'minscore' => NULL,
			'maxscore' => NULL,
			'client_id' => NULL,   // e.g. UserQiki::client()->id() for the scoring context
		);
		$vars = array();
		$wheres = array();
		switch ($opts['minlevel']) {
		case 'anon': 
			break;
		case 'user': 
			// $ALLDIGITS = "^[[:digit:]]+\$";
			// $wheres[] = "qontributor RLIKE '$ALLDIGITS'"; 
			$wheres[] = "s.subject_class = '".NounClass::User."'";
			break;  
		default: 
			die("Comment::fetchem(minlevel => $opts[minlevel])");
		}
		if ($opts['kontext'] != '') {
			$qq = new QikiQuestion($opts['kontext']);
			if ($qq->is()) {
				$wheres[] = "s.object_class = '".NounClass::QikiQuestion."'";
				$wheres[] = "s.object_id = ?";
				$vars[] = $qq->id();
			} else {
				$wheres[] = "FALSE";  // can't be any comments here, if the QikiQuestion was never recorded
			}
		}
		
		$WHEREclause = empty($wheres) ? '' : 'WHERE ' . join(' AND ', $wheres);
		if ($opts['limit'] == '') {
			$LIMITclause = '';
		} else {
			$LIMITclause = "LIMIT ?";
			$vars[] = $opts['limit'];
		}
		
		
		
		$ids = self::$pdo->column("
			SELECT SQL_CALC_FOUND_ROWS 
				s.sentence_id 
			FROM ".Sentence::$table." AS s
			JOIN ".Clause::$table." AS c
				ON c.sentence_id  = s.sentence_id
				AND c.noun_class = '".NounClass::Comment."'
			$WHEREclause
			ORDER BY s.created DESC
			$LIMITclause
		", $vars);
		$opts['totalrows'] = intval(self::$pdo->cell("SELECT FOUND_ROWS()"));
		
		

		if (!is_null($opts['scorer'])) {
			$scorer = new Scorer($opts['scorer']);
			foreach ($ids as $k => $id) {
				$score = $scorer->score(array(
					NounClass::Sentence => $id, 
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
		if (is_null($this->qikiQuestion)) {
			return $this->qomment();
		} else {
			return QikiQuestion::translateMarkdown(nl2br(htmlspecialchars(trim($this->qomment()))));
		}
	}
	public function htmlKontext() {
		if (is_null($this->qikiQuestion)) {
			return '?';
		} else {
			return $this->qikiQuestion->link();
		}
	}
	public function whotype() {
		return strtolower($this->row['subject_class']);
	}
	public function who() {
		switch ($this->whotype()) {
		case 'user':
			$whouser = new User(Comment::$pdo);   // TODO:  would UserQiki() work here?
			$whouser->byId($this->row['subject_id']);
			$retval = $whouser->name();
			break;
		case 'ipaddress':
			$retval = long2ip($this->row['subject_id']);
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
