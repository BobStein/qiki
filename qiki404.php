<?php

// qiki404.php
// -----------
// What to run if file not found

error_reporting(E_ALL);
ini_set("display_errors", 1);

$SHOW_VERBBAR = FALSE;   // faded gear-icon next to every comment, when clicked presents all tools.

require_once('UserQiki.php');
User::$ACTIONPREFIX = $_SERVER['PHP_SELF'];

require_once('qiki.php'); 
require_once('Comment.php');
require_once('Verb.php');
require_once('../toolqiki/php/parameter.php');



 
if (isset($_REQUEST['action'])) {
	switch ($_REQUEST['action']) {
	
	case 'verb_associate':
		$verbname = parameter('verbname');
		$obj = parameter('obj');
		$objid = parameter('objid');
		$delta = parameter('delta');
		if (UserQiki::$client->alreadyLoggedIn()) {
			$qontributor = UserQiki::$client->id();
		} else {
			$qontributor = $_SERVER['REMOTE_ADDR'];   // TODO: separate sentence, and always put it in there?  Or some other way of recording IP?
		}
		$verb = new Verb($verbname);
		$association = $verb->associate($obj, $objid, $qontributor, $delta);
		echo "success";
		exit;
	case 'newcomment':
		$qomment = parameter('qomment');
		$kontext = parameter('kontext');
		if (!UserQiki::$client->may(UserQikiAccess::create, NounClass::Comment)) {
			die("You don't have access to write comments for this qiki.");
		}
		if (UserQiki::$client->alreadyLoggedIn()) {
			$qontributor = UserQiki::$client->id();
		} else {
			$qontributor = $_SERVER['REMOTE_ADDR'];
		}
		$comment = Comment::insert($qomment, $qontributor, $kontext);
		echo "success";
		exit;
	case 'newqontext':
		$shouldqontext = parameter('shouldqontext');
		header('Location: ' . rawurlencode($shouldqontext));  // http://stackoverflow.com/questions/996139/php-urlencode-vs-rawurlencode
		header("Cache-Control: no-cache, must-revalidate");
		exit;
	default:
		die("Unknown action '$_REQUEST[action]'.");
	}
}

if (UserQiki::$client->may(UserQikiAccess::see, NounClass::Comment, 'anon')) {
	$minlevel = 'anon';
	$whichcomments = "Comments";
} else {
	$minlevel = 'user';
	$whichcomments = "Comments by users";
}
$limitrows = 50;
$comments = Comment::byKontext(kontext(), array('limit' => $limitrows, 'totalrows' => &$totalrows, 'minlevel' => $minlevel));

htmlhead(htmlspecialchars(qontext()) . ' - qiki', array('head' => array(headersJQueryUI())));

$linkToQiki = qikilinkWhole(qontext());
if (UserQiki::$client->may(UserQikiAccess::create, NounClass::Comment)) {
	?>
		<form class='contextform' action='<?php echo $FORMSUBMITURL; ?>' method='post'>
			<input type='hidden' name='action' value='newqontext' />
			<span id='whatshouldbe' class='kare1'>
				You think something should be here?&nbsp; 
				<?php
					if (count($comments) > 0) {
						echo "Cool, you're not alone!";
					} else {
						echo "Cool, you're the first!";
					}
				?>
			</span><span id='whatwouldlook' class='kare2'>
				So what would a
				<?php
					echo $linkToQiki;
					// TODO:  appear simply boldface like a wiki article self-reference?   As specified at http://en.wikipedia.org/wiki/Help:Link#Wikilinks
					// TODO:  clicking on it allows you to change it?
					// TODO:  ... otherwise dismantle the form and action=newqontext etc.
					echo htmlspecialchars(qontext()); 
				?>
				qiki
				look like?
			</span>
		</form>
		<form class='commentform' action='<?php echo $FORMSUBMITURL; ?>' method='post'>
			<input type='hidden' name='action' value='newcomment' />
			<input type='hidden' name='kontext' value='<?php echo htmlspecialchars(kontext()); ?>' />

			<textarea id='qomment' name='qomment'></textarea>
			<br />
			
			<input type=submit value='make a qiki wish'>
		</form>

	<?php echo "\n";
} else {
	echo "<p>(The qiki $linkToQiki is not currently taking comments.)</p>\n";
}

if (UserQiki::$client->isSuper()
 || UserQiki::$client->isGuest()) {
	echo Verb::qoolbar(UserQiki::$client->id());
}

