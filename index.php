<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);

require_once('qiki.php'); 
include("/home/visibone/security/qikisql.php");
require_once('SteinPDO.php'); 
require_once('Comment.php'); 

$pdo = new SteinPDO($USER, $PASS, array('host' => $HOST, 'database' => $DATABASE));
Comment::$pdo = $pdo;

?>

<title>qiki</title>
<link type="text/css" href="/qiki.css" rel="stylesheet" />

A qiki:

<ol>
	<li>gives answers in seconds (or great clues)
	<li>takes years to make (or minutes)
	<li>is available to everyone (or just you)
	<li>can be built (or built upon) by anyone with insight
	<li>looks like one long horizontal line of stuff
	<li>feels like total recall
	<li>uses no JavaScript (until you click)
	<li>uses only two HTML tags: &lt;span style class id title&gt; and &lt;a href name&gt;
	<li>uses any special characters
	<li>uses any CSS (including background-image - wink)
	<li>makes ample use of
		<ul>
			<li>hover hints (aka tool tips)
			<li>autoexpanders (avoiding scroll bars)
		</ul>
</ol>

stein at visibone dot com

<?php

$comments = Comment::byRecency(kontext(), 100);
echo "<h2>Recent Comments</h2>\n";
echo "<table class='commentlist'>\n";
foreach ($comments as $comment) {
	echo "<tr>\n";
	echo "<td>\n";
	echo $comment-> htmlKontext();
	echo "<td>\n";
	echo $comment->htmlQomment();
	echo "&nbsp; ";
	echo "<span class='sayswho'>";
		echo "(by ";
		echo $comment->whoshort();
		echo ", ";
		echo $comment->ago();
		echo ")";
	echo "</span>";
	echo "<br />\n";
}
echo "</table>\n";

?>