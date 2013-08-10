<?php

// Verb class for qiki

require_once('SteinPDO.php');
require_once('alldigits.php');


interface NounClass {   // for the Sentence.object_class and Sentence.subject_class fields
	const Verb         = 'Verb';     	// don't be alarmed, the word "verb" is a noun, this might be used e.g. to state User X likes Verb Y
	const Comment      = 'Comment';     // human answers (clause only)
	const User         = 'User';		// TODO: how to handle new User versus new UserQiki?
 	const QikiQuestion = 'QikiQuestion';// 
 	const IPAddress    = 'IPAddress';	// and store the ID using *unsigned* ip2long() 
	const Preference   = 'Preference';	// user preference, e.g. seeing anonymous comments, or spam
	const Unused       = 'Unused';		// for sentences with 1 noun or 0 nouns??
	const Sentence     = 'Sentence';	// (a sentence can refer to another sentence, e.g. for 3 or more nouns)
	const Author       = 'Author';      // maybe it should be called Variant, but it tracks the different answers, or something

	const Script       = 'Script';		// for UserQiki::may(UserQikiAccess::blah, NounClass::Script)     (NOTE: only NounClass that isn't a php class name AND isn't a qiki database table name)
										// Instead: qiki/qiki/software/xxxxx.php?  Or let a sentence make that association?
	const QikiML       = 'QikiML';      // Qiki answers (clause only)
	
// 	const URL
//	const Server or Site				// as in, a satellite qiki.somewheresite.com
//	const Scorer
//	const Feature instead of Preference?
//	const Language						// (human)
//	const Proposal						// a call for human action, e.g. development, censure, mediation, etc., including bounties and anti-bounties (donations if it fails)
//	const Time							// 64 bit DATETIME, for users to say when stuff happened, e.g. [Time]->(born)->[User/...]  or  ->[Qiki/qiki/human/...]
//	const AideMemoire					// VisiBone cheatsheet, example2 or syntax3
//	const Group or Badge				// Anonymous, Human, address-verified, bot, vouched-for, SuperUser, Developer, badges ala Stackexchange, native as in language

}
// Note these names must be secure, as they are not protected from injection attack (no check made whether they have ' in them).



