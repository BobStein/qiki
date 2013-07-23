<?php
//
// qiki.php
// --------
//
// Generic includable for qiki site.

define('DEBUG', TRUE);   // display PHP errors, jquery.js; FALSE for jquery.min.js
define('JQUERY_MIN', FALSE);   // minimize jQuery source

if (DEBUG) {
	error_reporting(E_ALL);
	ini_set("display_errors", 1);
}



$DOMAIN = "qiki.info";
$FORMSUBMITURL = $_SERVER['PHP_SELF'];
$ALLOW_SIGNUP = FALSE;
$QI_COLOR = "#0050D0"; // Qi background blue
$KI_COLOR = "#FFD800"; // Ki background yellow

require_once('SteinPDO.php');
require_once('UserQiki.php');
require_once('Verb.php');



$GLOBALS['QikiConnectLogin_already'] = FALSE;

// Here's the infinite loop that's broken by $QikiConnectLogin_already:
//
// QikiConnectlogin() --> UserQiki::clientlogin() 
//                    --> UserQiki::__construct() --> User::login() 
//                                                --> User::handle_login() --> UserQiki::loginForm() --> htmlhead() --> QikiConnectlogin()

function QikiConnectLogin() {  // Connects MySQL and determines user login status (a second call has no effect)
	if ($GLOBALS['QikiConnectLogin_already']) return;
	$GLOBALS['QikiConnectLogin_already'] = TRUE;
	
	include("/home/visibone/security/qikisql.php");
	SteinTable::connect($USER, $PASS, array('host' => $HOST, 'database' => $DATABASE));
	UserQiki::clientlogin();
}
QikiConnectLogin();
if (!UserQiki::$client->may(UserQikiAccess::see)) {
	die("<p>qiki.info is temporarily down.</p>\n");
}

function dotmin() {
	if (JQUERY_MIN) echo '.min';
}

function htmlhead($title, $opts = array()) {   // TODO: move JavaScript and CSS loading to htmlfoot()?  Allow $opts both places, but execute them all in htmlfoot()???
	global $DOMAIN;
	global $FORMSUBMITURL;
	global $ALLOW_SIGNUP;
	
	$opts += array(
		'head' => array(),				// extra lines in the <head> ... </head>, e.g. array("<meta something>\n")
		'cssphp' => array(),			// for CSS that has PHP in it, e.g. array("/home/visibone/public_html/utils.css.php")
		'js' => array(),				// extra JavaScript, e.g. array("//ajax.googleapis.com...")
	);
	QikiConnectLogin();   // need to call here so UserQiki::$client is available, in case htmlhead() is called above including qiki.php, taking advantage of forward-reference

	?>
		<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
		<html xmlns="http://www.w3.org/1999/xhtml">
		<head>
			<title><?php echo $title; ?></title>
			<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />

			<script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery<?php dotmin(); ?>.js" type="text/javascript"></script>
			<script type="text/javascript">window.jQuery || document.write("<"+"script src='//tool.qiki.info/js/jquery-1.10.2<?php dotmin(); ?>.js' type='text/javascript'><\/script>\n")</script>
			<!-- script src="//cdn.mathjax.org/mathjax/latest/MathJax.js?config=TeX-AMS-MML_HTMLorMML" type="text/javascript"></script -->
			<script type="text/javascript">
				FORMSUBMITURL = '<?php echo addslashes($FORMSUBMITURL); ?>';
				User_ERROR_START = decodeURIComponent('<?php echo rawurlencode(User::ERROR_START); ?>');  // thwart detection of these strings in JS code
				User_ERROR_END   = decodeURIComponent('<?php echo rawurlencode(User::ERROR_END  ); ?>');
			</script>
			<script src="//<?php echo $DOMAIN; ?>/qiki.js" type="text/javascript"></script>
			<link  href="//<?php echo $DOMAIN; ?>/qiki.css" rel="stylesheet" type="text/css" />
			<?php echo "\n";
				User::js(); 
				foreach ($opts['cssphp'] as $cssphp) {
					echo "\t\t\t<style type='text/css'>\n";
					echo "\n";
					echo "\n";
					echo "\n";
					include $cssphp;
					echo "\n";
					echo "\n";
					echo "\n";
					echo "\t\t\t</style>\n";
				}
				echo join("\n", $opts['head']);   // must come after cssphp, to override
				if (!is_array($opts['js'])) die("htmlhead() Option 'js' must be an array, not a " . gettype($opts['js']) . ".");
				foreach ($opts['js'] as $js) {
					if (!is_string($js)) die("htmlhead() Invalid option in 'js' array, should be a string, not a " . gettype($js) . ".");
					echo "\t\t\t<script src='$js' type='text/javascript'></script>\n";
				}
			?>
			<link href="//<?php echo $DOMAIN; ?>/favicon.ico" rel="icon"          type="image/x-icon" />
			<link href="//<?php echo $DOMAIN; ?>/favicon.ico" rel="shortcut icon" type="image/x-icon" />
		</head>
		<body>
	<?php echo "\n";
	
	

	if (UserQiki::$client->may(UserQikiAccess::see, NounClass::Script, $_SERVER['SCRIPT_FILENAME'])) {
		northeastcorner(UserQiki::$client->may(UserQikiAccess::signup));
	} else {
		northeastcorner(FALSE);
		
		// But what if we're inside UserQiki::loginForm()?  This thwarts the printing of the login form!
		// Only way out of this dilemma is the popup login form already in the NorthEast coner.
		// Though login errors (e.g. wrong password) don't show login errors, they just show the message here.
		// so we could add to the above if-clause && UserQiki::$client->status() isn't anonymous or login etc etc lots of ugly insider knowledge of class User...
		// This will come back to bite.
		
		echo "You don't have access to this page.\n";
		htmlfoot();
		exit;
	}
}

