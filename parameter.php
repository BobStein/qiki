<?php

function parameter($pname) {
	if (isset($_REQUEST[$pname])) {
		$GLOBALS[$pname] = $_REQUEST[$pname];
	} else {
		die("Missing parameter '$pname'");
	}
}

?>