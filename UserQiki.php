<?

// UserQiki - User class for qiki.info
// --------

require_once('User.php');
require_once('SteinPDO.php');

User::$PATHCONTEXT = "/";  // context is all of http://qiki.info/
User::$COOKIEPREFIX = "qiki";

class UserQiki extends User {
	public function __construct() {
		parent::__construct(SteinTable::pdo());
		$this->option('anonymous_allow', 'always');
		$this->login();
	}
	
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
		$opts += array(
			'hidepassword' => 'toggle',
		);
		return parent::signupForm($opts);
	}
}


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

?>