function headersJQueryUI() {
	return "
			<script src='//ajax.googleapis.com/ajax/libs/jqueryui/1.10.3/jquery-ui".dotmin().".js' type='text/javascript'></script>
			<script type='text/javascript'>window.jQuery.ui || document.write(\"<\"+\"script src='//tool.qiki.info/js/jquery-ui-1.10.3".dotmin().".js' type='text/javascript'><\/script>\\n\")</script>
			<link href='//ajax.googleapis.com/ajax/libs/jqueryui/1.10.3/themes/start/jquery-ui".dotmin().".css' type='text/css' rel='stylesheet' />
	\n";    // Note, no local backup for jQuery UI CSS files (there are a bunch) from Google - hoping it will work if ugly
}

function northeastcorner($signok = TRUE) {
	global $FORMSUBMITURL;

	?>
		<div class="necorner">
			<?php echo "\n";
				// echo gettype(UserQiki::$client);
				if (UserQiki::$client->alreadyLoggedIn()) {
					echo "<div id='loglink'>";
					echo "(<a id='logoutlink' href='" . UserQiki::$client->logouturl() . "'>logout</a> as " . UserQiki::$client->name() . ")";
					echo "</div>\n";
				} else {
					echo "<div id='loglink'>";
					echo "(<a id='loginlink' href='" . UserQiki::$client->loginurl() . "'>login</a>)";
					echo "</div>\n";
				}
			?>
			
			<div id='loginform'>
				<?php echo "\n";
					if ($signok) {
						?>
							<span id='orsignup'>(or <a href='<?php echo $FORMSUBMITURL; ?>?action=signup' title="if you don't have an account">sign up</a>)</span>
						<?php echo "\n";
					}
				?>
				<?php echo "\n" . UserQiki::$client->barebonesLoginForm(); ?>
			</div>
			
			<?php echo "\n";
				if ($signok) {
					?>
						<div id='signupform'>
							<span id='orlogin'>(or <a href='<?php echo $FORMSUBMITURL; ?>?action=signup' title="if you already have an account">log in</a>)</span>
							<?php echo UserQiki::$client->barebonesSignupForm(); ?>
						</div>
					<?php echo "\n";
				}
			?>
		</div>
	<?php echo "\n";
}

function htmlfoot() {	
	echo "<br />\n";
	echo footerlogo();

	if (UserQiki::$client->isSuper()) {   // TODO: call $client->may() instead?
		echo "<!-- \n";
		echo "\$_SERVER[]:\n"; var_export($_SERVER);
		echo "\$_REQUEST[]:\n"; var_export($_REQUEST);
		echo "\$_POST[]:\n"; var_export($_POST);
		echo "\$_COOKIE[]:\n"; var_export($_COOKIE);
		echo "\$_SESSION[]:\n"; var_export($_SESSION);
		// var_export($_GET);
		echo " -->\n";
	}
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
	$retval = urldecode($retval);   // TODO: should this happen??  It turns + into space!  Why is urldecode() called here?  For %NN symbols?
	return $retval;
}

function footerlogo() {
	return 
		"<p class='footer-logo'>"
			."<a class='footer-link' href='//meta.qiki.info/' target='_blank'>"
				."&copy;"
			."</a>"
			.date('Y')
			.' '
			.qikilogo()
			.".info"
		."</p>\n";
}

function qikilogo() {
	return 
		"<a href='//qiki.info/' class='qiki-logo'>"
			."<span class='qi-logo'>"
				."Qi"
			."</span>"
			."<span class='ki-logo'>"
				."Ki"
			."</span>"
		."</a>";
}
