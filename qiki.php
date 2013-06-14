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

function footerlogo() {
	return 
		"<p class='footer-logo'>"
			."&copy;"
			.date('Y')
			.' '
			.qikilogo()
			.".info"
		."</p>\n";
}

function qikilogo() {
	return 
		"<span class='qi-logo'>"
			."Qi"
		."</span>"
		."<span class='ki-logo'>"
			."Ki"
		."</span>";
}


?>