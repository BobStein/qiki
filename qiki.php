<?php
//
// qiki.php
// --------
//
// Generic includable for qiki site.


require_once('Noun.php'); 

define('DEBUG', TRUE);   // display PHP errors, jquery.js; FALSE for jquery.min.js
define('JQUERY_MIN', TRUE);   // minimize jQuery source
define('JQueryVersion', '1.10.2');
define('JQueryUIversion', '1.10.2');   // UI 1.10.3 has draggable bug   http://bugs.jqueryui.com/ticket/9315   http://jqueryui.com/changelog/1.10.3/

define('DOMAIN', "qiki.info");
$FORMSUBMITURL = $_SERVER['PHP_SELF'];
$ALLOW_SIGNUP = FALSE;
$QI_COLOR = "#0050D0"; // Qi background blue
$KI_COLOR = "#FFD800"; // Ki background yellow



if (DEBUG) {
	error_reporting(E_ALL);
	ini_set("display_errors", 1);
}
if (JQUERY_MIN) {
	define('DOTMIN', '.min');
} else {
	define('DOTMIN', '');
}



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
if (!UserQiki::client()->may(UserQikiAccess::see)) {
	die("<p>qiki.info is temporarily down.</p>\n");
}

function htmlhead($title, $opts = array()) {   // TODO: move JavaScript and CSS loading to htmlfoot()?  Allow $opts both places, but execute them all in htmlfoot()???
	global $FORMSUBMITURL;
	global $ALLOW_SIGNUP;
	
	$opts += array(
		'head' => array(),				// extra lines in the <head> ... </head>, e.g. array("<meta something>\n")
		'cssphp' => array(),			// for CSS that has PHP in it, e.g. array("/home/visibone/public_html/utils.css.php")
		'js' => array(),				// extra JavaScript, e.g. array("//ajax.googleapis.com...")
		
		/* northeastcorner() options too */
	);
	QikiConnectLogin();   // need to call here so UserQiki::client() is available, in case htmlhead() is called above including qiki.php, taking advantage of forward-reference

	?>
		<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
		<html xmlns="http://www.w3.org/1999/xhtml">
		<head>
			<title><?php echo $title; ?></title>
			<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
			<meta charset="utf-8" />
			<?php echo "\n";
				echo headersJQuery();
			?>
			<!-- script src="//cdn.mathjax.org/mathjax/latest/MathJax.js?config=TeX-AMS-MML_HTMLorMML" type="text/javascript"></script  - it was jarring and annoying  -->
			<script type="text/javascript">
				FORMSUBMITURL = '<?php echo addslashes($FORMSUBMITURL); ?>';   // TODO:  Move all this to qiki.js.php or something
				User_ERROR_START = decodeURIComponent('<?php echo rawurlencode(User::ERROR_START); ?>');  // thwart detection of these strings in inline JS code
				User_ERROR_END   = decodeURIComponent('<?php echo rawurlencode(User::ERROR_END  ); ?>');
			</script>
			<script src="//<?php echo DOMAIN; ?>/qiki.js" type="text/javascript"></script>
			<link  href="//<?php echo DOMAIN; ?>/qiki.css" rel="stylesheet" type="text/css" />
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
				assertTrue(is_array($opts['js']), "htmlhead() Option 'js' must be an array, not a " . gettype($opts['js']) . ".");
				foreach ($opts['js'] as $js) {
					assertTrue(is_string($js), "htmlhead() Invalid option in 'js' array, should be a string, not a " . gettype($js) . ".");
					echo "\t\t\t<script src='$js' type='text/javascript'></script>\n";
				}
			?>
			<link href="//<?php echo DOMAIN; ?>/favicon.ico" rel="icon"          type="image/x-icon" />
			<link href="//<?php echo DOMAIN; ?>/favicon.ico" rel="shortcut icon" type="image/x-icon" />
		</head>
		<body>
	<?php echo "\n";
	
	

	if (UserQiki::client()->may(UserQikiAccess::see, NounClass::Script, $_SERVER['SCRIPT_FILENAME'])) {
		northeastcorner(array('signup' => UserQiki::client()->may(UserQikiAccess::signup)) + $opts);
	} else {
		northeastcorner(array('signup' => FALSE) /* don't pass any other $opts[] */);
		
		// But what if we're inside UserQiki::loginForm()?  This thwarts the printing of the login form!
		// Only way out of this dilemma is the popup login form already in the NorthEast coner.
		// Though login errors (e.g. wrong password) don't show login errors, they just show the message here.
		// so we could add to the above if-clause && UserQiki::client()->status() isn't anonymous or login etc etc lots of ugly insider knowledge of class User...
		// This will come back to bite.
		
		echo "You don't have access to this page.\n";
		htmlfoot();
		exit;
	}
}

