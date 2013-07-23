<?php

// qikilink.php
// ------------
// qikipath()             path <-- qiki
// qikiurl()               url <-- qiki
// qikilinkWhole()   hyperlink <-- qiki    // unify, objectify or something.  yeah, objectify!
// qikilinkifyText()  convert all qiki:blah/blah codes in a swath of text to hyperlinks

function test_qikilink() {
	assertEquals("'http://qiki.info/php/strlen'", "qikiurl('php/strlen')");
	assertEquals("'http://qiki.info/php/%3C'",    "qikiurl('php/<')");
	
	assertEquals("'<a href=\\'http://qiki.info/php/strlen\\'>php/strlen</a>'", "qikilinkWhole('qiki:php/strlen')");
}

function qikilinkWhole($q) {
	$q = preg_replace('#^/#', '', $q);
	return "<a href='" . qikiurl($q) . "' class='qikilink'>" . htmlspecialchars($q) . "</a>";
}

function qikilink($s)  {   // obsolete, call qikilinkWhole() or qikilinkifyText()
	return qikilinkifyText($s);
}


// TODO: support blanks in the qiki?  If so, allow quotes or coded-blanks or something
// TODO: support "blah blah qiki something/with/at/least/one/slash
// TODO: 

function qikilinkifyText($s) {   // convert to hyperlinks all phrases e.g. "qiki:php/strlen"
	// $s = preg_replace_callback('#\\bqiki:"/?(.+?)"#', function($m) {
		// return "<a href='" . qikiurl($m[1]) . "' class='qikilink'>" . htmlspecialchars($m[1]) . "</a>";
	// }, $s);
	$s = preg_replace_callback('#\\bqiki:/?(\\S+)#', function($m) {
		//$q = str_replace('_', ' ', $m[1]);   // TODO: leave alone underscores that touch slashes or edges, e.g. "php/_"
		return qikilinkWhole($m[1]);
	}, $s);
	return $s;
}

function qikiurl($q) {   // convert to a URL all phrases, e.g. "http://qiki.info/php/strlen" === qikiurl("php/strlen")
	switch (gettype($q)) {
	case 'string':
		$a = explode('/', $q);
		break;
	case 'array':
		$a = $q;
		break;
	}
	$a = array_map('rawurlencode', $a);
	$path = join('/', $a);
	return "http://qiki.info/" . $path;
}

function qikipath(/* ... */) {   // internal server path, e.g. "/home/visibone/public_html/qiki/php/strlen" === qikipath("php/strlen")
	return "/home/visibone/public_html/qiki/" . join('/', func_get_args());
}
