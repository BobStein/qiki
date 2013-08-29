<?php

define ('MAX_QIKI_PREFIX', 32);
define ('MAX_QIKI_SUFFIX', 255);

require_once('SteinPDO.php');
require_once('Noun.php'); 
require_once('Verb.php');
require_once('Author.php');
// require_once('qikilink.php');

class QikiQuestion implements NounLean, DataTable
{
	// static public $table = __CLASS__;  // accessible to Verb or Sentence or Scorer or Preference
	// const NO_ID = "-1";
	// protected $row;
    
    protected $prefix;
    protected $suffix;
    protected $id;
    
    public function __construct($question, $mayfix = NULL) 
    {
        list($this->prefix, $this->suffix) = static::unfix($question, $mayfix);
        $this->id = FALSE;
    }
    public function info() 
    {
		      if (                       $this->suffix == '') {
			return $this->prefix;
		} elseif ( $this->prefix == '') {
			return                       $this->suffix;
		} else {
			return $this->prefix . '/' . $this->suffix;
		}
    }
    public function question() 
    {
        return $this->info();
    }
    public function assertValid($classname = NULL)
    {
        NounLean_assertValid($this, $classname);
        assertTrue(is_string($this->prefix), "Prefix: " . var_export($this->prefix, TRUE));
        assertTrue(is_string($this->suffix), "Suffix: " . var_export($this->suffix, TRUE));
        // TODO? assertTrue(!alldigits($this->prefix));
        // TODO: assert no slashes in prefix
    }
    public function id() 
    {
        return $this->id;
    }
                    // public function is() 
                    // {
                        // $row = static::pdo()->row("
                            // SELECT *
                            // FROM ".static::tablename()." 
                            // WHERE  qiki_prefix = ? AND qiki_suffix = ?
                        // ", array($this->prefix,      $this->suffix    ));
                        // if ($row === array()) {
                            // $this->id = FALSE;
                            // return FALSE;
                        // }
                        // $this->id = $row['qiki_id'];
                        // return TRUE;
                    // }
   
    
                    public function __YE__OLDE__construct($idorfixes)         // three ways to identify a question, check ::is() if it is in the database
                    {
                        if (alldigits($idorfixes)) {                // new QikiQuestion('8');  
                            $this->row = self::$pdo->row("
                                SELECT *
                                FROM ".self::$table." 
                                WHERE qiki_id = ?
                            ", array($idorfixes));
                            if ($this->row == array()) {
                                $this->row = array(
                                    'qiki_id' => self::NO_ID,
                                    'qiki_prefix' => '',
                                    'qiki_suffix' => '',
                                );
                            }
                        } elseif (is_array($idorfixes)) {           // new QikiQuestion(array('php', 'strlen'));  
                            $this->con_struct($idorfixes);
                        } elseif (is_string($idorfixes)) {          // new QikiQuestion("php/strlen");  
                            $this->con_struct(self::unfix($idorfixes));
                        } else {
                            assertFailure("QikiQuestion malformed construction, type " . gettype($idorfixes));
                        }
                    }
                    protected function con_struct($fixes) 
                    {
                        assertTrue(is_array($fixes) 
                                 && count($fixes) == 2 
                                 && isset($fixes[0]) 
                                 && isset($fixes[1]), "Bad QikiQuestion constructor(".var_export($fixes,TRUE).")");
                        $this->row = self::$pdo->row("
                            SELECT *
                            FROM ".self::$table." 
                            WHERE qiki_prefix = ? AND qiki_suffix = ?
                        ", array(        $fixes[0],          $fixes[1]));
                        if ($this->row === array()) {
                            $this->row = array(
                                'qiki_id' => self::NO_ID,
                                'qiki_prefix' => $fixes[0],
                                'qiki_suffix' => $fixes[1],
                            );
                        }
                    }
    // TODO? static protected function unfix($prefix_or_whole, $suffix_or_nothing = NULL)
    // TODO: omg lots of unit tests needed here
    static protected function unfix($question, $maysuffix = NULL) {    // TODO: move into constructor, the only place it's ever needed.  Converts prefix/suffix --> array(prefix, suffix)   OR   prefix, suffix --> array(prefix, suffix)
		if (!is_null($maysuffix)) {
			return array($question, $maysuffix);
		}
        assertTrue(is_string($question), "QikiQuestion::unfix(not string): " . var_export($question, TRUE));
		$pos = strpos($question, '/', 1);   // where's the slash?  (Don't want a starting slash.  TODO: don't want a bunch of starting slashes either.)
		if ($pos === FALSE) {
			return array($question, '');    // no slash?  it's all prefix, no suffix
		} else if ($pos > MAX_QIKI_PREFIX) {
			return array('', $question);    // prefix unreasonably long?  no prefix, it's all suffix, including the slash
		} else {
            $pstart = 0;
            if ($question[0] == '/') {      // prefix starts with a (heretofore skipped) slash?  Strip it.
                $pstart = 1;
            }
            $beforeslash = substr($question, $pstart, $pos);
            $afterslash  = substr($question,          $pos+1);
            if ($afterslash === FALSE) {
                $afterslash = '';   // stupid substr('abc', 3) is FALSE, should be '' -- see http://php.net/manual/en/function.substr.php#refsect1-function.substr-parameters
            }
			return array($beforeslash, $afterslash);   // prefix reasonable?  now's the only time we leave out something: that first separating slash isn't stored in prefix nor suffix
				
		}
                        // $a = explode('/', $question, 2);
                        // if (count($a) <= 1) {
                            // return array('', $question);
                        // } else {
                            // return array($a[0], $a[1]);
                        // }
	}
	// public function id()   // e.g. '8' (should not be converted to numeric)
	// {
		// return $this->row['qiki_id'];
	// }
	// public function is()   // TRUE means there's a record in qiki.QikiQuestion for it
	// {
		// return $this->id() !== self::NO_ID;
	// }
	public function numObjected($verb = NULL)   // number of sentences that use it in object -- TODO: move this counting to the constructor?  Delegate to Sentence?
    // TODO: breaks if __constructed(), works if selectInfoed() -- is that wrong?
	{
		// if (!$this->is()) {
			// return 0;
		// }
        if ($this->id() == FALSE) {
            return 0;
        }
        $WHEREclause = "WHERE q.qiki_id = ?";
        $vars = array(static::classname(), $this->id());
        if (!is_null($verb)) {
            assertTrue($verb instanceof Verb);
            $WHEREclause .= " AND so.verb_id = ?";
            $vars[] = $verb->id();
        }
		return static::pdo()->cell("
			SELECT COUNT(so.sentence_id) 
			FROM ".static::tablename()." AS q
			LEFT JOIN ".Sentence::tablename()." AS so
				ON so.object_class = ?
				AND so.object_id = q.qiki_id
			$WHEREclause
			GROUP BY q.qiki_id
		", $vars);
	}
	public function numSubjected()   // number of sentences that use it in subject
	{
        if ($this->id() == FALSE) {
            return 0;
        }
		return static::pdo()->cell("
			SELECT COUNT(ss.sentence_id) 
			FROM ".static::tablename()." AS q
			LEFT JOIN ".Sentence::tablename()." AS ss
				ON ss.subject_class = ?
				AND ss.subject_id = q.qiki_id
			WHERE q.qiki_id = ?
			GROUP BY q.qiki_id
		", array(QikiQuestion::classname(), $this->id()));
	}
    public function numSentenced()   // number of sentences that use it in subject or object
    {
        return $this->numSubjected() + $this->numObjected();
    }
    public function isAnswered()   // TRUE means a sentence uses it in an object with the verb = answer
    {
        return $this->numObjected(Verb::selectInfo('answer'));
    }
    public function isSentenced()   // TRUE means a sentence uses it in object or subject
    {
        return $this->numSentenced() > 0;
    }
                    // public function question()   // TODO: candidate numero uno for unit testing
                    // {
                        // if ($this->row['qiki_suffix'] == '') {
                            // return $this->row['qiki_prefix'];
                        // } else {
                            // return $this->row['qiki_prefix'] . '/' . $this->row['qiki_suffix'];
                        // }
                    // }
	public function htmlQuestion() 
	{
		return htmlspecialchars($this->question());
	}
	public function prefix() 
	{
		return $this->row['qiki_prefix'];
	}
	public function suffix() 
	{
		return $this->row['qiki_suffix'];
	}
	// public function noun() 
	// {
		// return new Noun(static::classname(), $this->id());
	// }
	public function url() 
	{
		$path = $this->question();
		$a = explode('/', $path);
		$a = array_map('rawurlencode', $a);
		$path = join('/', $a);
		return "http://qiki.info/" . $path;
	}
	public function htmlLink($opts = array()) 
	{
		$opts += array(
			'popup' => FALSE,
			/* 'text' => 'some text & stuff' */  // to output instead of the qiki question, not &-escaped, will be &-escaped
			/* 'html' => 'some <b>html</b>' */   // to output instead of the qiki question, already &-escaped, tags supported, chars supported
			'title' => '',   // not &-escaped
			'class' => '',
		);
		if (isset($opts['text']) && isset($opts['html'])) {
			assertFailure("QikiQuestion::htmlLink(text => '$opts[text]', html => '$opts[html]') -- please don't set both.");
		} elseif (isset($opts['text'])) {
			$visiblePart = htmlspecialchars($opts['text']);
		} elseif (isset($opts['html'])) {
			$visiblePart = $opts['html'];
		} else {
			$visiblePart = $this->question();
		}
		$target = $opts['popup'] ? "target='_blank'" : '';
		$htmlTitle = htmlspecialchars($opts['title']);
		if ($this->isAnswered()) {   // green means has answers
			$isclass = 'ansqiki';
		} elseif ($this->isSentenced()) {   // blue means there are comments but no answers
			$isclass = 'comqiki';
		} elseif ($this->id() !== FALSE) {
			$isclass = 'zomqiki';   // pink means there's a QikiQuestion for it but no sentences.  Orphan question?  Purge?   // TODO: how does this ever get here?
		} else {
			$isclass = 'nonqiki';   // red means unheard of, no sentences, no row in QikiQuestion table
		}
		return "<a href='{$this->url()}' class='qikilink $isclass $opts[class]' $target title='$htmlTitle'>$visiblePart</a>";
	}



	// static public function TimidFactory($question, $maysuffix = NULL) {   // returns a QikiQuestion object, ::is() tells you if it exists already
		// list($prefix, $suffix) = $fixes = self::unfix($question, $maysuffix);
		// $id = self::$pdo->cell("
			// SELECT qiki_id
			// FROM ".self::$table." 
			// WHERE qiki_prefix = ? AND qiki_suffix = ?
		// ", array(     $prefix,            $suffix));
		// if ($id === FALSE) {
			// return new self($fixes);
		// } else {
			// return new self($id);
		// }
	// }
    
    
    
    
    public function stow()
    {
        if ($this->id() === FALSE) {
            $that = static::selectInfo($this->prefix, $this->suffix);   // TODO: pick one of these triplet of brute force checks for pre-existence
            if ($that->id() === FALSE) {
                $stmt = static::pdo()->prepare("
                    INSERT INTO `".static::tablename()."` (  qiki_prefix,   qiki_suffix)
                    VALUES                                (            ?,             ?) ON DUPLICATE KEY UPDATE qiki_id = LAST_INSERT_ID(qiki_id)");   // thanks http://stackoverflow.com/a/779252/673991
                $stmt->execute(array                      ($this->prefix, $this->suffix));
                $this->id = static::pdo()->lastInsertId();
            } else {
                $this->id = $that->id;
            }
        }
    }
    static public function selectInfo($question, $mayfix = NULL)
    {
        list($prefix, $suffix) = static::unfix($question, $mayfix);
        $row = static::pdo()->row("
            SELECT *
            FROM ".static::tablename()." 
            WHERE  qiki_prefix = ? AND qiki_suffix = ?
        ", array(      $prefix,            $suffix    ));
        if (empty($row)) {
            return new static($prefix, $suffix);
        }
        return static::constructRow($row);
    }
    static public function selectId($id)
    {
        assertTrue(alldigits($id));
		$row = static::pdo()->row("
			SELECT *
			FROM ".static::tablename()." 
			WHERE qiki_id = ?
		", array($id));
        if (empty($row)) {
            return FALSE;
        }
        return static::constructRow($row);
    }
    static private function constructRow($row)
    {
        $that = new static($row['qiki_prefix'], $row['qiki_suffix']);
        $that->id = $row['qiki_id'];
        return $that;
    }
                    /**
                     * <code>
                     * $qq = QikiQuestion::factoryBold('php/strlen');
                     * $qq = QikiQuestion::factoryBold(array('php', 'strlen'));
                     * assert($qq->is());
                     * </code>
                     */
                    // static public function factoryBold($question) {   // constructor with teeth,  ::is() will be TRUE, by SELECTing if it can, INSERTing if it has to
                        // // // $qq = self::TimidFactory($question, $maysuffix);
                        // assert(!alldigits($question));
                        // $qq = new self($question);
                        // if ($qq->is()) {
                            // return $qq;
                        // }
                        // $stmt = self::$pdo->prepare("
                            // INSERT INTO ".self::$table." (  qiki_prefix, qiki_suffix)
                            // VALUES                       (            ?,           ?)");
                        // $stmt->execute(array             ($qq->prefix(), $qq->suffix()));
                        // $id = self::$pdo->lastInsertId();
                        // return new self($id);
                    // }
	
	
	
                    // static public function StartCompleteSet() {
                    // }
                    // static public function FinishCompleteSet($opts = array()) {
                        // $opts += array(
                            // 'deleteRemains' => FALSE,
                        // );
                        // if ($opts['deleteRemains']) {
                            // // TODO: delete obsolete variants
                        // }
                    // }
                    
                    
                    
	public function state($opts=array()) {   // TODO: two sentences, [UserQiki or BotUser]->(bot)-> [ [Author]->(answer)->[QikiQuestion] ] <-[QikiML]
		$opts += array(
			'verb' => 'answer',
			'variant' => new UnusedNoun,   // (new Author($id))->noun()
			'info' => '',
			'value' => 1.0,
			'changecount' => &$whether_sentence_changed_zero_or_one,
		);
        $info = array();
        $info['subject'] = $opts['variant'];
		$info['verb'] = Verb::selectInfo($opts['verb']);
        $info['object'] = $this;
        $sentence = new Sentence($info);
        $sentence->clause(new QikiML($opts['info']));
        $sentence->value($opts['value']);
        $opts = array('changecount' => &$opts['changecount']);
        $sentence->stow($opts);
        return $sentence->id();
        
                        // $sentence_id = $verb->state(array(
                            // 'subject' => $opts['variant'],
                            // 'object' => $this->noun(),
                            // 'value' => $opts['value'],
                            // 'op' => 'set',
                            // 'clause' => array(new Noun(NounClass::QikiML, $opts['info'])),
                            // 'changecount' => &$opts['changecount'],
                        // ));
                        // return $sentence_id;
	}
    
	public function answers() 
    {
        // Caution: if called after QikiQuestion::__construct() will return empty, need QikiQuestion::selectInfo() instead.
        $info = array();
        $info['subject'] = Author::classname();
        $info['verb'] = Verb::selectInfo('answer');
        $info['object'] = $this;
        $info['clause'] = QikiML::classname();
        $answers = Sentence::selectInfoArray($info);
        // foreach($answers as $answer) echo "<!-- " . $answer->clause('QikiML')->idVariant() . " -->";
        // echo "\n";
        // var_export(array_keys($answers));
        $rc = uasort(
            $answers, 
            function ($qmlL, $qmlR) {
                return strcmp(
                    $qmlL->clause('QikiML')->idVariant(), 
                    $qmlR->clause('QikiML')->idVariant()
                );
            }
        );
        assertTrue($rc);
        // foreach($answers as $answer) echo "<!-- " . $answer->clause('QikiML')->idVariant() . " -->";
        // echo "\n";
        // var_export(array_keys($answers));
        return $answers;
        
                        // $verbAnswer = new Verb('answer');
                        // $retval = static::pdo()->column("
                            // SELECT 
                                // s.sentence_id,   # IFNULL(a.info, CONCAT('UNDEFINED', a.author_id)),
                                // c.noun_info
                            // FROM ".Sentence::tablename()." AS s
                            // JOIN ".Clause::tablename()." AS c
                                // ON c.sentence_id = s.sentence_id
                                // AND c.noun_class = ?
                            // LEFT JOIN ".Author::tablename()." AS a
                                // ON s.subject_id = a.author_id
                                // AND s.subject_class = ?
                            // WHERE s.verb_id = ?
                                // AND s.object_class = ?
                                // AND s.object_id = ?
                            // ORDER BY a.info
                        // ", array(
                            // NounClass::QikiML, 
                            // NounClass::Author, 
                            // $verbAnswer->id(), 
                            // QikiQuestion::classname(), 
                            // $this->id(),
                        // ));
                        // return $retval;
	}
    static public function classname() 
    {
        return NounClass::QikiQuestion;
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





require_once('../toolqiki/php/phpQuery.php');   // thanks http://stackoverflow.com/a/469713/673991

class QikiML implements Noun 
{
    private $info;
    private $doc;
    public function __construct($info) 
    {
        $this->info = $info;
        $this->doc = NULL;
    }
    public function info() 
    {
        return $this->info;
    }
    private function doc()
    {
        if (is_null($this->doc)) {
            $this->doc = phpQuery::newDocumentHTML($this->info);
        }
        return $this->doc;
    }
    public function idVariant()
    {
        $doc = $this->doc();
        return $doc[':root']->attr('id');
    }
    public function assertValid($classname = NULL) 
    {
        Noun_assertValid($this, $classname);
    }
    static public function classname()
    {
        return NounClass::QikiML;
    }
}
