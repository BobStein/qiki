<?

// UserQiki - User class for qiki.info
// --------

require_once('User.php');
User::$table = 'UserQiki';
require_once('SteinPDO.php');
require_once('Preference.php');
require_once(realpath(dirname(__FILE__) . '/../toolqiki/config/settings.php'));   // thanks http://stackoverflow.com/a/2860184/673991

User::$PATHCONTEXT = "/";  // context is all of http://qiki.info/
User::$COOKIEPREFIX = "qiki";

final class UserQikiAccess {
	const see       = 'see';        // user may 'see' a thing
	const signup    = 'signup';     // user may 'signup' aka create a new user account
	const create    = 'create';     // user may 'create' or 'delete' a thing
	
	// const edit      = 'edit';       // user may 'edit' a thing
	// const delete    = 'delete';     // user may 'delete' a thing
	// const approve   = 'approve';    // user may 'approve' or 'disapprove' a thing
}

class UserQiki extends User {
	static protected $useridBobStein = '1';
	static protected $useridGuest = '2';
	
	static protected $client = NULL;
	public function client() {
		return self::$client;
	}
	static public function clientlogin() {  // (a second call has no effect ( is that still necessary after the QikiConnectLogin_already flag? ) )
		if (is_null(self::$client)) {
			self::$client = new self();
			if (self::$anonymous_allow) self::$client->option('anonymous_allow', 'always');
			self::$client->option('signup_allow', self::$client->may(UserQikiAccess::signup) ? 'yes' : 'no');
			self::$client->login();   // login() --> loginForm() --> htmlhead() needs to use UserQiki::client()
		}
	}
	static public function clientlogoff() {
		self::$client->force_logoff();
		self::$client = NULL;
	}
	public function __construct() {   // singleton class -- should be protected, but:  Fatal error: Access level to UserQiki::__construct() must be public (as in class User)
		parent::__construct(SteinTable::pdo());
	}
	
	public $prefs = NULL;
	public function preference($pref) {
		if (is_null($this->prefs)) {
			$this->prefs = Preference::fromUser($this->id());
		}
		if (!isset($this->prefs[$pref])) {
			return FALSE;
		}
		return $this->prefs[$pref];
	}

	static protected $anonymous_allow = TRUE;  // TODO: a better way (make this static private, and from infra.qiki call UserQiki::anonymousDisallow()?)
	static public function anonymousDisallow() {
		self::$anonymous_allow = FALSE;
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
		// if (self::client()->may(UserQikiAccess::signup)) {
			return parent::signupForm($opts);
		// } else {
		//  	return "Signup is not currently available.";
		// }
	}
	
	static protected $mayers = array();   
	// a "mayer" is a permission arbiter callback function
	// e.g. maySomething(
	//		$this,		// UserQiki instance for user in question
	//		$access,    // UserQikiAccess constant, what kind of action, e.g. UserQikiAccess::see
	//		$object,    // type of object, e.g. "Site"
	//		$context    // specifics of the object, e.g. $_SERVER['SCRIPT_FILENAME']
	// ) {
	//		return 'yes';     // Recognize the object type, access allowed 
	//		return 'no';      // Recognize the object type, access disallowed
	//		return 'maybe';   // Don't recognize the object type, let someone else decide (allowed, if no one recognizes)
	// }
	static public function mayer($mayer) {
		self::$mayers[] = $mayer;
	}
	public function may(
		/* UserQikiAccess:: */ $access, 
		/* NounClass:: */ $object = NULL, 
		$context = NULL
	) {
		foreach (self::$mayers as $id => $mayer) {
			$mayoid = $mayer($this, $access, $object, $context);
			switch (TRUE) {   // type-strict switch, thanks to Greg W: http://stackoverflow.com/a/3525666/673991
			case ($mayoid === 'yes'):
			//se ($mayoid === '1'):
			case ($mayoid === TRUE):
				return TRUE;
			case ($mayoid === 'no'):
			//se ($mayoid === '0'):
			case ($mayoid === FALSE):
			case ($mayoid === NULL):
				return FALSE;
			case ($mayoid === 'maybe'):
				break;
			default:
				die("UserQiki::mayer[$id]() returned '" . var_export($mayoid, TRUE) . "'");   // TODO: identify caller
			}
		}
		return TRUE;   // TODO:  return self::$anonymous_allow ?   Meh, that conflates nonspecificity of user with nonspecificity of object
	}
	
	public function isSuper() {
		return $this->id() == self::$useridBobStein;
	}
	
	public function isGuest() {
		return $this->id() == self::$useridGuest;
	}
}

UserQiki::mayer(function($user, $access, $object, $context) {   // TODO: this could be the one-and-only mayer, except how to decentralize then?
	global $SETTINGS;   // TODO: make forgiving when !isset($SETTINGS['allow-blah']):  (1) function settings(), (2) $('.toggle').each( ... upload all settings every time? ... )
	
	if ($user->isSuper()) {
		return TRUE;
	}
	
	switch ($access) {
	
	case UserQikiAccess::signup:
		return $SETTINGS['allow-signup'];
	
	case UserQikiAccess::create:
		$objecttype = gettype($object);
		switch (TRUE) {
		case ($object === NounClass::Comment):
			return $user->isAnon() ? $SETTINGS['allow-anoncomm'] : $SETTINGS['allow-usercomm'];
		case ($object === NounClass::Verb):
			return $user->isAnon() ? $SETTINGS['allow-anonqool'] : $SETTINGS['allow-userqool'];
		}
		die("Unknown user-may Create object: " . var_export($object, TRUE));
		
	case UserQikiAccess::see:
		$objecttype = gettype($object);
		switch (TRUE) {
		case ($object === NULL):
			return $SETTINGS['allow-any'];
		case ($object === NounClass::Script):
			if (       1 == preg_match('#^/home/visibone/public_html/qiki/index.php#', $context)
			        || 1 == preg_match('#^/home/visibone/public_html/metaqiki/#'     , $context)) {
				return $user->isAnon() ? $SETTINGS['allow-anonseehome'] : $SETTINGS['allow-userseehome'];
			} else if (1 == preg_match('#^/home/visibone/public_html/qiki/qiki404.php#', $context)) {
				return $user->isAnon() ? $SETTINGS['allow-anonseeqiki'] : $SETTINGS['allow-userseeqiki'];
			} else if (1 == preg_match('#^/home/visibone/public_html/devqiki/#', $context)) {
				return $user->isSuper();
			}
			die("Unknown user-may See Script context: " . var_export($context, TRUE));
		case ($object === NounClass::Comment):
			if ($context == 'anon') {  // about anonymous comments:
				return $user->isAnon() ? $SETTINGS['allow-anonseeanon'] : $SETTINGS['allow-userseeanon'];
			} else if ($context == 'user') {   // about all comments (even from a logged-in-user)
				return $user->isAnon() ? $SETTINGS['allow-anonseeuser'] : $SETTINGS['allow-userseeuser'];
			}
			die("Unknown user-may See Comment context: " . var_export($context, TRUE));
		case ($object === NounClass::QikiQuestion):
			if ($context == 'sleepingtool') {
				return $user->isAnon() ? FALSE : TRUE;
			} elseif ($context == 'answer') {
				return $user->isAnon() ? TRUE : TRUE;
			} else {
				die("Unknown See-QikiQuestion context: $context");
			}
		default:
			die("Unknown user-may See object: " . var_export($object, TRUE));
		}
	default:
		die("Unknown user-may access: " . var_export($access, TRUE));
	}
	die("Unexpected UserQiki::mayer() fall-through " . var_export(func_get_args(), TRUE));
});
