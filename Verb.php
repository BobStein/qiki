<?php

// Verb class for qiki

require_once('SteinPDO.php');
require_once('alldigits.php');



// These are not class names NOR database table names, but they are _class field (enum) values, e.g. qiki.Sentence.subject_class
// Sigh so the right thing to do is make a database of Nouns, with a column of class names.  (If there is a table, it can be named in <class name>::$table)

interface NounClass {   // for the Sentence.object_class and Sentence.subject_class fields
	const Verb       = 'Verb';      // don't be alarmed, the word "verb" is a noun, this might be used e.g. to state User X likes Verb Y
	const Comment    = 'Comment';   
	const User       = 'User';		// TODO: how to handle new User versus new UserQiki?
//	const Sentence					// (a sentence can refer to another sentence, e.g. for 3 or more nouns)
// 	const URL						// nonnumeric identification!
 	const Qiki		 = 'Qiki';		// nonnumeric identification!
 	const IPAddress	 = 'IPAddress';	// and store the ID using ip2long()
//	const Server or Site			// as in, a satellite qiki.somewheresite.com
//	const Scorer
	const Preference = 'Preference';// user preference, e.g. seeing anonymous comments, or spam
//	const Feature instead of Preference?
	const Script     = 'Script';	// for UserQiki::may(UserQikiAccess::blah, NounClass::Script)     (NOTE: only NounClass that isn't a php class name AND isn't a qiki database table name)
								    // Instead: qiki/qiki/software/xxxxx.php?  Or let a sentence make that association?
//	const Unused					// for sentences with 1 noun?
//	const Language					// (human)
//	const Proposal					// a call for human action, e.g. development, censure, mediation, etc., including bounties and anti-bounties (donations if it fails)
//	const Time						// 32 bit TIMESTAMP
//	const AideMemoire				// VisiBone cheatsheet, example2 or syntax3
}
// Note these names must be secure, as they are not protected from injection attack (no check made whether they have ' in them).



function allNounClasses() {
	$r = new ReflectionClass('NounClass');
	return $r->getConstants();
}

/* 
Sentence
--------
noun-verb-noun associations
User wrote Comment  (value = # times edited, created is first, modified is latest)
Comment about Qiki  (value = replied-to comment??)
Comment about Comment?  (value = root comment??)
*/

