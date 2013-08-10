<?php
	// alldigits.php
		
	function alldigits($s) {   // is this a string of 1 or more digits and no other characters
		if (gettype($s) != 'string') return FALSE;
		return 1 == preg_match('/^\\d+$/', $s);
	}
?>