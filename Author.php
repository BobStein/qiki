<?php

// Author class for qiki.info
// ------
// An author is an automated generator of answers
// TODO: rename Variant?

require_once('SteinPDO.php');
require_once('Verb.php');

class Author implements NounLean, DataTable   // was extends SteinTable {   // TODO: extends Noun?  Extends UserQiki???
{
    private $name;
    private $id;
    public function __construct($name) 
    {
        $this->name = $name;
        $this->id = FALSE;
    }
    public function info() 
    {
        return $this->name;
    }
    public function id() 
    {
        return $this->id;
    }
    public function assertValid($classname = NULL) 
    {
        NounLean_assertValid($this, $classname);
    }
    static public function selectId($id) 
    {
		$row = static::pdo()->row("
			SELECT *
			FROM ".static::tablename()." 
			WHERE author_id = ?
		", array($id));
        return static::constructRow($row);
    }
    static public function selectInfo($info) 
    {
		$row = static::pdo()->row("
			SELECT *
			FROM ".static::tablename()." 
			WHERE info = ?
		", array($info));
        return static::constructRow($row);
    }
    static private function constructRow($row)
    {
        if (empty($row)) {
            return FALSE;
        }
        $that = new static($row['info']);
        $that->id = $row['author_id'];
        return $that;
    }
    public function stow()
    {
		$stmt = static::pdo()->prepare("
			INSERT INTO ".static::tablename()." (       info)
			VALUES                              (          ?)");
		$stmt->execute(array                    ($this->info));
		$this->id = static::pdo()->lastInsertId();
    }
    static public function classname()
    {
        return NounClass::Author;
    }
    static public function tablename() 
    {
        return __CLASS__;
    }
    static public function pdo() 
    {
        return SteinTable::pdo();
    }



	static public $OBSOLETE_table = __CLASS__;  // accessible to Verb or Sentence or Scorer or Preference
	protected $row;
	
	public function __OBSOLETE_construct($id) {
		$this->row = static::pdo()->row("
			SELECT *
			FROM ".static::tablename()." 
			WHERE author_id = ?
		", array($id));
	}
	public function OBSOLETE_id() {
		return $this->row['author_id'];
	}
	public function OBSOLETE_info() {
		return $this->row['info'];
	}
	public function OBSOLETE_noun() {
		return new Noun(static::classname(), $this->id());
	}
	
	static public function OBSOLETE_TimidFactory($info) {   // returns an Author object if it exists already, FALSE otherwise
		$id = static::pdo()->cell("
			SELECT author_id
			FROM ".static::tablename()." 
			WHERE info = ?
		", array($info));
		if ($id === FALSE) {
			return FALSE;
		} else {
			return new self($id);
		}
	}
	static public function OBSOLETE_BoldFactory($info) {   // returns an Author object, by taking (SELECTing) if it can, making (INSERTing) if it has to
		$qq = static::TimidFactory($info);
		if ($qq !== FALSE) {
			return $qq;
		}
		$stmt = static::pdo()->prepare("
			INSERT INTO ".static::tablename()." ( info)
			VALUES                       (    ?)");
		$stmt->execute(array             ($info));
		$id = static::pdo()->lastInsertId();
		return new self($id);
	}
	static public function DeleteAllSentences() {
		$sth = static::pdo()->prepare("DELETE FROM a WHERE object_class = ?");
		$sth->execute(array(                           static::classname()));		
	}
	static public function allids() {
		return static::pdo()->column("
			SELECT 
				s.sentence_id,
				CONCAT(q.qiki_prefix, '/', q.qiki_suffix, '#', a.info) AS url
			FROM ".Sentence::tablename()." AS s
			JOIN ".QikiQuestion::tablename()." AS q
				ON s.object_class = ?
				AND s.object_id = q.qiki_id
			JOIN ".Author::tablename()." AS a
				ON s.subject_class = ?
				AND s.subject_id = a.author_id
		", array(QikiQuestion::classname(), static::classname()));
	}
	static public function tablevariants() {
		return static::pdo()->column("
			SELECT
				a.info,
				COUNT(s.sentence_id)
			FROM ".Author::tablename()." AS a
			LEFT JOIN ".Sentence::tablename()." AS s
				ON s.subject_class = ?
				AND s.subject_id = a.author_id
			GROUP BY a.info
			ORDER BY a.info
		", array(static::classname()));
	}
	static public function PurgeOrphanAnswers($orphan_sentence_ids) {
		$retval = 0;
		foreach ($orphan_sentence_ids as $orphan_sentence_id) {
			// TODO: log old values
			$sth = static::pdo()->prepare("DELETE FROM ".Sentence::tablename()." WHERE sentence_id = ? LIMIT 1");
			$sth->execute(array(                                       $orphan_sentence_id));
			$retval += $sth->rowCount();
			$sth2 = static::pdo()->prepare("DELETE FROM ".Clause::tablename()." WHERE sentence_id = ?");
			$sth2->execute(array(                                     $orphan_sentence_id));
		}
		return $retval;
	}
	static public function PurgeOrphanVariants($orphan_variant_infos) {
		$retval = 0;
		foreach ($orphan_variant_infos as $orphan_variant_info) {
			// TODO: log old values, the old records might have needed them!
			$sth = static::pdo()->prepare("DELETE FROM ".Author::tablename()." WHERE info = ? LIMIT 1");
			$sth->execute(array(                             $orphan_variant_info));
			$retval += $sth->rowCount();
		}
		return $retval;
	}
}