function allNounClasses() {
	$r = new ReflectionClass('NounClass');
	return $r->getConstants();
}
function isNounClass($nclass) {
	return in_array($nclass, allNounClasses());
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
    protected /* LeanNoun */ $subject;
    public $verb;
    protected /* LeanNoun*/ $object;
    
    public function assertValid() {
        $subject->assertValid();
        $verb->assertValid();
        $object->assertValid();
    }
    
	static public $table = __CLASS__;  // accessible to Verb or Sentence or Scorer or Preference
	protected $row;
	public $verb;
	public function __construct($id) {
		// WTF was this?   $this->
		// clearly these grommets were never instantiated
		
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

class Clause {
	static public $table = __CLASS__;
    protected $sentence_id;
    protected /* FatNoun */ $noun;
    public function assertValid() {
        // $sentence_id is in Sentence.sentence_id table, or self::NO_ID
        $noun->assertValid();
    }
}

class Noun {
	protected $nclass, $ninfo;
	public function __construct($nclass, $ninfo) {
		assssert(gettype($ninfo) == 'string', "Noun info must be a string, not " . gettype($ninfo));
		assssert(isNounClass($nclass), "Not a noun class '$nclass' (a " . gettype($nclass) . ")");
		$this->nclass = $nclass;
		$this->ninfo = $ninfo;
	}
	public function nclass() {
		return $this->nclass;
	}
	public function ninfo() {
		return $this->ninfo;
	}
	static public function factory($nclasses, $ninfos) {
		if (is_array($nclasses) && is_array($ninfos)) {
			if (count($nclasses) != count($ninfos)) {
				die("Noun::factory() array count mismatch, class has " . count($nclasses) . " and info has " . count($ninfos));
			}
			$retval = array();
			while ($nclasses != array()) {
				$retval[] = new self(array_shift($nclasses), array_shift($ninfos));
			}
			return $retval;
		} else {
			return new self($nclasses, $ninfos);
		}
	}
	// TODO:  instantiate the object??  e.g. $this->instance = new $this->nclass()($this->info());   // But it breaks on UserQiki, maybe use pclass() instead of nclass()
	// Or should all nounific classes extend Noun?  Instead of SteinTable, which is just a stoopid global globbifier anyway.  Yea this makes more sense.
    public function assertValid() {
        assssert(isNounClass($this->nclass()));
    }
}

class LeanNoun extends Noun {
    public function assertValid() {
        // class is one of the enums of Sentence.object_class or Sentence.subject_class
        // info is alldigits()
        parent::assertValid();
    }
}

class FatNoun extends Noun {
    public function assertValid() {
        // class is a string, 255 char max, in
        // info is a string, 65K max
        parent::assertValid();
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
		// self::checkPdo();
		if (alldigits($nameorid)) {
			$this->row = self::$pdo->row("
				SELECT *
				FROM ".self::$table." 
				WHERE verb_id = ?
			", array($nameorid));
		} else {
			$this->row = self::$pdo->row("
				SELECT *
				FROM ".self::$table." 
				WHERE name = ?
			", array($nameorid));
		}
	}
    public function assertValid() {
        // $this->id() is either NO_ID or in the Verb.verb_id database
    }
    
	public function id() {
		return $this->row['verb_id'];
	}
	public function name() {
		return $this->row['name'];
	}
	public function state($opts) {   // sentence maker, changer.  Returns $sentence_id normally, or array of them if $opts['object'] is an array.

		// Whadaya know, the order that seems to make the most sense is verb-object-subject.  Ala Mayan Tzotzil.
		// http://en.wikipedia.org/wiki/Verb%E2%80%93object%E2%80%93subject

		$opts += array(
			'object'  => new Noun(NounClass::Unused, '0'),   // or array(Noun, Noun, ...)
			'subject' => new Noun(NounClass::Unused, '0'),
			'clause' => array( /* Noun, Noun, ... */ ),
			'value' => 0,
			'changecount' => &$number_of_sentences_changed,
			
			/* op => set      force the value, if a sentence with this subject-verb-object exists already, overwrite */
			/* op => delta      add the value, if a sentence with this subject-verb-object did NOT exist, pretend it was zero */
			/* op => insert   always insert a new sentence, whether or not one already exists with this subject-verb-object */
			
			// TODO: instead of $opts['op'], 3 methods:  newSentence(), incSentence(), setSentence() (ala operators: new, +=, =) -- unified internally via 'op'?
		);
		if (is_array($opts['object'])) {
			$retval = array();
			$totalChanges = 0;
			while ($opts['object'] != array()) {
				$nextobject = array_shift($opts['object']);
				$retval[] = $this->state(array('object' => $nextobject) + $opts);
				$totalChanges += $opts['changecount'];
			}
			$opts['changecount'] = $totalChanges;
			return $retval;
		} else {
			assssert(alldigits($opts['object']->ninfo()), "Verb::state() object non numeric id: '" . $opts['object']->ninfo() . "'");
			assssert(alldigits($opts['subject']->ninfo()), "Verb::state() subject non numeric id: '" . $opts['subject']->ninfo() . "'");
			
			$equs = array();                 $vars = array();
			$equs[] =       "verb_id = ?";   $vars[] = $this->id();
			$equs[] =  "object_class = ?";   $vars[] = $opts['object']->nclass();
			$equs[] =  "object_id    = ?";   $vars[] = $opts['object']->ninfo();
			$equs[] = "subject_class = ?";   $vars[] = $opts['subject']->nclass();
			$equs[] = "subject_id    = ?";   $vars[] = $opts['subject']->ninfo();
			
			// TODO:  new SentenceFactoryOrSomething($this, $opts['object'], $opts['subject'], ...)
			// instantiating a NounClass without insisting a database record exist yet (or doesn't exist)
			// and then $sentence->delta(-1); or $sentence->op('delta', -1); or something

			assssert(isset($opts['op']), "Verb::state() missing op");
			switch ($opts['op']) {
			case 'set':
			case 'delta':
				$row = $this->sentenceSelect($equs, $vars);
				if ($row === array()) {
					$sentence_id = $this->sentenceInsert($equs, $vars, $opts['value']);
					$this->clauseSet($sentence_id, $opts['clause']);
					$opts['changecount'] = 1;
				} else {
					assssert($this->sentenceSelectFound() == 1, "Verb::state() expected to find 1 sentence, not " . $this->sentenceSelectFound());
					$this->sentenceUpdate($row, $opts);
					$sentence_id = $row['sentence_id'];
					$didClauseChange = $this->clauseSet($sentence_id, $opts['clause']);
					if ($didClauseChange) {
						$this->sentenceModified($sentence_id);
						$opts['changecount'] = 1;
					}
				}
				break;
			case 'insert':
				$sentence_id = $this->sentenceInsert($equs, $vars, $opts['value']);
				$this->clauseSet($sentence_id, $opts['clause']);
				$opts['changecount'] = 1;
				break;
			default:
				die("Verb::state() bad op '$opts[op]'");
			}
			return $sentence_id;
		}
	}
	
	protected $sentenceSelectFound;
	protected function sentenceSelect($equs, $vars) {
		assssert($equs != array());
		$retval = self::$pdo->row("
			SELECT SQL_CALC_FOUND_ROWS *
			FROM ".Sentence::$table."
			WHERE ".join(' AND ', $equs)."
			LIMIT 1
		", $vars);
		$this->sentenceSelectFound = self::$pdo->rowsFound();
		return $retval;
	}
	protected function sentenceSelectFound() {
		return $this->sentenceSelectFound;
	}
	
	protected function sentenceInsert($equs, $vars, $newvalue) {
		$equs[] = "value = ?";         
		$vars[] = $newvalue;
		
		$equs[] = "created = NOW()";
		
		$stmt = self::$pdo->prepare("
			INSERT INTO ".Sentence::$table."
			SET ".join(',', $equs)."
		");
		$stmt->execute($vars);
		return self::$pdo->lastInsertId();
	}
	
	protected function sentenceModified($sentence_id) {   // because the clause was modified
		$stmt = self::$pdo->prepare("
			UPDATE ".Sentence::$table." 
			SET modified = NOW()
			WHERE sentence_id = ?
			LIMIT 1   /* just in case */
		");
		$stmt->execute(array($sentence_id));
	}
	
	protected function sentenceUpdate($row, $opts) {
		$opts += array(
			/* op => set or delta */
			'value' => 0,
			'changecount' => &$whether_sentence_changed_zero_or_one,
		);
		switch ($opts['op']) {
		case 'set':    
			if (doubleval($row['value']) == doubleval($opts['value'])) {
				$opts['changecount'] = 0;
				return;
			} else {
				$opts['changecount'] = 1;
			}
			$SETclause = "SET value = ?";
			break;
		case 'delta':  
			if (doubleval($opts['value']) == 0.0) {
				$opts['changecount'] = 0;
			} else {
				$opts['changecount'] = 1;
			}
			$SETclause = "SET value = value + (?)"; 
			break;
		default: 
			die('Verb::sentenceUpdate bad op $opts[op]');
		}
		$SETclause .= ", modified = NOW()";   // TODO: modified only if clause or something else changed
		$stmt = self::$pdo->prepare("
			UPDATE ".Sentence::$table." 
			$SETclause
			WHERE sentence_id = ?
			LIMIT 1   /* just in case */
		");
		$stmt->execute(array($opts['value'], $row['sentence_id']));
	}
	
	protected function clauseSet($sentence_id, $nouns) {   // returns TRUE if record changed, FALSE otherwise
		assssert(is_array($nouns), "Verb::clauseInsert() expecting an array, not a " . gettype($nouns));  // TODO recursively allow a Noun, or array of Noun
		$retval = FALSE;
		foreach ($nouns as $noun) {
			assssert($noun instanceof Noun, "Verb::clauseInsert() expecting an array of Noun, not of " . get_class($noun));
			
			$equs1 = array();             $vars1 = array();
			$equs1[] = "sentence_id = ?"; $vars1[] = $sentence_id;
			$equs1[] = "noun_class = ?";  $vars1[] = $noun->nclass();
			$oldninfo = self::$pdo->cell("
				SELECT noun_info
				FROM ".Clause::$table."
				WHERE ".join(' AND ', $equs1)."
			", $vars1);
			if ($oldninfo !== FALSE && $oldninfo === $noun->ninfo()) {
				continue;
			}
			
			$retval = TRUE;
			$upds2 = array();
			$equs2 = $equs1;              $vars2 = $vars1;
			$equs2[] = "noun_info = ?";   $vars2[] = $noun->ninfo();
			$upds2[] = "noun_info = ?";   $vars2[] = $noun->ninfo();
			$stmt = self::$pdo->prepare("
				INSERT INTO ".Clause::$table."
				SET ".join(',', $equs2)."
				ON DUPLICATE KEY UPDATE ".join(',', $upds2)."
			");
			$stmt->execute($vars2);
			
			$newninfo = self::$pdo->cell("
				SELECT noun_info
				FROM ".Clause::$table."
				WHERE ".join(' AND ', $equs1)."
			", $vars1);
			if ($newninfo === FALSE || $newninfo !== $noun->ninfo()) {
				die("
					{$noun->nclass()} Clause Mismatch for sentence $sentence_id:
					<div>
						{$noun->ninfo()}
					</div>
						$newninfo
					<div>
					</div>
				");   // this happens e.g. when writing 8829 characters that MySQL UTF-8-ifies to "?"
			}
		}
		return $retval;
	}
	
	// DONE:  function state($subject, $verb, $object, $newValue or $deltaValue) where the 1st and 3rd parameters are Noun objects (and returns a Sentence object)

	
	// public function associate($objclass, $objid, $sclass, $sid, $delta = 1) {   // $object class and id can be tandem arrays
		// if (is_array($objclass) && is_array($objid)) {
			// if (count($objclass) != count($objid)) {
				// die("Verb::associate() object array count mismatch, class has " . count($objclass) . " and id has " . count($objid));
			// }
			// while ($objclass != array()) {
				// $nextclass = array_shift($objclass);
				// $nextid = array_shift($objid);
				// $this->associate($nextclass, $nextid, $sclass, $sid, $delta);
			// }
		// } elseif (is_string($objclass) && is_string($objid)) {
			// try {
				// $verbid = $this->id();
				// $sDelta = strval($delta);
				// $stmt = self::$pdo->prepare("
					// INSERT INTO ". Sentence::$table." (    verb_id, object_class, object_id, subject_class, subject_id,   value, created)
											   // VALUES (          ?,            ?,         ?,             ?,          ?,       ?,   NOW()) ON DUPLICATE KEY UPDATE value = value + (?)");
				// $stmt->execute(array                  (    $verbid,    $objclass,    $objid,       $sclass,       $sid, $sDelta,                                             $sDelta));
				// if ('00000' !== $stmt->errorCode()) die('Verb::associate() execute error ' . $stmt->errorCode());   // never happens, right?
			// } catch (PDOException $e) {
				// die("Verb::associate() error: {$this->name()} with a $objclass - " . $e->getMessage());
			// }
		// } else {
			// die("Verb::associate() object type error, class is a " . gettype($objclass) . " and id is a " . gettype($objid));
		// }
	// }
	// public function set($objclass, $objid, $sclass, $sid, $setting) {   // DONE:  MergeMorphMeld with associate()
		// try {
			// $verbid = $this->id();
			// $stmt = self::$pdo->prepare("
				// INSERT INTO ". Sentence::$table." (    verb_id, object_class, object_id, subject_class, subject_id,    value, created)
							               // VALUES (          ?,            ?,         ?,             ?,          ?,        ?,   NOW()) ON DUPLICATE KEY UPDATE value = ?");
			// $stmt->execute(array                  (    $verbid,    $objclass,    $objid,       $sclass,       $sid, $setting,                                   $setting));
		// } catch (PDOException $e) {
			// die("Error setting {$this->name()} with a $objclass - " . $e->getMessage());
		// }
	// }
	static public function all($opts = array()) {
		self::checkPdo();
		$ids = self::$pdo->column("SELECT verb_id FROM ". self::$table." ORDER BY verb_id ASC");
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
			
			/* NounClass::Verb     => $verb_id,    */   //
			/* NounClass::User     => $user_id,    */   // pick one of these to specify the object, or none of these to sum over all objects
			/* NounClass::Sentence => $comment_id, */   //
			
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
		$WHEREclause = empty($wheretests) ? '' : "WHERE " . join(' AND ', $wheretests);
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
		$retval = self::$pdo->column("
			SELECT 
				v.name,
				SUM(s.value) AS total
			FROM ".Sentence::$table." AS s
			JOIN ".self::$table." AS v
				USING(verb_id)
			$WHEREclause
			GROUP BY verb_id
			HAVING MIN(s.value) != 0   # weeds out retracted ratings
				OR MAX(s.value) != 0
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
		foreach (self::all() as $verb) {
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
		$verbsIveUsed = self::associations(array('subject_class' => NounClass::User, 'subject_id' =>  $user_id, 'order' => 'often'));
		$verbsEveryone = self::associations(array('order' => 'old', 'order' => 'often'));
		$verbsUnusedByMe = array_diff_key($verbsEveryone, $verbsIveUsed);
		
		$retval = '';
		$retval .= "<div class='qoolbar'>";
		$verbTool = new self('tool');
		$retval .= $verbTool->img(array('class' => ''));
		if (count($verbsIveUsed) > 0 || count($verbsUnusedByMe) > 0) {
			$retval .= "<p>&middot;&middot;&middot;</p>\n";
		}
		$retval .= self::showverbs($verbsIveUsed, array('class' => 'qool'));
		if (count($verbsIveUsed) > 0 && count($verbsUnusedByMe) > 0) {
			$retval .= "<p>&middot;&middot;&middot;</p>\n";
		}
		$retval .= self::showverbs($verbsUnusedByMe, array('class' => 'qool'));
		$retval .= "</div>";
		return $retval;
	}

	static public function showverbs($verbs, $opts = array()) {   // $verbs is array(verbname => ignored, ...)
		$opts += array(
			// 'classes' => 'verblist',
			'postsup' => array(),		// array(verbname => value, ...) as returned by Verb::associations()
			'postsub' => array(),		// array(verbname => value, ...) as returned by Verb::associations()
			'class' => '',				// class(es) for img
		);
		$retval = '';
		foreach ($verbs as $verbname => $value) {
			$verb = new Verb($verbname);
			$imgopts = array('class' => $opts['class']);
			foreach ($opts as $pos => $values) {
				if (isset($values[$verbname])) {
					$imgopts += array($pos => $values[$verbname]);
				}
			}
			$retval .= $verb->img($imgopts);
		}
		return $retval;
	}
}
