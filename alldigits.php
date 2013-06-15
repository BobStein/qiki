<?php
	// alldigits.php
		
	function alldigits($s) {
		if (gettype($s) != 'string') return FALSE;
		return 1 == preg_match('/^\\d+$/', $s);
	}
?>