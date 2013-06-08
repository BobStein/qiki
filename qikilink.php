<?php

function qikilink($s) {
	return preg_replace('#\\bqiki:/?(\\S+)#', '<a href="http://qiki.info/$1" class="qikilink">$1</a>', $s);
}

?>