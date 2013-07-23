<?php

require_once('Verb.php');

class Scorer {
	protected $name;
	public function __construct($name) {
		$this->name = $name;
	}
	public function score($opts=array()) {
		$opts += array(
			'subject_class' => '',
			'subject_id' => '',
			'verb_name' => '',
		);
		return 0;
	}
}
