 <?php
error_reporting(E_ALL);
ini_set("display_errors", 1);

require_once('qiki.php'); 
require_once('Comment.php'); 

htmlhead('qiki');

?>

A qiki:

<ol>
	<li>gives answers in seconds (or great clues)
	<li>takes years to make (or minutes)
	<li>is available to everyone (or just you)
	<li>can be built (or built upon) by anyone with insight
	<li>looks like one long horizontal line of stuff
	<li>feels like total recall
	<li>uses no JavaScript (until you click)
	<li>uses HTML limited to: &lt;span style class id title lang&gt; and &lt;a href name&gt;
	<li>uses any special characters, so &amp;hearts; looks like &hearts;
	<li>uses any CSS (including background-image - wink)
	<li>uses tool tips (stuff shows up when you hover with the mouse)
	<li>uses auto expanders &mdash; a scrollbar is a sign of incomplete design
	<!-- li>uses MathJax, so \<span></span>( E=mc^2 \<span></span>) looks like \( E=mc^2 \) -->
</ol>

stein at visibone dot com

<?php

if (UserQiki::$client->may(UserQikiAccess::see, NounClass::Comment, 'user')) {
	if (UserQiki::$client->may(UserQikiAccess::see, NounClass::Comment, 'anon')) {
		$minlevel = 'anon';
	} else {
		$minlevel = 'user';
	}
	$limitrows = 10;
	$comments = Comment::byRecency(array('limit' => $limitrows, 'totalrows' => &$totalrows, 'minlevel' => $minlevel));
	echo "<h2>Recent Comments</h2>\n";
	echo "<table class='commentlist'>\n";
	foreach ($comments as $comment) {
		echo "<tr>\n";
		echo "<td>\n";
		echo $comment->htmlKontext();
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
	$numRowsNotShown = max(0, $totalrows - $limitrows);
	if ($numRowsNotShown > 0) {
		echo "<tr><td colspan='2'>... $numRowsNotShown older comments ...\n";
	}
	echo "</table>\n";
}
htmlfoot();

?>