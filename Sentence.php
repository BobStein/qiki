<?php


require_once('SteinPDO.php');
require_once('Noun.php');
require_once('UnusedNoun.php');
require_once('alldigits.php');
require_once('Verb.php');


/* 
Sentence
--------
A Sentence is a Noun-Verb-Noun association, with a value, and a time, and an id, e.g.
User wrote Comment      (value = # times edited, created is first, modified is latest)
Comment about Qiki      (value = replied-to comment??)
Comment about Comment?  (value = root comment??  No, cheating because then you couldn't have a commented likeX10 about a comment)
*/

/**
 * A Sentence is a Noun-Verb-Noun association, with a value, and a time, and an id, and maybe Clauses 
 * @category    qiki
 * @package     qiki
 * @author      Bob Stein
 */
class Sentence implements DataTable, NounLean
{
    // protected /* Verb */ $verb;
    // protected /* NounLean */ $object;
    // protected /* NounLean */ $subject;
    // protected $value;
    // protected $clausenouns = array(/* Noun */);
    // // // DONE: replace verb, object, subject with $info[] array containing them?
    
    private $id;
    private $info;
    private $modified; 
    private $seconds_ago;
    
    public function __construct($info)   // was array(Verb $verb, NounLean $object, NounLean $subject, $value = 0.0) 
    {
        assertTrue(is_array($info));
        $info += array(
            'verb' => Verb::selectInfo('comment'),
            'object' => new UnusedNoun,
            'subject' => new UnusedNoun,
            'value' => 0,
            'clausenouns' => array(),   // bad idea?  encourages erroneous new Sentence(array('clausenouns' => array($noun, $noun, ...)));
        );                              //                      should be  new Sentence(array('clausenouns' => array($nounclass => $noun, $nounclass => $noun, ...)));
        $this->info = $info;
                        
                        // $this->verb = $verb;
                        // $this->object = $object;
                        // $this->subject = $subject;
                        // $this->value($value);
                        // $this->clause($clausenouns);
                        
        $this->id = FALSE;
        $this->modified = FALSE;
        $this->seconds_ago = FALSE;
        $this->assertValid(static::classname());
    }
    public function assertValid($classname = NULL)
    {
        NounLean_assertValid($this, $classname);
        assertTrue($this->id === FALSE || alldigits($this->id), "Bad Sentence::\$id = ".var_export($this->id, TRUE));
        
        assertIsa($this->subject(), NounLean_classname());
        $this->subject()->assertValid(NounLean_classname());
        $this->verb()->assertValid(Verb::classname());
        assertIsa($this->object(), NounLean_classname());
        $this->object()->assertValid(NounLean_classname());
        Clause::assertValid($this->clause());
    }
                    // static private function assertValidObject($object_or_array) 
                    // {
                        // if (is_array($object_or_array)) {
                            // $objects = $object_or_array;
                            // foreach ($objects as $object) {
                                // static::assertValidObject($object);
                            // }
                        // } else {
                            // $object = $object_or_array;
                            // assertIsa($object, NounLean_classname());
                            // $object->assertValid();
                        // }
                    // }
    public function info() 
    {
        return $this->info;
                        // return array(
                            // 'verb' => $this->verb,
                            // 'object' => $this->object,
                            // 'subject' => $this->subject,
                            // 'value' => $this->value,
                            // 'clausenouns' => $this->clausenouns,
                        // );
    }
    public function id()
    {
                        // $this->is();   // TODO: this still bugs me, what if is() called id()?  Or if assertValid() called id()?  What then?!?  They ACTUALLY both did but I caught it.  Why fuss?  Cuz is() must NOT be called between construct & insert -- it could overwrite a new comment clause with some old one!!!
        return $this->id;
    }
                    // private function clausenouns()
                    // {
                        // return $this->info['clausenouns'];
                    // }
    public function __call($method, $arguments)   // getter (0-parameter) and setter (1-parameter) methods
    {   
        switch ($method) {
            case 'verb':
            case 'object':
            case 'subject':
            case 'value':
                if (isset($arguments[0])) {
                    $this->info[$method] = $arguments[0];
                }
                return $this->info[$method];
            
            default:
                assertFailure("Undefined method __call()ed, Sentence::$method()");
        }
    }
    public function clause($nounything = NULL)   // A Noun, a noun classname, or an array of such.  Returns a Noun or array of such or FALSE (meaning no clause of that nounclass here)
    {
        if (is_null($nounything)) {                                 // ->clause() returns an array of all clause nouns
            return $this->info['clausenouns'];
        } elseif (is_array($nounything)) {                          // aggregate setter: ->clause(array(noun, noun, ...))
                                                                    // aggregate getter: ->clause(array(nounclass, nounclass, ...)) returns array(noun, noun, ...)
            $arrayOfNouns = $noun;
            foreach ($arrayOfNouns as $nounlike) {
                $retval = array();
                $returnything = $this->clause($nounlike);
                if ($returnything !== FALSE) {
                    $retval[$noun::classname()] = $returnything;
                }
                return $retval;
            }
        } elseif ($nounything instanceof Noun) {                    // setter: ->clause($noun)
            $noun = $nounything;
            $this->info['clausenouns'][$noun::classname()] = $noun;
            return $noun;
        } elseif (isNounClass($nounything)) {                       // getter: ->clause($nounclass)
            $nounclass = $nounything;
            if (isset( $this->info['clausenouns'][$nounclass])) {
                return $this->info['clausenouns'][$nounclass];
            } else {
                return FALSE;
            }
        } else {
            assertFailure("Bad argument, Sentence::clause(... " . var_export($nounything, TRUE) . " ...)" . var_export(class_implements($nounything), TRUE));
        }
        // DONE: if NULL, return all clausenouns
        // TODO: ->clause(NounClass::ALL) ?
        // TODO: ->clause(NounClass::ALL()) ?
        // TODO: ->clause(NounClass::select($ID_ALL)) ?
    }

    
    