class Sentence extends SteinTable implements NounClass {
	static public $table = __CLASS__;  // accessible to Verb or Sentence or Scorer or Preference
	protected $row;
	public $verb;
	public function __construct($id) {
		// WTF was this?   $this->
		// Sentence::checkPdo();
		$this->row = Sentence::$pdo->row("
			SELECT *
			FROM ".Sentence::$table." 
			WHERE sentence_id = ?
		", array($nameorid));
		$this->verb = new Verb($row['verb_id']);
	}
	public function specify($id) {
	}
	public function id() { return $this->row['sentence_id']; }
	public function value() { return doubleval($this->row['value']); }
	public function icon($opts) {
		if ($this->value() == 0.0) {
			return "";
		} else if ($this->value() == 1.0) {
			return $this->verb->img($opts);
		} else {
			return $this->verb->img($opts) . "&times;" . strval($this->value());
		}
	}
}



// Possible Verbs and sentence structures
// --------------
// noun1 versus noun2 (with user-wrote aux sentence) ratio of value -- 0.5 means noun2 is twice the value of noun 1, how to do zero or infinite?
// user publish/privatize feature 
// user requires-qlout (to see) feature -- 100 means based on some scoring, some level of qlout is required to see the feature
// user thinks-verb-choice-wrong (in a) sentence -- 0=not at all, 1=there's a better verb, 2=egregiously wrong choice
// user sees qiki (ala access_log)

class Verb extends SteinTable {
	static public $table = __CLASS__;  // accessible to Verb or Scorer
	//static protected /* SteinPDO */ $pdo;
	protected $row;
	public function __construct($nameorid) {
		// Verb::checkPdo();
		if (alldigits($nameorid)) {
			$this->row = Verb::$pdo->row("
				SELECT *
				FROM ".Verb::$table." 
				WHERE verb_id = ?
			", array($nameorid));
		} else {
			$this->row = Verb::$pdo->row("
				SELECT *
				FROM ".Verb::$table." 
				WHERE name = ?
			", array($nameorid));
		}
	}
	public function id() {
		return $this->row['verb_id'];
	}
	public function name() {
		return $this->row['name'];
	}
	public function state($opts) {
		$opts += array(
			'object' => array(NounClass::User => $user_id),
			'subject'=> array(NounClass::User => $user_id),
		);
	}
	
	// TODO:  function state($subject, $verb, $object, $newValue or $deltaValue) where the 1st and 3rd parameters are Noun objects (and return a Sentence object)
	
	public function associate($objclass, $objid, $sclass, $sid, $delta = 1) {   // $object class and id can be tandem arrays
		if (is_array($objclass) && is_array($objid)) {
			if (count($objclass) != count($objid)) {
				die("Verb::associate() object array count mismatch, class has " . count($objclass) . " and id has " . count($objid));
			}
			while ($objclass != array()) {
				$nextclass = array_shift($objclass);
				$nextid = array_shift($objid);
				$this->associate($nextclass, $nextid, $sclass, $sid, $delta);
			}
		} elseif (is_string($objclass) && is_string($objid)) {
			try {
				$verbid = $this->id();
				$sDelta = strval($delta);
				$stmt = Verb::$pdo->prepare("
					INSERT INTO ". Sentence::$table." (    verb_id, object_class, object_id, subject_class, subject_id,   value, created)
											   VALUES (          ?,            ?,         ?,             ?,          ?,       ?,   NOW()) ON DUPLICATE KEY UPDATE value = value + (?)");
				$stmt->execute(array                  (    $verbid,    $objclass,    $objid,       $sclass,       $sid, $sDelta,                                             $sDelta));
				if ('00000' !== $stmt->errorCode()) die('Verb::associate() execute error ' . $stmt->errorCode());   // never happens, right?
			} catch (PDOException $e) {
				die("Verb::associate() error: {$this->name()} with a $objclass - " . $e->getMessage());
			}
		} else {
			die("Verb::associate() object type error, class is a " . gettype($objclass) . " and id is a " . gettype($objid));
		}
	}
	public function set($objclass, $objid, $sclass, $sid, $setting) {   // TODO:  MergeMorphMeld with associate()
		try {
			$verbid = $this->id();
			$stmt = Verb::$pdo->prepare("
				INSERT INTO ". Sentence::$table." (    verb_id, object_class, object_id, subject_class, subject_id,    value, created)
							               VALUES (          ?,            ?,         ?,             ?,          ?,        ?,   NOW()) ON DUPLICATE KEY UPDATE value = ?");
			$stmt->execute(array                  (    $verbid,    $objclass,    $objid,       $sclass,       $sid, $setting,                                   $setting));
		} catch (PDOException $e) {
			die("Error setting {$this->name()} with a $objclass - " . $e->getMessage());
		}
	}
	static public function all($opts = array()) {
		Verb::checkPdo();
		$ids = Verb::$pdo->column("SELECT verb_id FROM ". Verb::$table." ORDER BY verb_id ASC");
		$retval = array();
		foreach ($ids as $id) {
			$retval[] = new Verb($id);
		}
		return $retval;
	}
	
	// Should associations() return an array of Verb instances?
	// Or maybe Sentence instances!  
	// But then those instances would not represent a SINGLE row in the table but a SET of rows.  Ah...
	
	static public function associations($opts = array()) {   // array(verb_name => value, ...)
		$opts += array(
			'subject_class' => NULL,
			'subject_id' => NULL,
			'verb_name' => NULL,
			/* NounClass::Verb    => $verb_id,    */   //
			/* NounClass::User    => $user_id,    */   // pick one of these to specify the object, or none of these to sum over all objects
			/* NounClass::Comment => $comment_id, */   //
			'order' => 'old',						// old, new, recent, often
		);
		$wheretests = array();
		$queryparameters = array();
		foreach (allNounClasses() as $nounclass) {
			if (isset($opts[$nounclass])) {
				$wheretests[] = "s.object_class = ? AND s.object_id = ?";
				$queryparameters[] = $nounclass;
				$queryparameters[] = $opts[$nounclass];
				break;   // only first NounClass option matters
			}
		}
		if (!is_null($opts['subject_class'])) {
			$wheretests[] = "s.subject_class = ?";
			$queryparameters[] = $opts['subject_class'];
		}
		if (!is_null($opts['verb_name'])) {
			$wheretests[] = "s.subject_class = ?";
			$queryparameters[] = $opts['subject_class'];
		}
		if (!is_null($opts['subject_id'])) {
			$wheretests[] = "s.subject_id = ?";
			$queryparameters[] = $opts['subject_id'];
		}
		$WHEREclause = $wheretests == array() ? '' : "WHERE " . join(' AND ', $wheretests);
		switch ($opts['order']) {
		case 'old':
			$ORDERclause = "ORDER BY verb_id ASC";
			break;
		case 'new':
			$ORDERclause = "ORDER BY verb_id DESC";
			break;
		case 'recent':
			$ORDERclause = "ORDER BY MAX(s.modified) DESC";
			break;
		case 'often':
			$ORDERclause = "ORDER BY total DESC";
			break;
		default:
			die("Verb::associations() unknown order => $opts[order]");
			break;
		}
		$retval = Verb::$pdo->column("
			SELECT 
				v.name,
				sum(s.value) as total
			FROM ".Sentence::$table." AS s
			JOIN ".Verb::$table." AS v
				USING(verb_id)
			$WHEREclause
			GROUP BY verb_id
			$ORDERclause
		", $queryparameters);
		return $retval;
	}
	public function img($opts = array()) {
		$opts += array(
			'tooltip' => $this->name(),
			'postsup' => '',   // postfix superscript html (no escaping here)
			'postsub' => '',   // postfix subscript html
			'src' => $this->row['urlImage'],
			'class' => '',
			'id' => NULL,
			'attr' => '',
		);
		if (is_null($opts['id'])) {
			$idattr = '';
		} else {
			$idattr = "id='$opts[id]'";
		}
		$retval = '';
		$htmlTooltip = htmlspecialchars($opts['tooltip']);
		$postdata = '';
		if (trim($opts['postsup']) !== '') $postdata .= " data-postsup='$opts[postsup]' ";  // TODO: pass through strip_tags() and htmlspecialchars_decode()?
		if (trim($opts['postsub']) !== '') $postdata .= " data-postsub='$opts[postsub]' ";  // TODO: pass through strip_tags() and htmlspecialchars_decode()?
		$retval .= 
			"<span "
				."$idattr "
				."class='verb-qiki verb-{$this->name()} $opts[class]' "
				."title=\"$htmlTooltip\" "
				."data-verb='{$this->name()}' "
				."$opts[attr] "
				.$postdata
			.">";
			$retval .= "<img src='$opts[src]' />";
			if ($opts['postsup'] != '' || $opts['postsub'] != '') {
				// $retval .= '\\(';
				// if ($opts['postsup'] != '') $retval .= "^{{$opts['postsup']}}";
				// if ($opts['postsub'] != '') $retval .= "_{{$opts['postsub']}}";
				// $retval .= '\\)';
				$postsup = trim($opts['postsup']) === '' ? '&nbsp;' : trim($opts['postsup']);
				$postsub = trim($opts['postsub']) === '' ? '&nbsp;' : trim($opts['postsub']);
				$retval .= "<div class='verb-post'><div class='verb-postsup'>$postsup</div>".
				                                  "<div class='verb-postsub'>$postsub</div></div>\n";
			}
		$retval .= "</span>";
		return $retval;
	}
	
	static public function htmlThanks($opts = array()) {
		$opts += array(
			'item' => '<li>',
		);
		foreach (Verb::all() as $verb) {
			echo $opts['item'];
			echo "Thanks to ";
			echo "<a href=\"{$verb->row['urlDesigner']}\" target='_blank'>";
				echo $verb->row['nameDesigner'];
			echo "</a>";
			echo " for the ";
			echo $verb->img();
			echo ' ';
			echo "<a href=\"{$verb->row['urlSource']}\" target='_blank'>";
				echo $verb->name();
			echo "</a>";
			echo " icon.";
			echo "\n";
		}
	}
	
	static public function qoolbar($user_id) {   // TODO: $opts += array('order' => 'often', 'userid' => n...wtfWasIthinkingHere )
		$verbsIveUsed = Verb::associations(array('subject_class' => NounClass::User, 'subject_id' =>  $user_id, 'order' => 'often'));
		$verbsEveryone = Verb::associations(array('order' => 'old', 'order' => 'often'));
		$verbsUnusedByMe = array_diff_key($verbsEveryone, $verbsIveUsed);
		
		$retval = '';
		$retval .= "<div class='qoolbar'>";
		$retval .= Verb::showverbs($verbsIveUsed);
		$retval .= "<p>:</p>\n";
		$retval .= Verb::showverbs($verbsUnusedByMe);
		$retval .= "</div>";
		return $retval;
	}

	static public function showverbs($verbs, $opts = array()) {   // $verbs is array(verbname => ignored, ...)
		$opts += array(
			// 'classes' => 'verblist',
			'postsup' => array(),		// array(verbname => value, ...) as returned by Verb::associations()
			'postsub' => array(),		// array(verbname => value, ...) as returned by Verb::associations()
		);
		$retval = '';
		foreach ($verbs as $verbname => $value) {
			$verb = new Verb($verbname);
			$imgopts = array();
			foreach ($opts as $pos => $values) {
				if (isset($values[$verbname])) {
					$imgopts += array($pos => $values[$verbname]);
				}
			}
			$retval .= /* "<span class='$opts[classes]'>" . */ $verb->img($imgopts)/*  . "</span>" */;
		}
		return $retval;
	}
}
