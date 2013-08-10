<?php

// Author class for qiki.info
// ------
// An author is an automated generator of answers
// TODO: rename Variant?

require_once('SteinPDO.php');
require_once('Verb.php');

class Author extends SteinTable {   // TODO: extends Noun?  Extends UserQiki???
	static public $table = __CLASS__;  // accessible to Verb or Sentence or Scorer or Preference
	protected $row;
	
	public function __construct($id) {
		$this->row = self::$pdo->row("
			SELECT *
			FROM ".self::$table." 
			WHERE author_id = ?
		", array($id));
	}
	public function id() {
		return $this->row['author_id'];
	}
	public function info() {
		return $this->row['info'];
	}
	public function noun() {
		return new Noun(NounClass::Author, $this->id());
	}
	
	static public function TimidFactory($info) {   // returns an Author object if it exists already, FALSE otherwise
		$id = self::$pdo->cell("
			SELECT author_id
			FROM ".self::$table." 
			WHERE info = ?
		", array($info));
		if ($id === FALSE) {
			return FALSE;
		} else {
			return new self($id);
		}
	}
	static public function BoldFactory($info) {   // returns an Author object, by taking (SELECTing) if it can, making (INSERTing) if it has to
		$qq = self::TimidFactory($info);
		if ($qq !== FALSE) {
			return $qq;
		}
		$stmt = self::$pdo->prepare("
			INSERT INTO ".self::$table." ( info)
			VALUES                       (    ?)");
		$stmt->execute(array             ($info));
		$id = self::$pdo->lastInsertId();
		return new self($id);
	}
	static public function DeleteAllSentences() {
		$sth = self::$pdo->prepare("DELETE FROM a WHERE object_class = ?");
		$sth->execute(array(                           NounClass::Author));		
	}
	static public function allids() {
		return self::$pdo->column("
			SELECT 
				s.sentence_id,
				CONCAT(q.qiki_prefix, '/', q.qiki_suffix, '#', a.info) AS url
			FROM ".Sentence::$table." AS s
			JOIN ".QikiQuestion::$table." AS q
				ON s.object_class = ?
				AND s.object_id = q.qiki_id
			JOIN ".Author::$table." AS a
				ON s.subject_class = ?
				AND s.subject_id = a.author_id
		", array(NounClass::QikiQuestion, NounClass::Author));
	}
	static public function tablevariants() {
		return self::$pdo->column("
			SELECT
				a.info,
				COUNT(s.sentence_id)
			FROM ".Author::$table." AS a
			LEFT JOIN ".Sentence::$table." AS s
				ON s.subject_class = ?
				AND s.subject_id = a.author_id
			GROUP BY a.info
			ORDER BY a.info
		", array(NounClass::Author));
	}
	static public function PurgeOrphanAnswers($orphan_sentence_ids) {
		$retval = 0;
		foreach ($orphan_sentence_ids as $orphan_sentence_id) {
			// TODO: log old values
			$sth = self::$pdo->prepare("DELETE FROM ".Sentence::$table." WHERE sentence_id = ? LIMIT 1");
			$sth->execute(array(                                       $orphan_sentence_id));
			$retval += $sth->rowCount();
			$sth2 = self::$pdo->prepare("DELETE FROM ".Clause::$table." WHERE sentence_id = ?");
			$sth2->execute(array(                                     $orphan_sentence_id));
		}
		return $retval;
	}
	static public function PurgeOrphanVariants($orphan_variant_infos) {
		$retval = 0;
		foreach ($orphan_variant_infos as $orphan_variant_info) {
			// TODO: log old values, the old records might have needed them!
			$sth = self::$pdo->prepare("DELETE FROM ".Author::$table." WHERE info = ? LIMIT 1");
			$sth->execute(array(                             $orphan_variant_info));
			$retval += $sth->rowCount();
		}
		return $retval;
	}
}