    /**
     * Is the sentence in the DataTable already?
     *
     * @return string|bool the sentence_id, or FALSE if none
     * @access public
     */
    
    // TODO: quiescent case minimal queries in is() or state()
    // TODO: repeat calls zero queries for is()
    
    // private $row;   // DONE: eliminated this property!  Was only used by stow() to detect a change in value
    static public function selectInfo($info)   // Read 1 sentence by content, or construct a husk object if not (info but no id)
    {
        $sentences = static::selectInfoArray($info, array('limit' => 1));
        if (empty($sentences)) {
            return new static($info);
        }
        assertTrue(count($sentences) == 1);
        assertTrue(static::rowsFound() == 1);
        return array_shift($sentences);
    }
    static public function selectInfoArray($info, $opts = array())   // Read sentences by content, returns array(sentence, sentence, ...), or array() if no match
    // $info['subject'] or $info['object'] can be a class name, an object, or an array of objects
    {
        $opts += array(
            /* e.g. 'limit' => '20', */
            'order' => 'recent',
        );
        $equates = array();
        $parameters = array();
        
        $JOINclause = '';
        if (isset($info['clause'])) {   // noun class
            assertTrue(isNounClass($info['clause']));
            $JOINclause = "
                JOIN ".Clause::tablename()." AS c
                    ON c.sentence_id = s.sentence_id
                    AND c.noun_class = ?
            ";
            $parameters[] = $info['clause'];
        }
        
        if (isset($info['subject'])) {
            static::selectInfoNoun($info['subject'], 'subject', $equates, $parameters);
        }
        if (isset($info['verb'])) {
            $info['verb']->assertValid(Verb::classname());
            $equates[] = "s.verb_id = ?";   
            $parameters[] = $info['verb']->id();
        }
        if (isset($info['object'])) {
            static::selectInfoNoun($info['object'], 'object', $equates, $parameters);
        }
        if (empty($equates)) {
            $WHEREclause = '';
        } else {
            $WHEREclause = 'WHERE ' . join(' AND ', $equates);
        }
        
		switch ($opts['order']) {
		case 'old':
			$ORDERclause = "ORDER BY s.sentence_id ASC";
			break;
		case 'new':
			$ORDERclause = "ORDER BY s.sentence_id DESC";
			break;
		case 'recent':
			$ORDERclause = "ORDER BY s.modified DESC";
			break;
		default:
			assertFailure("Sentence::selectInfoArray() unknown order => $opts[order]");
		}
        
        if (isset($opts['limit'])) {
            assertTrue(is_numeric($opts['limit']));
            $LIMITclause = "LIMIT ?";
            $parameters[] = $opts['limit'];
        } else {
            $LIMITclause = '';
        }
        
        $grid = static::pdo()->grid("
            SELECT SQL_CALC_FOUND_ROWS *
            FROM ".static::tablename()." AS s
            $JOINclause
            $WHEREclause
            $ORDERclause
            $LIMITclause
        ", $parameters);
        
        
        // static::pdo()->debugdump();
        
        
        static::$rowsFound = static::pdo()->rowsFound();
        
        $retval = array();
        foreach($grid as $row) {
            $retval[$row['sentence_id']] = static::constructRow($row);
        }
        return $retval;
    }
    static private function selectInfoNoun($nounsorta, $ject, &$equates, &$parameters)
    {
        if (is_string($nounsorta)) {  
            $classname = $nounsorta;
            assertTrue(isNounLeanClass($classname));
            $equates[] = "s.{$ject}_class = ?";   
            $parameters[] = $classname;
        } elseif (is_object($nounsorta)) {
            $noun = $nounsorta;
            $noun->assertValid(NounLean_classname());
            $equates[] = "s.{$ject}_class = ?";   
            $parameters[] = $noun->classname();
            $equates[] = "s.{$ject}_id = ?";   
            $parameters[] = $noun->id();   // If $noun->id() returns FALSE, the whole selectInfoArray() will return an empty array() -- that's good, right?
        // } elseif (is_array($nounsorta)) {
            // $nouns = $nounsorta;
            // if (!empty($nouns)) {
                // $ids = array();
                // $classnames = array();
                // foreach ($nouns as $noun) {
                    // $id = $noun->id();
                    // assertTrue($id !== FALSE);
                    // assertTrue(alldigits($id));
                    // $ids[] = $id;
                    // $classnames[] = $noun->classname();
                // }
                // $classnamesUnique = array_unique($classnames);
                // assertTrue(count($classnamesUnique) == 1, "Array of diverse $ject classes: " . join(', ', $classnames));
                // $idlist = join(',', $ids);
                // $equates[] = "s.{$ject}_class = ?";   
                // $parameters[] = array_shift($classnames);
                // $equates[] = "s.{$ject}_id IN ($idlist)";   
            // }
        } else {
            assertFailure("Sentence::selectInfoNoun() invalid $ject " . var_export($noun, TRUE));
        }
    }
    static private $rowsFound;
    static public function rowsFound() 
    {
        return static::$rowsFound;
    }
    
                    public function OBSOLETE_is() 
                    {
                        $opts += array(
                            'subject' => $this->subject(),
                            'object' => $this->object(),
                        );
                        assertTrue(!is_array($opts['subject']));   // TODO: return an array when subject is an array
                        assertTrue(!is_array($opts['object']));   // TODO: return an array when object is an array
                        list($equates, $parameters) = $this->equates_and_parameters($opts);
                        // TODO: don't look up on info what might have been created by select($id), nor what was just 2 nanoseconds ago looked up by is()
                        $row = static::pdo()->row("
                            SELECT SQL_CALC_FOUND_ROWS *
                            FROM ".static::tablename()."
                            WHERE ".join(' AND ', $equates)."
                        ", $parameters);
                        if ($row === array()) {
                            $this->id = FALSE;
                            return FALSE;
                        }
                        $this->id = $row['sentence_id'];
                        $this->rowsFound = static::pdo()->rowsFound();
                        $this->clause(Clause::select($this->id));
                        $this->table_freshen();
                        // DONE: call table_freshen() instead of setting these next three?  And instead of the bulky seconds_ago column in the above query
                        // $this->value($row['value']);
                        // $this->modified = $row['modified'];
                        // $this->seconds_ago = $row['seconds_ago'];
                        return TRUE;   // TODO caution: id may be set, but clauses will not be!
                    }

    // Note: Sentence::stow() will NOT call e.g. $this->subject()->stow().  If sub-nouns should be stowed, call them before constructing the sentence.
    public function stow($opts = array())
    {
        $opts['op'] = 'stow';
        $this->state($opts);
    }
    public function delta($opts = array())
    {
        $opts['op'] = 'delta';
        $this->state($opts);
    }
    public function insert($opts = array())
    {
        $opts['op'] = 'insert';
        $this->state($opts);
    }
    private function equates_and_parameters($opts = array())
    {
        $opts += array(
            'object' => $this->object(),
        );
        $equs = array();                 $vars = array();
        $equs[] =       "verb_id = ?";   $vars[] = $this->verb()->id();
        $equs[] =  "object_class = ?";   $vars[] = $opts['object']->classname();
        $equs[] =  "object_id    = ?";   $vars[] = $opts['object']->id();
        $equs[] = "subject_class = ?";   $vars[] = $this->subject()->classname();
        $equs[] = "subject_id    = ?";   $vars[] = $this->subject()->id();
        return array($equs, $vars);
    }
    public function state($opts = array())
    {
        $opts += array(
            'op' => 'stow',   // or 'delta' or 'insert' operation
            'changecount' => &$number_of_sentence_rows_changed,
            'object' => $this->object(),
            //'value' => 0 if table_update()ing, $this->info['value'] if table_insert()ing
        );
        if (is_array($opts['object'])) {
            foreach ($opts['object'] as $objectoid) {
                $this->state(array('object' => $objectoid) + $opts);
            }
            return;
        }
        switch ($opts['op']) {
            case 'stow':
            case 'delta':
                if ($this->id() === FALSE) {
                    $that = static::selectInfo($this->info());
                    $this->id = $that->id;
                }
                if ($this->id() === FALSE) {
                    $this->id = $this->table_insert($opts);
                    Clause::stow($this->id, $this->clause());
                    $this->table_freshen();
                    $opts['changecount'] = 1;
                } else {
                    $this->table_update($opts);
                    $did_value_change = $opts['changecount'];
                    $did_clause_change = Clause::stow($this->id, $this->clause());
                    if ($did_value_change || $did_clause_change) {
                        $this->table_modified();
                        $this->table_freshen();
                        $opts['changecount'] = 1;
                    }
                }
                break;
            case 'insert':
                $this->id = $this->table_insert($opts);
                Clause::stow($this->id, $this->clause());
                $this->table_freshen();
                $opts['changecount'] = 1;
                break;
            default:
                assertFailure("Bad operation, Sentence::state(op => $opts[op])");
        }
        // TODO:  $this->subject()->state($opts);   -- at bottom of this function so, if they're inserted, the rooty sentence has a higher id than the leafy sentence.
        // TODO:  $this->object()->state($opts);
    }
    private function table_update($opts = array())   // operation 'stow' stores $opts['value'] in the existing DataTable row, operation 'delta' adds it.  Expects is() to have been called already.
    {
        assertTrue(FALSE !== $this->id);
		$opts += array(
			/* op => stow or delta */
			'value' => 0,   // TODO: problem?  Attempting to stow something other than value in an existing record (with a nonzero value already) will zero it
			'changecount' => &$whether_sentence_changed_zero_or_one,
		);
        $opts['changecount'] = 0;
		switch ($opts['op']) {
		case 'stow':    
			if (doubleval($opts['value']) == doubleval($this->value())) {
				return;
			} 
			$SETclause = "SET value = ?";
			break;
		case 'delta':  
			if (doubleval($opts['value']) == 0.0) {
                return;
			}
			$SETclause = "SET value = value + (?)"; 
			break;
		default: 
			assertFailure("Bad operation, Verb::table_update(op => $opts[op])");
		}
        $opts['changecount'] = 1;
		$SETclause .= ", modified = NOW()";   // TODO: modified only if clause or something else changed
		$stmt = static::pdo()->prepare("
			UPDATE ".static::tablename()." 
			$SETclause
			WHERE sentence_id = ?
			LIMIT 1   /* just in case */
		");
		$stmt->execute(array($opts['value'], $this->id));
    }
    private function table_modified() 
    {
		$stmt = static::pdo()->prepare("
			UPDATE ".static::tablename()." 
			SET modified = NOW()
			WHERE sentence_id = ?
			LIMIT 1   /* just in case */
		");
		$stmt->execute(array($this->id));
    }
    private function table_freshen()   // freshen modified, seconds_ago, and value properties from the DataTable
    {
        assertTrue(FALSE !== $this->id);
        $rc = static::pdo()->row("
            SELECT 
                value,
                modified,
				TIMESTAMPDIFF(SECOND, modified, NOW()) AS seconds_ago 
            FROM ".static::tablename()."
            WHERE sentence_id = ?
        ", array($this->id));
        if (count($rc) == 3) {
            $this->value($rc['value']);
            $this->modified = $rc['modified'];
            $this->seconds_ago = $rc['seconds_ago'];
        } else {
            assertFailure("Sentence::table_freshen() where did sentence $this->id go?\n" . var_export($rc, TRUE));
        }
    }
    private function table_insert($opts = array()) 
    {
		$opts += array(
			/* op => stow or delta */
			'value' => $this->info['value'],
		);
        list($equs, $vars) = $this->equates_and_parameters($opts);
        $equs[] = "value = ?";
        $vars[] = $opts['value'];

        $equs[] = "created = NOW()";
        
		$stmt = static::pdo()->prepare("
			INSERT INTO ".static::tablename()."
			SET ".join(',', $equs)."
		");
		$stmt->execute($vars);
		return static::pdo()->lastInsertId();   
    }
    
    
    static private function constructRow($row)
    {
        if (empty($row)) {
            return FALSE;
        }
        $newinfo = array(
               'verb' =>                           Verb::selectId($row['verb_id']),
             'object' =>  NounLean_selectId($row['object_class'], $row['object_id']),   // TODO: limit recursion?  Sentence referring to itself?  Or a rating of a comment on a rating of a comment -- do you REALLY want to load all those comments into memory?
            'subject' => NounLean_selectId($row['subject_class'], $row['subject_id']),  // Maybe only allow newer, higher-numbered id's to link to older, lower-numbered id's
        'clausenouns' =>                         Clause::selectId($row['sentence_id']),
              // 'value' =>                                        $row['value'],
        );
        // TODO?   Avoid sentence recursion?   if ($row['subject_class'] != Sentence::classname()) $newinfo['subject'] = NounLean_selectId($row['subject_class'], $row['subject_id']);

        $that = new static($newinfo);
        $that->id = $row['sentence_id'];
        $that->table_freshen();
        // DONE: call table_freshen() instead of setting these next two?  And instead of the bulky seconds_ago column in the above query
        // $that->modified = $row['modified'];
        // $that->seconds_ago = $row['seconds_ago'];
        return $that;
    }
    static public function selectId($id)   // TODO: someday when we're REALLY smart, just store the id in the instance, and don't do any MySQL unless needed (e.g. never if only ->id() member is used)
    {
        assertTrue(alldigits($id));
		$row = static::pdo()->row("
			SELECT *
			FROM ".static::tablename()." 
			WHERE sentence_id = ?
		", array($id));
        return static::constructRow($row);
    }
	public function htmlModified() {
        if ($this->modified === FALSE) {
            return 'sometime';
        } else {
            return htmlspecialchars($this->modified);
        }
	}
    
    
    public function htmlLink() 
    {
        if (method_exists($this->object(), 'htmlLink')) {
            return $this->object()->htmlLink();
        } else {
            return htmlspecialchars($this->object()->info());
        }
    }
    
    public function htmlContent() 
    {
        $comment = $this->clause(Comment::classname());
        if ($comment === FALSE) {
            return '';
        }
        if (method_exists($comment, 'htmlContent')) {
            return $comment->htmlContent();
        } else {
            return htmlspecialchars($comment->info());
        }
    }
    
    
	public function whotype() {
		return $this->subject()->classname();
        // TODO: if the subject is not a "user" class then hunt for the earliest (lowest ID) sentence that objectifies this sentence, and look at THEIR subject
	}
	public function who() {
		switch ($this->whotype()) {
		case UserQiki::classname():
			$whouser = new User(static::pdo());   // TODO:  would UserQiki do some kind of better job here?
			$whouser->byId($this->subject()->id());   // was $this->row['subject_id']);
			$retval = $whouser->name();
			break;
		case IPAddress::classname():
			$retval = long2ip($this->subject()->id());   // was $this->row['subject_id']);
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
		case UserQiki::classname():
			$whoshort = strtok($who, ' ');
			$wholong = "user $who";
			break;
		case IPAddress::classname():
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



    
	public function seconds_ago() {
		return $this->seconds_ago;
	}
	public function ago() {
		$seconds_ago = $this->seconds_ago();
		if ($seconds_ago === FALSE) {
            return 'sometime';
        } elseif ($seconds_ago <                  2) {
			return                                              'now';
		} elseif ($seconds_ago <             1.5*60) {
			return $this->agormat($seconds_ago,                 's',   'second');
		} elseif ($seconds_ago <          1.5*60*60) {
			return $this->agormat($seconds_ago/           (60), 'm',   'minute');
		} elseif ($seconds_ago <       1.5*24*60*60) {
			return $this->agormat($seconds_ago/        (60*60), 'h',   'hour');
		} elseif ($seconds_ago <     2.0*7*24*60*60) {
			return $this->agormat($seconds_ago/     (24*60*60), 'd',   'day');
		} elseif ($seconds_ago <    2.0*30*24*60*60) {
			return $this->agormat($seconds_ago/   (7*24*60*60), 'w',   'week');
		} elseif ($seconds_ago <   2.0*365*24*60*60) {
			return $this->agormat($seconds_ago/(30.5*24*60*60), 'mon', 'month');
		} else {
			return $this->agormat($seconds_ago/ (365*24*60*60), 'y',   'year');
		}
	}
	private function agormat($x, $unit, $unitword) {
		$n = strval(round($x));
		$a = (($unitword == 'hour') ? 'an' : 'a');
		if ($n == 1) {
			return "<span class='agormat' title='about $a $unitword ago: {$this->htmlModified()}'>$n$unit ago</span>";
		} else {
			return "<span class='agormat' title='about $n {$unitword}s ago: {$this->htmlModified()}'>$n$unit ago</span>";
		}
	}
    
    
    
    static public function classname() 
    {
        return NounClass::Sentence;   // return NounClass::__CLASS__;
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




            class FormerlyKnownAs_Sentence extends SteinTable implements NounClass {
                static public $table = __CLASS__;  // accessible to Verb or Sentence or Scorer or Preference

                protected /* LeanNoun */ $subject;
                public $verb;
                protected /* LeanNoun*/ $object;
                
                public function assertValid() {
                    $subject->assertValid();
                    $verb->assertValid();
                    $object->assertValid();
                }
                
                protected $row;
                public function __construct($id) {
                    // WTF was this?   $this->
                    // clearly these grommets were never instantiated
                    
                    // Sentence::checkPdo();
                    $this->row = Sentence::pdo()->row("
                        SELECT *
                        FROM ".Sentence::tablename()." 
                        WHERE sentence_id = ?
                    ", array($nameorid));
                    $this->verb = Verb::selectInfo($row['verb_id']);
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

            
            
// A Clause associates an extra Noun with a Sentence 
// (that is, in addition to the subject and object Nouns)

// It can also be a big bulky ad hoc Noun, 
// that needs no id of its own because there will never be another use for the exact same info
// such as a Comment.

// Some lean nouns may never be stored in a Clause, such as a Sentence
// Other lean nouns might someday be stored in Clause,
// such as a QikiQuestion where stow() or id() are never called 
// (taking their place are Clause::stow() and Sentence::id())

// One Sentence may have multiple Clauses, 
// but no more than one per NounClass, 
// e.g. a Comment and an IPv6 address, 
// but never two Comments 
// (that would require two Sentences)

// But a Clause is not itself a Noun -- it has no id or info of its own

class Clause implements DataTable   // to store a noun so flagrantly unique as to never warrant an id, e.g. a Comment (actually, it does have a vicarious id through its sentence_id)
{
    // protected $sentence_id;
    // protected $noun;
    // public function assertValid() {
        // // // $sentence_id is in Sentence.sentence_id table, or self::NO_ID
        // $noun->assertValid();
    // }
    
    
    // These members, assertValid() and selectId() and stow(), could have been members of Sentence.
    // They probably won't be used by anything else anyway.
    // And they wouldn't have needed any parameters if they had been.
    static public function assertValid($nouns)
    {
        assertTrue(is_array($nouns));
        foreach ($nouns as $classname => $noun) {
            assertTrue($classname === $noun->classname(), "Clause noun '$classname' => '".var_export($noun->classname(), TRUE)."'");
            $noun->assertValid(Noun_classname());
        }
    }
    static public function selectId($sentence_id)   // (the clause getter) returns an array of Noun objects
    {
        $grid = static::pdo()->grid("
			SELECT *
			FROM ".static::tablename()." 
			WHERE sentence_id = ?
		", array($sentence_id));
        $nouns = array();
        foreach ($grid as $row) {
            $noun = Noun_factory($row['noun_class'], $row['noun_info']);
            // $noun = new $row['noun_class']($row['noun_info']);
            $nouns[$row['noun_class']] = $noun;
        }
        return $nouns;
    }
    static public function stow($sentence_id, $nouns)    // (the clause setter) Stow the clauses, TRUE means a record changed, FALSE none did
    {
        assertTrue(is_array($nouns));
        $retval = FALSE;
        foreach ($nouns as $noun) {
            $noun->assertValid();
        
            assertTrue(is_string($noun->info()));
            // TODO: this assumes $noun->info() is a string
            // what if $this->noun is a Sentence?  Detect by info() not being a string?  By having an id()?
            // Will we preserve LSP by detecting info() not a string and info() present, and storing id() instead of info()?
            // Assume for now that this is a waste of time.
            
 			$equs1 = array();             $vars1 = array();
			$equs1[] = "sentence_id = ?"; $vars1[] = $sentence_id;
			$equs1[] = "noun_class = ?";  $vars1[] = $noun->classname();
			$oldninfo = static::pdo()->cell("
				SELECT noun_info
				FROM ".static::tablename()."
				WHERE ".join(' AND ', $equs1)."
			", $vars1);
			if ($oldninfo !== FALSE && $oldninfo === $noun->info()) {
				continue;
			}
			
			$retval = TRUE;
			$upds2 = array();
			$equs2 = $equs1;              $vars2 = $vars1;
			$equs2[] = "noun_info = ?";   $vars2[] = $noun->info();
			$upds2[] = "noun_info = ?";   $vars2[] = $noun->info();
			$stmt = static::pdo()->prepare("
				INSERT INTO ".static::tablename()."
				SET ".join(',', $equs2)."
				ON DUPLICATE KEY UPDATE ".join(',', $upds2)."
			");
			$stmt->execute($vars2);
			
			$newninfo = static::pdo()->cell("
				SELECT noun_info
				FROM ".static::tablename()."
				WHERE ".join(' AND ', $equs1)."
			", $vars1);
			if ($newninfo === FALSE || $newninfo !== $noun->info()) {
				assertFailure("
					{$noun->classname()} Clause Mismatch for sentence $sentence_id:
					<div>
						{$noun->info()}
					</div>
						$newninfo
					<div>
					</div>
				");   // this happens e.g. when writing ISO-8829 characters that MySQL UTF-8-ifies to "?"
			}
        }
        return $retval;
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

            class Noun___NotSoFast_ThisWillNotBeUsed {
                protected $nclass, $ninfo;
                public function __construct($nclass, $ninfo) {
                    assertTrue(isNounClass($nclass), "Not a noun class '$nclass' (a " . gettype($nclass) . ")");
                    assertTrue(gettype($ninfo) == 'string', "Noun info must be a string, not " . gettype($ninfo));
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
                        assertTrue(count($nclasses) == count($ninfos), "Noun::factory() array count mismatch, " 
                            . "class has " . count($nclasses) 
                            . " and info has " . count($ninfos)
                        );
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
                    assertTrue(isNounClass($this->nclass()));
                }
            }

            class NounLean___NotSoFast_ThisWillNotBeUsed extends Noun___NotSoFast_ThisWillNotBeUsed  // noun-info is an id to a database table
            {
                static protected $table;
                protected function pdo() 
                {
                    return SteinTable::pdo();
                }
                public function assertValid() {
                    // class is one of the enums of Sentence.object_class or Sentence.subject_class
                    assertTrue(alldigits($this->ninfo));
                    
                    parent::assertValid();
                }
            }

            class NounFat___NotSoFast_ThisWillNotBeUsed extends Noun___NotSoFast_ThisWillNotBeUsed 
            {
                public function assertValid() {
                    // class is a string, 255 char max, in
                    // info is a string, 65K max
                    parent::assertValid();
                }
            }