function headersJQuery() {
	return "
			<script src='//ajax.googleapis.com/ajax/libs/jquery/" . JQueryVersion . "/jquery" . DOTMIN . ".js' type='text/javascript'></script>
			<script type='text/javascript'>
				'jQuery' in window || document.write(
					\"<\"+\"script src='//tool.qiki.info/js/jquery-1.10.2" . JQueryVersion . DOTMIN . ".js' type='text/javascript'>\"
					+\"<\\/script>\\n\"
				);
			</script>
	\n";
}
function headersJQueryUI() {
	return "
			<script src='//ajax.googleapis.com/ajax/libs/jqueryui/" . JQueryUIversion . "/jquery-ui" . DOTMIN . ".js' type='text/javascript'></script>
			<script type='text/javascript'>
				'ui' in window.jQuery || document.write(
					\"<\"+\"script src='//tool.qiki.info/js/jquery-ui-" . JQueryUIversion . DOTMIN . ".js' type='text/javascript'>\"
					+\"<\\/script>\\n\"
				);
			</script>
			<link href='//ajax.googleapis.com/ajax/libs/jqueryui/" . JQueryUIversion . "/themes/start/jquery-ui" . DOTMIN . ".css' type='text/css' rel='stylesheet' />
	\n";    // Note, no local backup for jQuery UI CSS files (there are a bunch) from Google - hoping it will work if ugly
}

function northeastcorner($opts = array()) {
	global $FORMSUBMITURL;

	$opts += array(
		'signup' => TRUE,
		'htmlNortheast' => '',
	)
	?>
		<div class="necorner">
			<?php echo "\n";
				echo $opts['htmlNortheast'];
				// echo gettype(UserQiki::client());
				if (UserQiki::client()->is()) {
					echo "<span id='loglink'>";
					echo "(<a id='logoutlink' href='" . UserQiki::client()->logouturl() . "'>logout</a> as " . UserQiki::client()->name() . ")";
					echo "</span>\n";
				} else {
					echo "<span id='loglink'>";
					echo "(<a id='loginlink' href='" . UserQiki::client()->loginurl() . "'>login</a>)";
					echo "</span>\n";
				}
			?>
			
			<div id='loginform'>
				<?php echo "\n";
					if ($opts['signup']) {
						?>
							<span id='orsignup'>(or <a href='<?php echo $FORMSUBMITURL; ?>?action=signup' title="if you don't have an account">sign up</a>)</span>
						<?php echo "\n";
					}
				?>
				<?php echo "\n" . UserQiki::client()->barebonesLoginForm(); ?>
			</div>
			
			<?php echo "\n";
				if ($opts['signup']) {
					?>
						<div id='signupform'>
							<span id='orlogin'>(or <a href='<?php echo $FORMSUBMITURL; ?>?action=login' title="if you already have an account">log in</a>)</span>
							<?php echo UserQiki::client()->barebonesSignupForm(); ?>
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

	if (UserQiki::client()->isSuper()) {   // TODO: call client()->may() instead?
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

function qontext() {  // Human readable context, without the initial slash
	$retval = kontext();
	return $retval;
}

function kontext() {  // Machine usable context, with the slash
	$retval = $_SERVER['REQUEST_URI'];
	$retval = preg_replace('#^/#', '', $retval, 1);  // Only remove one initial slash, the one unavoidable in the URL, this is the only place this happens
	$retval = rawurldecode($retval);   // DONE:  does NOT turn + into space!  Not urldecode().  Does decode %NN symbols.
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


// TODO: Export to assertQiki.php?  Part of unit testing?
// Allow 2nd parameter function, that is only called if the assert failure, 
// except it MAY be called when the assert passes for EVERY test to verify they all work!
// TODO: Extract and show the line of source code?
// link to a web page that displays the code with syntax highlighting, and highlights the assert line
// version-dependent!  It could extract from the git repository ASSUMING there was a fetch, 
// maybe the time and date of the most recent fetch should be known by the source code
// i.e. the revision hash or whatever git calls it

function assertFailure($message = NULL)
{
	if (!isset($message)) {
		$message = 'ASSERT FAILURE';
	}
    echo "\nThe upside-down stack:\n";
    echo "<pre>\n";
        debug_print_backtrace();
        echo "\n";
    echo "</pre>\n";
    die($message);
}
function assertTrue($test, $message = NULL) 
{
    if ($test === TRUE) {
        return;
    } elseif ($test === FALSE) {
        assertFailure($message);
    } else {
        assertFailure(
            "Warning: assertion is neither boolean TRUE nor FALSE: " 
            . "''" . var_export($test, TRUE) . "''\n"
            . "The message was: ''$message''"
        );
    }
}
function assertIsa($o, $classname) 
{
    assertTrue(is_a($o, $classname));
}
