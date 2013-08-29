<?php

// Verb class for qiki

require_once('SteinPDO.php');
require_once('Noun.php');
require_once('UnusedNoun.php');
require_once('alldigits.php');
require_once('Sentence.php');


// Possible Verbs and sentence structures
// --------------
// noun1 versus noun2 (with user-wrote aux sentence) ratio of value -- 0.5 means noun2 is twice the value of noun 1, how to do zero or infinite?
// user publish/privatize feature 
// user requires-qlout (to see) feature -- 100 means based on some scoring, some level of qlout is required to see the feature
// user thinks-verb-choice-wrong (in a) sentence -- 0=not at all, 1=there's a better verb, 2=egregiously wrong choice
// user sees qiki (ala access_log)

class Verb implements DataTable, NounLean   // was extends SteinTable 
{
	// static public $table = __CLASS__;  // accessible to Verb or Scorer
	//static protected /* SteinPDO */ $pdo;
    
	protected $row;
	
	public function __construct($name) 
    {
        $this->row = array(
            'verb_id' => FALSE,
            'name' => $name,
            'urlImage' => '',
        );
		// self::checkPdo();
		// if (alldigits($nameorid)) {
            // $id = $nameorid;
			// $this->row = static::pdo()->row("
				// SELECT *
				// FROM ".self::tablename()." 
				// WHERE verb_id = ?
			// ", array($id));
		// } else {
            // $name = $nameorid;
			// $this->row = static::pdo()->row("
				// SELECT *
				// FROM ".self::tablename()." 
				// WHERE name = ?
			// ", array($name));
            // if ($this->row == array()) {
                // $this->row['name'] = $name;
            // }
		// }
	}
    public function info() 
    {
        return $this->row['name'];
    }
    public function name() 
    {
        return $this->row['name'];
    }
    public function assertValid($classname = NULL)
    {
        NounLean_assertValid($this, $classname);
        assertTrue(1 === preg_match("#^\\w+$#", $this->name()), "Verb::name invalid characters");
        // TODO?? !$that->is() or $that->id() is in the Verb.verb_id database
    }
    
                    // public function is() 
                    // {
                        // $row = static::pdo()->row("
                            // SELECT *
                            // FROM ".static::tablename()." 
                            // WHERE name = ?
                        // ", array($this->name()));
                        // if ($row == array()) {
                            // assertFailure("Verb::id() on non-stored verb");
                            // return FALSE;
                        // }
                        // $this->row = $row;
                        // return TRUE;
                    // }
                    
