<?php

require_once('Verb.php');

class Preference {
	protected $id;
	protected $name;
	static $tableGrid = array(
		1 => 'anonymous',
		2 => 'spam',
		3 => 'qoolopen',
	);
	static $defaultFromUser = array(
		'anonymous' => FALSE,
		'spam' => FALSE,
	);
	public function __construct($idorname) {
		if (isset(self::$tableGrid[$idorname])) {
			$this->id = $idorname;
			$this->name = self::$tableGrid[$idorname];
		} else if (($key = array_search($idorname, self::$tableGrid)) !== FALSE) {
			$this->id = $key;
			$this->name = self::$tableGrid[$key];
		} else {
			$this->id = NULL;
			$this->name = NULL;
		}
	}
	public function id() {
		return $this->id;
	}
	public function name() {
		return $this->name;
	}
	static public function fromUser($client_id) {   // array(preference_name => trueORfalse, ...)
		if ($client_id == User::NOUSERID) {
			return self::$defaultFromUser;
		}
		$wheretests = array();
		$queryparameters = array();
		
		$wheretests[] = "s.subject_class = ?";
		$queryparameters[] = NounClass::User;
		
		$wheretests[] = "s.subject_id = ?";
		$queryparameters[] = $client_id;
		
		$verbPrefer = new Verb('prefer');
		$wheretests[] = "s.verb_id = ?";
		$queryparameters[] = $verbPrefer->id();
		
		$wheretests[] = "s.object_class = ?";
		$queryparameters[] = NounClass::Preference;

		$WHEREclause = empty($wheretests) ? '' : "WHERE " . join(' AND ', $wheretests);
		$id2value = Sentence::pdo()->column("
			SELECT 
				s.object_id,
				s.value
			FROM ".Sentence::$table." AS s
			$WHEREclause
		", $queryparameters);
		$name2bool = array();
		foreach ($id2value as $id => $value) {
			$pref = new self($id);
			if ($value) {
				$name2bool[$pref->name()] = TRUE;
			} else {
				$name2bool[$pref->name()] = FALSE;
			}
		}
		return $name2bool;
	}
}