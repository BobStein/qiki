<?php
//
// qiki.php
// --------
//
// Generic includable for qiki site.

define('DEBUG', TRUE);   // display PHP errors, jquery.js; FALSE for jquery.min.js
if (DEBUG) {
	error_reporting(E_ALL);
	ini_set("display_errors", 1);
}



$DOMAIN = "qiki.info";
$FORMSUBMITURL = $_SERVER['PHP_SELF'];

include("/home/visibone/security/qikisql.php");
require_once('UserQiki.php');
require_once('SteinPDO.php');

SteinTable::connect($USER, $PASS, array('host' => $HOST, 'database' => $DATABASE));
$user = new UserQiki();


function htmlhead($title) {
	global $DOMAIN;
	global $FORMSUBMITURL;
	?>
		<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
		<html xmlns="http://www.w3.org/1999/xhtml">
		<head>
			<title><?php echo $title; ?></title>
			<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
			<script type="text/javascript" src="//code.jquery.com/jquery-latest<?php if (!DEBUG) echo '.min'; ?>.js"></script>
			<script>
				FORMSUBMITURL = '<?php echo addslashes($FORMSUBMITURL); ?>';
			</script>
			<script type="text/javascript" src="//<?php echo $DOMAIN; ?>/qiki.js"></script>
			<link   type="text/css"       href="//<?php echo $DOMAIN; ?>/qiki.css" rel="stylesheet" />
			<?php echo "\n";
				User::js(); 
			?>
			<link rel="icon"          type="image/x-icon" href="//<?php echo $DOMAIN; ?>/favicon.ico"/>
			<link rel="shortcut icon" type="image/x-icon" href="//<?php echo $DOMAIN; ?>/favicon.ico" />
		</head>
		<body>
	<?php echo "\n";
}

function necorner() {
	global $user;
	global $FORMSUBMITURL;
	?>
		<div class="necorner">
			<?php
				if ($user->alreadyLoggedIn()) {
					echo "<div id='loglink'>";
					echo "(<a id='logoutlink' href='{$user->logouturl()}'>logout</a> as {$user->name()})";
					echo "</div>\n";
				} else {
					echo "<div id='loglink'>";
					echo "(<a id='loginlink' href='{$user->loginurl()}'>login</a>)";
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
	<?php echo "\n";
}

function htmlfoot() {
	echo footerlogo();
	?>
		</body>
		</html>
	<?php echo "\n";
}

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
			."<a class='footer-link' href='//meta.qiki.info/' target='_blank'>&copy;</a>"
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