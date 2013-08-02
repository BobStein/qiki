<?php

define ('MAX_QIKI_PREFIX', 32);
define ('MAX_QIKI_SUFFIX', 255);

require_once('SteinPDO.php');
require_once('Verb.php');
require_once('alldigits.php');

class QikiQuestion extends SteinTable {
	static public $table = __CLASS__;  // accessible to Verb or Sentence or Scorer or Preference
	protected $row;
	
	public function __construct($id) {
		$this->row = self::$pdo->row("
			SELECT *
			FROM ".self::$table." 
			WHERE qiki_id = ?
		", array($id));
	}
	public function id() {
		return $this->row['qiki_id'];
	}
	public function question() {   // TODO: candidate numero uno for unit testing
		if ($this->row['qiki_suffix'] == '') {
			return $this->row['qiki_prefix'];
		} else {
			return $this->row['qiki_prefix'] . '/' . $this->row['qiki_suffix'];
		}
	}
	public function noun() {
		return new Noun(NounClass::QikiQuestion, $this->id());
	}
	static protected function unfix($question) {    // array(prefix, suffix)
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
	public function state($opts=array()) {
	}
	static public function TimidFactory($question) {   // returns a QikiQuestion object if it exists already, FALSE otherwise
		list($prefix, $suffix) = self::unfix($question);
		$id = self::$pdo->cell("
			SELECT qiki_id
			FROM ".self::$table." 
			WHERE qiki_prefix = ? AND qiki_suffix = ?
		", array(     $prefix,            $suffix));
		if ($id === FALSE) {
			return FALSE;
		} else {
			return new self($id);
		}
	}
	static public function BoldFactory($question) {   // returns a QikiQuestion object, by taking (SELECTing) if it can, making (INSERTing) if it has to
		$qq = self::TimidFactory($question);
		if ($qq !== FALSE) {
			return $qq;
		}
		list($prefix, $suffix) = self::unfix($question);
		$stmt = self::$pdo->prepare("
			INSERT INTO ".self::$table." (qiki_prefix, qiki_suffix)
			VALUES                       (          ?,           ?)");
		$stmt->execute(array             (    $prefix,     $suffix));
		$id = self::$pdo->lastInsertId();
		return new self($id);
	}
}