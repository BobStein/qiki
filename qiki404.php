<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);


require_once('qiki.php'); 
require_once('Comment.php');
require_once('Tool.php');
require_once("parameter.php");

User::$ACTIONPREFIX = $FORMSUBMITURL;

 
if (isset($_REQUEST['action'])) {
	switch($_REQUEST['action']) {
	
	// How can any of these ever happen?
	// case 'login':
		// echo $user->loginForm();   // in case JavaScript disabled?
		// exit;
	// case 'logout':
		// $user->force_logoff();
		// $user->redirect($GLOBALS['BASE']);
		// exit;
	// case 'signup':
		// echo $user->signupForm();   // in case JavaScript disabled?
		// exit;
	case 'tool_associate':
		parameter('toolname');
		parameter('obj');
		parameter('objid');
		if ($user->alreadyLoggedIn()) {
			$qontributor = $user->id();
		} else {
			$qontributor = $_SERVER['REMOTE_ADDR'];
		}
		$tool = new Tool($toolname);
		$association = $tool->associate($obj, $objid, $qontributor);
		echo "success";
		exit;
	case 'newcomment':
		parameter('qomment');
		parameter('kontext');
		if ($user->alreadyLoggedIn()) {
			$qontributor = $user->id();
		} else {
			$qontributor = $_SERVER['REMOTE_ADDR'];
		}
		$comment = Comment::insert($qomment, $qontributor, $kontext);
		// try {
			// $stmt = $pdo->prepare("
				// INSERT INTO comments ( qomment,  qontributor,  kontext, created) 
						      // VALUES (       ?,            ?,        ?,   NOW())");
			// $stmt->execute(array     ($qomment, $qontributor, $kontext         ));
		// } catch (PDOException $e) {
			// die($e->getMessage());
		// }
		echo "success";
		exit;
	case 'newqontext':
		parameter('shouldqontext');
		header('Location: ' . rawurlencode($shouldqontext));  // http://stackoverflow.com/questions/996139/php-urlencode-vs-rawurlencode
		header("Cache-Control: no-cache, must-revalidate");
		exit;
	default:
		die("Unknown action '$_REQUEST[action]'.");
	}
}

header("HTTP/1.0 200 OK for comments");
header("Status: 200 OK for comments");

$comments = Comment::byKontext(kontext(), "ORDER BY created DESC");   // TODO: LIMIT clause

htmlhead(qontext() . ' - qiki');
necorner();

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
				echo qikilink('qiki:' . qontext());
				// TODO:  appear simply boldface like a wiki article self-reference?   As specified at http://en.wikipedia.org/wiki/Help:Link#Wikilinks
				// TODO:  clicking on it allows you to change it?
				// TODO:  ... otherwise dismantle the form and action=newqontext etc.
			?>
			<!-- input name='shouldqontext' type='text' class='should-qontext'
				value="<?php echo htmlspecialchars(qontext()); ?>"
			/ -->
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

// if (count($comments) > 0) {
	// ? >
		// <h4 id='hereswhatothers' class='kare2'>
			// Here&rsquo;s what others had to say:
		// </h4>
	// < ? p h p
// }
//echo "<table>\n";
foreach ($comments as $comment) {
	//echo "<tr>";
	//echo "<td>";
	echo $comment->htmlQomment();
	//echo "<td>";
	echo "&nbsp; ";
	echo "<span class='sayswho'>";
		echo "(by ";
		echo $comment->whoshort();
		echo ", ";
		echo $comment->ago();
		echo ")";
	echo "</span>\n";
	$assocs = Tool::associations(get_class($comment), $comment->id());
	if ($assocs != array()) {
		foreach($assocs as $toolname => $value) {
			$tool = new Tool($toolname);
			echo $tool->img();
			if ($value != 1.0) {
				echo "&times;";
				echo strval($value);
			}
			echo " ";
		}
	}
	
	toolexpander(get_class($comment), $comment->id());
	echo "\t<br />\n";
}
//echo "</table>\n";

echo "<!-- \n";
echo "\$_SERVER[]:\n"; var_export($_SERVER);
echo "\$_REQUEST[]:\n"; var_export($_REQUEST);
echo "\$_POST[]:\n"; var_export($_POST);
echo "\$_COOKIE[]:\n"; var_export($_COOKIE);
echo "\$_SESSION[]:\n"; var_export($_SESSION);
// var_export($_GET);
echo " -->\n";

htmlfoot();

function toolexpander($obj, $objid) {
	echo "\t<span class='toolcombo' data-obj='$obj' data-objid='$objid'>\n";
		$tool = new Tool('tool');
		echo "\t\t" . $tool->img(array('title' => 'see tools', 'class' => 'toolopen')) . "\n";
		echo "\t\t<span class='toolbar'>\n";
			foreach(Tool::all() as $toolEach) {
				if ($toolEach->id() != $tool->id()) {
					echo "\t\t\t" . $toolEach->img() . "\n";
				}
			}
		echo "\t\t</span>\n";
	echo "\t</span>\n";
}

?>