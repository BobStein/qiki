<?php

require_once('Verb.php');
require_once('UserQiki.php');

// TODO: a simple scorer that just totalled up all the values in Sentence that matched the criteria
// TODO: another that would just count the number of such sentences
// TODO: another that did so normalized by some broader total
// e.g. so a user who rated lots of likes would find each one less potent


class Scorer {
	protected $name;
	public function __construct($name) {
		$this->name = $name;
	}
	public function score($opts=array()) {
		$opts += array(
			/* NounClass::Verb     => $verb_id,    */   //
			/* NounClass::User     => $user_id,    */   // pick one of these to specify the object, or none of these to sum over all objects
			/* NounClass::Sentence => $comment_id, */   //
			'client_id' => NULL,   // e.g. UserQiki::client()->id()
		);
		switch ($this->name) {
		
		case 'spammy':	
			$verb = new Verb('spam');
			
			// TODO: MergeMorphMeld with Verb::associations() or something
			$wheretests = array();
			$queryparameters = array();
			foreach (allNounClasses() as $nounclass) {
				if (isset($opts[$nounclass])) {
					$wheretests[] = "s.object_class = ? AND s.object_id = ?";
					$queryparameters[] = $nounclass;
					$queryparameters[] = $opts[$nounclass];
					break;
				}
			}
			$wheretests[] = "s.verb_id = ?";
			$queryparameters[] = $verb->id();
			$wheretests[] = "s.subject_class = ?";
			$queryparameters[] = NounClass::User;   // Ignores ratings from anonymous users -- but still shows the icon!  
			$WHEREclause = empty($wheretests) ? '' : "WHERE " . join(' AND ', $wheretests);
			$pdo = Verb::pdo();
			$uservotes = $pdo->column("
				SELECT
					s.subject_id,
					s.value
				FROM ".Sentence::$table." AS s
				$WHEREclause
			", $queryparameters);
			
			$isClientSpecified = !is_null($opts['client_id']);
			$isClientLoggedIn = $isClientSpecified && $opts['client_id'] != User::NOUSERID;
			$didClientScore = $isClientLoggedIn && isset($uservotes[$opts['client_id']]);
			$scoreOfClient = $didClientScore ? $uservotes[$opts['client_id']] : 0.0;
			
			// So, $scoreOfClient == 0.0 could mean: 
			//		client says I dunno, or 
			//		client never scored, or 
			//		anonymous client, or 
			//		calling function never specified a client
			
			if ($scoreOfClient == 0.0) {
				$countPositive = 0;
				foreach ($uservotes as $uservote) {
					if ($uservote > 0.0) {
						$countPositive++;
					}
				}
				$spamscore = $countPositive / 2.0;  // if client has no opinion, it only matters how many others had a positive score, and half as much
			} else {
				$spamscore = $scoreOfClient;  // to each person, their own (nonzero) rating is godlike
			}
			return $spamscore;
		}
		return 0.0;
	}
}