    static public function selectId($id) 
    {
        assertTrue(alldigits($id));
		$row = static::pdo()->row("
			SELECT *
			FROM ".static::tablename()." 
			WHERE verb_id = ?
		", array($id));
        if ($row === array()) {
            return FALSE;
        }
        $that = new static($row['name']);
        $that->row = $row;
        return $that;
    }
	static public function selectInfo($name) 
    {
         $row = static::pdo()->row("
            SELECT *
            FROM ".static::tablename()." 
            WHERE name = ?
        ", array($name));
        if ($row == array()) {
            return FALSE;   // TODO:  return new static($name)?  assertFailure()?
        }
        $that = new static($row['name']);
        $that->row = $row;
        return $that;
    }
	public function id() 
    {
        // $this->is();
        return $this->row['verb_id'];
	}
    public function stow()
    {
        assertFailure("Not implemented, Verb::stow()");
    }
    
    
    
                public function state_NOTUSEDANYMORE($opts)   // sentence maker, changer.  Returns $sentence_id normally, or array of them if $opts['object'] is an array.
                {
                    // Whadaya know, the order that seems to make the most sense is verb-object-subject.  Ala Mayan Tzotzil.
                    // http://en.wikipedia.org/wiki/Verb%E2%80%93object%E2%80%93subject

                    $opts += array(
                        'object'  => new Noun(NounClass::UnusedNoun, '0'),   // or array(Noun, Noun, ...)
                        'subject' => new Noun(NounClass::UnusedNoun, '0'),
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
                        assertTrue(alldigits($opts['object']->ninfo()), "Verb::state() object non numeric id: '" . $opts['object']->ninfo() . "'");
                        assertTrue(alldigits($opts['subject']->ninfo()), "Verb::state() subject non numeric id: '" . $opts['subject']->ninfo() . "'");
                        
                        $equs = array();                 $vars = array();
                        $equs[] =       "verb_id = ?";   $vars[] = $this->id();
                        $equs[] =  "object_class = ?";   $vars[] = $opts['object']->nclass();
                        $equs[] =  "object_id    = ?";   $vars[] = $opts['object']->ninfo();
                        $equs[] = "subject_class = ?";   $vars[] = $opts['subject']->nclass();
                        $equs[] = "subject_id    = ?";   $vars[] = $opts['subject']->ninfo();
                        
                        // TODO:  new SentenceFactoryOrSomething($this, $opts['object'], $opts['subject'], ...)
                        // instantiating a NounClass without insisting a database record exist yet (or doesn't exist)
                        // and then $sentence->delta(-1); or $sentence->op('delta', -1); or something

                        assertTrue(isset($opts['op']), "Verb::state() missing op");
                        switch ($opts['op']) {
                        case 'set':
                        case 'delta':
                            $row = $this->sentenceSelect($equs, $vars);
                            if ($row === array()) {
                                $sentence_id = $this->sentenceInsert($equs, $vars, $opts['value']);
                                $this->clauseSet($sentence_id, $opts['clause']);
                                $opts['changecount'] = 1;
                            } else {
                                assertTrue($this->sentenceSelectFound() == 1, "Verb::state() expected to find 1 sentence, not " . $this->sentenceSelectFound());
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
                            assertFailure("Verb::state() bad op '$opts[op]'");
                        }
                        return $sentence_id;
                    }
                }
                
                protected $sentenceSelectFound;
                protected function sentenceSelect($equs, $vars) {
                    assertTrue($equs != array());
                    $retval = static::pdo()->row("
                        SELECT SQL_CALC_FOUND_ROWS *
                        FROM ".Sentence::tablename()."
                        WHERE ".join(' AND ', $equs)."
                        LIMIT 1
                    ", $vars);
                    $this->sentenceSelectFound = static::pdo()->rowsFound();
                    return $retval;
                }
                protected function sentenceSelectFound() {
                    return $this->sentenceSelectFound;
                }
                
                protected function sentenceInsert($equs, $vars, $newvalue) {
                    $equs[] = "value = ?";         
                    $vars[] = $newvalue;
                    
                    $equs[] = "created = NOW()";
                    
                    $stmt = static::pdo()->prepare("
                        INSERT INTO ".Sentence::tablename()."
                        SET ".join(',', $equs)."
                    ");
                    $stmt->execute($vars);
                    return static::pdo()->lastInsertId();
                }
                
                protected function sentenceModified($sentence_id) {   // because the clause was modified
                    $stmt = static::pdo()->prepare("
                        UPDATE ".Sentence::tablename()." 
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
                        assertFailure('Verb::sentenceUpdate bad op $opts[op]');
                    }
                    $SETclause .= ", modified = NOW()";   // TODO: modified only if clause or something else changed
                    $stmt = static::pdo()->prepare("
                        UPDATE ".Sentence::tablename()." 
                        $SETclause
                        WHERE sentence_id = ?
                        LIMIT 1   /* just in case */
                    ");
                    $stmt->execute(array($opts['value'], $row['sentence_id']));
                }
                
                protected function clauseSet($sentence_id, $nouns) {   // returns TRUE if record changed, FALSE otherwise
                    assertTrue(is_array($nouns), "Verb::clauseInsert() expecting an array, not a " . gettype($nouns));  // TODO recursively allow a Noun, or array of Noun
                    $retval = FALSE;
                    foreach ($nouns as $noun) {
                        assertTrue($noun instanceof Noun, "Verb::clauseInsert() expecting an array of Noun, not of " . get_class($noun));
                        
                        $equs1 = array();             $vars1 = array();
                        $equs1[] = "sentence_id = ?"; $vars1[] = $sentence_id;
                        $equs1[] = "noun_class = ?";  $vars1[] = $noun->nclass();
                        $oldninfo = static::pdo()->cell("
                            SELECT noun_info
                            FROM ".Clause::tablename()."
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
                        $stmt = static::pdo()->prepare("
                            INSERT INTO ".Clause::tablename()."
                            SET ".join(',', $equs2)."
                            ON DUPLICATE KEY UPDATE ".join(',', $upds2)."
                        ");
                        $stmt->execute($vars2);
                        
                        $newninfo = static::pdo()->cell("
                            SELECT noun_info
                            FROM ".Clause::tablename()."
                            WHERE ".join(' AND ', $equs1)."
                        ", $vars1);
                        if ($newninfo === FALSE || $newninfo !== $noun->ninfo()) {
                            assertFailure("
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
                            // $stmt = static::pdo()->prepare("
                                // INSERT INTO ". Sentence::tablename()." (    verb_id, object_class, object_id, subject_class, subject_id,   value, created)
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
                        // $stmt = static::pdo()->prepare("
                            // INSERT INTO ". Sentence::tablename()." (    verb_id, object_class, object_id, subject_class, subject_id,    value, created)
                                                       // VALUES (          ?,            ?,         ?,             ?,          ?,        ?,   NOW()) ON DUPLICATE KEY UPDATE value = ?");
                        // $stmt->execute(array                  (    $verbid,    $objclass,    $objid,       $sclass,       $sid, $setting,                                   $setting));
                    // } catch (PDOException $e) {
                        // die("Error setting {$this->name()} with a $objclass - " . $e->getMessage());
                    // }
                // }
    
	static public function all($opts = array()) 
    {
		$ids = static::pdo()->column("SELECT verb_id FROM ". static::tablename()." ORDER BY verb_id ASC");
		$retval = array();
		foreach ($ids as $id) {
			$retval[] = Verb::selectId($id);
		}
		return $retval;
	}
	
	// Should associations() return an array of Verb instances?
	// Or maybe Sentence instances!  
	// But then those instances would not represent a SINGLE row in the table but a SET of rows.  Ah...
	
	static public function associations($opts = array()) {   // array(verb_name => value, ...)   // TODO: move to Sentence!  There it's more like a fuzzy group selector
		$opts += array(
            'subject' => NULL,
            'object' => NULL,
			'verb' => NULL,
			
			/*     Verb::classname() => $verb_id,    */   //
			/* UserQiki::classname() => $user_id,    */   // pick one of these to specify the object, or none of these to sum over all objects
			/* Sentence::classname() => $comment_id, */   //
			
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
		if (!is_null($opts['verb'])) {
			$wheretests[] = "s.verb_id = ?";
			$queryparameters[] = $opts['verb']->id();
		}
		if (!is_null($opts['subject'])) {
			$wheretests[] = "s.subject_class = ?";
			$queryparameters[] = $opts['subject']->classname();
			$wheretests[] = "s.subject_id = ?";
			$queryparameters[] = $opts['subject']->id();
		}
		if (!is_null($opts['object'])) {
			$wheretests[] = "s.object_class = ?";
			$queryparameters[] = $opts['object']->classname();
			$wheretests[] = "s.object_id = ?";
			$queryparameters[] = $opts['object']->id();
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
			assertFailure("Verb::associations() unknown order => $opts[order]");
		}
		$retval = static::pdo()->column("
			SELECT 
				v.name,
				SUM(s.value) AS total
			FROM ".Sentence::tablename()." AS s
			JOIN ".static::tablename()." AS v
				USING(verb_id)
			$WHEREclause
			GROUP BY verb_id
			HAVING MIN(s.value) != 0   # weed out retracted ratings
				OR MAX(s.value) != 0
			$ORDERclause
		", $queryparameters);
		return $retval;
	}
	public function img($opts = array()) 
    {
        if (FALSE === $this->id()) {
            return "((({$this->info()})))";
        }
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
		foreach (static::all() as $verb) {
            if (!$verb->is()) {
                echo "(({$this->info()}))\n";
                continue;
            }
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
	
	static public function qoolbar($user_id) {   // TODO: $opts += array('order' => 'often', 'userid' => n...wtfWasIthinkingHere ) ... cries out for an object model for scoring
		$verbsIveUsed = static::associations(array('subject_class' => UserQiki::classname(), 'subject_id' =>  $user_id, 'order' => 'often'));
		$verbsEveryone = static::associations(array( /* 'order' => 'old', */ 'order' => 'often'));
		$verbsUnusedByMe = array_diff_key($verbsEveryone, $verbsIveUsed);
		
		$retval = '';
		$retval .= "<div class='qoolbar'>";
		$verbTool = static::selectInfo('tool');
		$retval .= $verbTool->img(array('class' => ''));
		if (count($verbsIveUsed) > 0 || count($verbsUnusedByMe) > 0) {
			$retval .= "<p>&middot;&middot;&middot;</p>\n";
		}
		$retval .= static::showverbs($verbsIveUsed, array('class' => 'qool'));
		if (count($verbsIveUsed) > 0 && count($verbsUnusedByMe) > 0) {
			$retval .= "<p>&middot;&middot;&middot;</p>\n";
		}
		$retval .= static::showverbs($verbsUnusedByMe, array('class' => 'qool'));
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
			$verb = static::selectInfo($verbname);
			$imgopts = array('class' => $opts['class']);
			foreach ($opts as $pos => $values) {
				if (is_array($values) && isset($values[$verbname])) {
					$imgopts += array($pos => $values[$verbname]);  // Translate e.g.   showverbs([like], [postsub=>[like=>3]])   to   Verb(like)->img([postsub=>3])
				}
			}
			$retval .= $verb->img($imgopts);
		}
		return $retval;
	}
    static public function classname() 
    {
        return NounClass::Verb;
    }
    static public function tablename() 
    {
        return __CLASS__;
    }
    static public function pdo() 
    {
        return SteinTable::pdo();
    }
}
