<?php

// qiki.php
// --------
//
// Generic includable for qiki site.


function qontext() {  // Human readable context, without the slash
	$retval = kontext();
	$retval = preg_replace('#^/#', '', $retval);
	return $retval;
}

function kontext() {  // Machine usable context, with the slash
	$retval = $_SERVER['REQUEST_URI'];
	$retval = urldecode($retval);
	return $retval;
}


?>