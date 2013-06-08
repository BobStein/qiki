<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);

//$DOMAIN = "qi.ki";
$DOMAIN = "qiki.info";
$FORMSUBMITURL = $_SERVER['PHP_SELF'];

require_once('qiki.php'); 
include("/home/visibone/security/qikisql.php");
require_once('User.php');
require_once('Comment.php');
require_once("parameter.php");

User::$PATHCONTEXT = "/";  // context is all of http://qiki.info/
User::$COOKIEPREFIX = "qiki";
User::$ACTIONPREFIX = $FORMSUBMITURL;

try {
	$pdo = new PDO(
			'mysql:'
				."host=$HOST;"
				."dbname=$DATABASE;"
				."charset=utf8",
			$USER,
			$PASS);
	$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$pdo->exec("SET CHARACTER SET utf8");  // see http://stackoverflow.com/questions/4361459/php-pdo-charset-set-names#4361485
} catch (PDOException $e) {
	die("Error connecting to MySQL: " . $e->getMessage());
}

class UserQiki extends User {
	public function loginForm($opts = array()) {
		htmlhead('qiki - log in');
		echo $this->barebonesLoginForm($opts);
		htmlfoot();
		return '';
	}
	public function barebonesLoginForm($opts = array()) {
		return parent::loginForm($opts);
	}
	public function signupForm($opts = array()) {
		htmlhead('qiki - sign up');
		echo $this->barebonesSignupForm($opts);
		htmlfoot();
		return '';
	}
	public function barebonesSignupForm($opts = array()) {
		return parent::signupForm($opts);
	}
}

$user = new UserQiki($pdo);
$user->option('anonymous_allow', 'always');
$user->login();

// switch ($user->raw_login()) {   // good to call even if alreadyLoggedIn in case logging out
// case 'anonymous':
	// allow anonymous users
	// break;
// case 'blank':
	// htmlhead('qiki - log in');
	// echo $user->loginForm(array("error" => "Please enter your user username and password."));
	// htmlfoot();
	// exit;
// case 'noxtable':
// case 'denied':
	// htmlhead('qiki - log in');
	// echo $user->loginForm(array("error" => "Invalid username or password."));
	// htmlfoot();
	// exit;
// case 'signincomplete':
	// htmlhead('qiki - sign up');
	// echo $user->signupForm(array("error" => "Please fill in all fields."));
	// htmlfoot();
	// exit;
// case 'signmismatch':
	// htmlhead('qiki - sign up');
	// echo $user->signupForm(array("error" => "Passwords should match."));
	// htmlfoot();
	// exit;
// case 'signedup':
	// htmlhead('qiki - log in');
	// echo $user->loginForm();
	// htmlfoot();
	// exit;
// case 'loggedin':
	// $user->redirectSame();
	// exit;
// case 'logout':
	// $user->redirectSame();  // allow anonymous users on any page (otherwise we'd need to redirectHome()
	// exit;
// case 'signerror':
// case 'error':
	// echo $user->errorMessage();
	// exit;
// default:
	// echo "Unexpectedly " . $user->status();
	// exit;
// case 'granted':
	// break;
// }

 
if (isset($_REQUEST['action'])) {
	switch($_REQUEST['action']) {
	case 'login':
		echo $user->loginForm();   // in case JavaScript disabled?
		exit;
	case 'logout':
		$user->force_logoff();
		$user->redirect($GLOBALS['BASE']);
		exit;
	case 'signup':
		echo $user->signupForm();   // in case JavaScript disabled?
		exit;
	case 'newcomment':
		parameter('qomment');
		parameter('kontext');
		if ($user->alreadyLoggedIn()) {
			$qontributor = $user->id();
		} else {
			$qontributor = $_SERVER['REMOTE_ADDR'];
		}
		try {
			$stmt = $pdo->prepare("
				INSERT INTO comments ( qomment,  qontributor,  kontext, created) 
						      VALUES (       ?,            ?,        ?,   NOW())");
			$stmt->execute(array     ($qomment, $qontributor, $kontext         ));
		} catch (PDOException $e) {
			die($e->getMessage());
		}
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

Comment::$pdo = $pdo;
$comments = Comment::byKontext(kontext(), "ORDER BY created DESC");

htmlhead(qontext() . ' - qiki');

?>
	<div class="necorner">
		<?php
			if ($user->alreadyLoggedIn()) {
				echo "<div id='loglink'>";
				echo "(<a id='logoutlink' href='$FORMSUBMITURL?action=logout'>logout</a> as {$user->name()})";
				echo "</div>\n";
			} else {
				echo "<div id='loglink'>";
				echo "(<a id='loginlink' href='$FORMSUBMITURL?action=login'>login</a>)";
				echo "</div>\n";
			}
		?>
		<div id='loginform'>
			<span id='orsignup'>(or <a href='<?php echo $FORMSUBMITURL; ?>?action=signup' title="if you don't have an account">sign up</a>)</span>
			<?php echo $user->barebonesLoginForm(); ?>
		</div>
		<div id='signupform'>
			<span id='orlogin'>(or <a href='<?php echo $FORMSUBMITURL; ?>?action=signup' title="if you already have an account">log in</a>)</span>
			<?php echo $user->barebonesSignupForm(); ?>
		</div>
	</div>
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
echo "<table>\n";
foreach ($comments as $comment) {
	echo "<tr>";
	echo "<td>";
	echo $comment->htmlQomment();
	//echo "<td>";
	echo "&nbsp; ";
	echo "<span class='sayswho'>";
		echo "(by ";
		echo $comment->whoshort();
		echo ", ";
		echo $comment->ago();
		echo ")";
	echo "</span>";
	echo "\n";
}
echo "</table>\n";

echo "<!-- \n";
echo "\$_SERVER[]:\n"; var_export($_SERVER);
echo "\$_REQUEST[]:\n"; var_export($_REQUEST);
echo "\$_POST[]:\n"; var_export($_POST);
echo "\$_COOKIE[]:\n"; var_export($_COOKIE);
echo "\$_SESSION[]:\n"; var_export($_SESSION);
// var_export($_GET);
echo " -->\n";

htmlfoot();


function htmlhead($title) {
	?>
		<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
		<html xmlns="http://www.w3.org/1999/xhtml">
		<head>
			<title><?php echo $title; ?></title>
			<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
			<script type="text/javascript" src="//code.jquery.com/jquery-latest.min.js"></script>
			<script type="text/javascript" src="/qiki.js"></script>
			<link type="text/css" href="/qiki.css" rel="stylesheet" />
			<?php echo "\n";
				User::js(); 
			?>
		</head>
		<body>
	<?php echo "\n";
}

function htmlfoot() {
	?>
		</body>
		</html>
	<?php echo "\n";
}

?>