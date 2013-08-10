<?php

define ('MAX_QIKI_PREFIX', 32);
define ('MAX_QIKI_SUFFIX', 255);

require_once('SteinPDO.php');
require_once('Verb.php');
require_once('Author.php');
// require_once('qikilink.php');

class QikiQuestion extends SteinTable 
{
	static public $table = __CLASS__;  // accessible to Verb or Sentence or Scorer or Preference
	const NO_ID = -1;
	protected $row;
	
	public function __construct($idorfixes)         // three ways to identify a question, check ::is() if it is in the database
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
			debug_print_backtrace();
			die("QikiQuestion malformed construction, type " . gettype($idorfixes));
		}
	}
	protected function con_struct($fixes) 
	{
		assssert(is_array($fixes) 
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
	static protected function unfix($question, $maysuffix = NULL) {    // prefix/suffix --> array(prefix, suffix)   OR   prefix, suffix --> array(prefix, suffix)
		if (!is_null($maysuffix)) {
			return array($question, $maysuffix);
		}
		$pos = strpos($question, '/');
		if ($pos === FALSE) {
			return array($question, '');   // no slash?  it's all prefix, no suffix
		} else if ($pos == 0) {
			return array('', $question);   // starts with slash?  no prefix, it's all suffix, including the slash
		} else if ($pos > MAX_QIKI_PREFIX) {
			return array('', $question);   // prefix unreasonably long?  no prefix, it's all suffix, including the slash
		} else {
			return array(                  // prefix reasonable?  now's the only time we leave out something: that first separating slash isn't stored in prefix nor suffix
				substr($question, 0, $pos), 
				substr($question,    $pos+1)
			);
		}
		$a = explode('/', $question, 2);
		if (count($a) <= 1) {
			return array('', $question);
		} else {
			return array($a[0], $a[1]);
		}
	}
	public function id()   // e.g. '8' (should not be converted to numeric)
	{
		return $this->row['qiki_id'];
	}
	public function is()   // TRUE means there's a record in qiki.QikiQuestion for it
	{
		return $this->id() !== self::NO_ID;
	}
	public function numObjected()   // number of sentences that use it in object -- TODO: move this counting to the constructor?
	{
		if (!$this->is()) {
			return 0;
		}
		return self::$pdo->cell("
			SELECT COUNT(so.sentence_id) 
			FROM ".self::$table." AS q
			LEFT JOIN ".Sentence::$table." AS so
				ON so.object_class = ?
				AND so.object_id = q.qiki_id
			WHERE q.qiki_id = ?
			GROUP BY q.qiki_id
		", array(NounClass::QikiQuestion, $this->id()));
	}
	public function numSubjected()   // number of sentences that use it in subject
	{
		if (!$this->is()) {
			return 0;
		}
		return self::$pdo->cell("
			SELECT COUNT(ss.sentence_id) 
			FROM ".self::$table." AS q
			LEFT JOIN ".Sentence::$table." AS ss
				ON ss.subject_class = ?
				AND ss.subject_id = q.qiki_id
			WHERE q.qiki_id = ?
			GROUP BY q.qiki_id
		", array(NounClass::QikiQuestion, $this->id()));
	}
	public function numSentenced()   // number of sentences that use it in subject or object
	{
		return $this->numSubjected() + $this->numObjected();
	}
	public function isSentenced()   // TRUE means a sentence uses it in object or subject
	{
		return $this->numSentenced() > 0;
	}
	public function question()   // TODO: candidate numero uno for unit testing
	{
		if ($this->row['qiki_suffix'] == '') {
			return $this->row['qiki_prefix'];
		} else {
			return $this->row['qiki_prefix'] . '/' . $this->row['qiki_suffix'];
		}
	}
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
	public function noun() 
	{
		return new Noun(NounClass::QikiQuestion, $this->id());
	}
	public function url() 
	{
		$path = $this->question();
		$a = explode('/', $path);
		$a = array_map('rawurlencode', $a);
		$path = join('/', $a);
		return "http://qiki.info/" . $path;
	}
	public function link($opts = array()) 
	{
		$opts += array(
			'popup' => FALSE,
			/* text => text */   // not &-escaped, will be &-escaped
			/* html => html */   // already &-escaped, tags supported
			'title' => '',   // not &-escaped
			'class' => '',
		);
		if (isset($opts['text']) && isset($opts['html'])) {
			die("QikiQuestion::link(text => '$opts[text]', html=> ''$opts[html]') -- please don't set both.");
		} elseif (isset($opts['text'])) {
			$visiblePart = htmlspecialchars($opts['text']);
		} elseif (isset($opts['html'])) {
			$visiblePart = $opts['html'];
		} else {
			$visiblePart = $this->question();
		}
		$target = $opts['popup'] ? "target='_blank'" : '';
		$htmlTitle = htmlspecialchars($opts['title']);
		if ($this->isSentenced()) {
			$isclass = 'yesqiki';
		} elseif ($this->is()) {
			$isclass = 'maybeqiki';
		} else {
			$isclass = 'noqiki';
		}
		return "<a href='{$this->url()}' class='qikilink $isclass $opts[class]' $target title='$htmlTitle'>$visiblePart</a>";
	}
	static public function translateMarkdown($text) 
	{
		$text = preg_replace_callback('#\\bqiki:(\\S+)#', function($m) {
			$qq = new QikiQuestion($m[1]);
			return $qq->link();
		}, $text);
		return $text;
	}
		// function qikilinkifyText($s) {   // convert to hyperlinks all phrases e.g. "qiki:php/strlen"
			// $s = preg_replace_callback('#\\bqiki:"/?(.+?)"#', function($m) {
				// return "<a href='" . qikiurl($m[1]) . "' class='qikilink'>" . htmlspecialchars($m[1]) . "</a>";
			// }, $s);
		// }
		
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
	/**
	 * <code>
	 * $qq = QikiQuestion::factoryBold('php/strlen');
	 * $qq = QikiQuestion::factoryBold(array('php', 'strlen'));
	 * assert($qq->is());
	 * </code>
	 */
	static public function factoryBold($question) {   // constructor with teeth,  ::is() will be TRUE, by SELECTing if it can, INSERTing if it has to
		// $qq = self::TimidFactory($question, $maysuffix);
		assert(!alldigits($question));
		$qq = new self($question);
		if ($qq->is()) {
			return $qq;
		}
		$stmt = self::$pdo->prepare("
			INSERT INTO ".self::$table." (  qiki_prefix, qiki_suffix)
			VALUES                       (            ?,           ?)");
		$stmt->execute(array             ($qq->prefix(), $qq->suffix()));
		$id = self::$pdo->lastInsertId();
		return new self($id);
	}
	
	
	
	static public function StartCompleteSet() {
	}
	static public function FinishCompleteSet($opts = array()) {
		$opts += array(
			'deleteRemains' => FALSE,
		);
		if ($opts['deleteRemains']) {
			// TODO: delete obsolete variants
		}
	}
	public function state($opts=array()) {
		$opts += array(
			'verb' => 'answer',
			'variant' => new Noun(NounClass::Unused, '0'),   // (new Author($id))->noun()
			'info' => '',
			'value' => 1.0,
			'changecount' => &$whether_sentence_changed_zero_or_one,
		);
		$verb = new Verb($opts['verb']);
		$sentence_id = $verb->state(array(
			'subject' => $opts['variant'],
			'object' => $this->noun(),
			'value' => $opts['value'],
			'op' => 'set',
			'clause' => array(new Noun(NounClass::QikiML, $opts['info'])),
			'changecount' => &$opts['changecount'],
		));
		return $sentence_id;
	}
	public function answers() {
		$verbAnswer = new Verb('answer');
		$retval = self::$pdo->column("
			SELECT 
				s.sentence_id,   # IFNULL(a.info, CONCAT('UNDEFINED', a.author_id)),
				c.noun_info
			FROM ".Sentence::$table." AS s
			JOIN ".Clause::$table." AS c
				ON c.sentence_id = s.sentence_id
				AND c.noun_class = ?
			LEFT JOIN ".Author::$table." AS a
				ON s.subject_id = a.author_id
				AND s.subject_class = ?
			WHERE s.verb_id = ?
				AND s.object_class = ?
				AND s.object_id = ?
			ORDER BY a.info
		", array(
			NounClass::QikiML, 
			NounClass::Author, 
			$verbAnswer->id(), 
			NounClass::QikiQuestion, 
			$this->id(),
		));
		return $retval;
	}
}