if (UserQiki::$client->isSuper()) {
	$verbsMe = Verb::associations(array('subject_class' => NounClass::User, 'subject_id' => UserQiki::$client->id()));
	$verbsEveryone = Verb::associations();

	$verbsUsedByOthers = array_diff_assoc($verbsEveryone, $verbsMe);   // aka verbs used by somebody other than me, that I'm not the only one who uses them

	$verbsUnusedByMe = array_diff_key($verbsEveryone, $verbsMe);

	echo "My verbs: ";
	echo Verb::showverbs($verbsMe, array('postsup' => $verbsMe, 'postsub' => $verbsUsedByOthers));
	echo "<br>\n";

	echo "All verbs: ";
	echo Verb::showverbs($verbsEveryone, array('postsup' => $verbsMe, 'postsub' => $verbsUsedByOthers));
	echo "<br>\n";

	echo "Verbs I haven't used yet: ";
	echo Verb::showverbs($verbsUnusedByMe, array('postsub' => $verbsEveryone));
	echo "<br>\n";
}

if (count($comments) > 0) {
	echo "<span class='Comment-Heading'>$whichcomments:</span>\n";   // TODO: tell how many anonymous comments were removed from this view
}
foreach ($comments as $comment_id => $comment) {
	echo "<span "
		."class='verb-object' "
		."data-object-class='" . get_class($comment) . "' "
		."data-object-id='$comment_id'"
	.">";
		echo $comment->htmlQomment();
		echo "&nbsp; ";
		echo "<span class='sayswho'>";
			echo "(by ";
			echo $comment->whoshort();
			echo ", ";
			echo $comment->ago();
			echo ")";
		echo "</span>\n";
		$assocs   = Verb::associations(array(NounClass::Comment => $comment->id()));
		$assocsMe = Verb::associations(array(NounClass::Comment => $comment->id(), 'subject_id' => UserQiki::$client->id()));
		if ($assocs != array()) {
			foreach($assocs as $verbname => $value) {
				$value = doubleval($value);
				if (isset($assocsMe[$verbname])) {
					$valueMe = doubleval($assocsMe[$verbname]);
				} else {
					$valueMe = 0.0;
				}
				$valueOthers = $value - $valueMe;
				
				
				
				$numberMe =    $valueMe     == 0.0 ? '' : strval($valueMe);
				$numberTotal = $valueOthers == 0.0 ? '' : strval($value);
				
				
				
				$tooltip = '';
				if ($valueMe == 0.0) {
					$numberMe = '';
					$what = 'this comment';
					$classes = 'mezero';
				} else {
					$numberMe = strval($valueMe);   // Top number is client's scoring, displayed if nonzero
					$what = 'it';
					if ($valueMe == 1.0 && $valueOthers == 0.0) {
						$classes = 'melast';
					} else {
						$classes = 'menozero';
					}
					$s = (($valueMe == 1.0) ? '' : "'s");
					$tooltip .= "You rated this comment $valueMe $verbname$s.";
				}
				
				if ($valueOthers == 0.0) {
					$numberTotal = '';
				} else {
					$numberTotal = strval($value);   // Bottom number is total scoring, displayed IF DIFFERENT FROM CLIENT'S (i.e. if other's scoring is nonzero)
					$s = (($valueOthers == 1.0) ? '' : "'s");
					if ($tooltip != '') $tooltip .= "\n";
					$tooltip .= "Others rated $what $valueOthers $verbname$s.";
				}
				
				if ($valueMe != 0.0 && $valueOthers != 0.0) {
					$tooltip .= " Total $value.";
				}
				
				
				
				if ($value != 0.0 || $valueMe != 0.0) {
					$verb = new Verb($verbname);
					echo $verb->img(array('tooltip' => $tooltip, 'postsup' => $numberMe, 'postsub' => $numberTotal, 'class' => $classes));   
							
					// TODO:  encapsulate somehow, e.g. 'subject_id' => UserQiki::$client->id(), 'showValues' => TRUE));
				}
			}
		}
		if ($SHOW_VERBBAR) {
			verbexpander(get_class($comment), $comment->id());
		}
	echo "</span>";
	echo "\t<br />\n";
}
$numRowsNotShown = max(0, $totalrows - $limitrows);
if ($numRowsNotShown > 0) {
	echo "<p>... $numRowsNotShown older comments ...</p>\n";
}


htmlfoot();

function verbexpander($obj, $objid) {   // VERBBAR
	echo "\t<span class='verbcombo' data-obj='$obj' data-objid='$objid'>\n";
		$verbTool = new Verb('tool');
		echo "\t\t" . $verbTool->img(array('title' => 'see verbs', 'class' => 'verbopen')) . "\n";
		echo "\t\t<span class='verbbar'>\n";
			foreach(Verb::all() as $verbEach) {
				if ($verbEach->id() != $verbTool->id()) {
					echo "\t\t\t" . $verbEach->img() . "\n";
				}
			}
		echo "\t\t</span>\n";
	echo "\t</span>\n";
}

?>