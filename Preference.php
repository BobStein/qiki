<?php

require_once('Noun.php'); 

class Preference implements NounLean 
{
	private $id;
	private $name;
	static private $tableGrid = array(
		'1' => 'anonymous',
		'2' => 'spam',
		'3' => 'qoolopen',
	);
	static $defaultFromUser = array(
		'anonymous' => FALSE,
		'spam' => FALSE,
        'qoolopen' => FALSE,
	);
    public function __construct($name) 
    {
        $this->name = $name;
        $this->id = FALSE;
    }
    public function info() 
    {
        return $this->name;
    }
    static public function selectInfo($name) 
    {
        if (($key = array_search($name, static::$tableGrid)) !== FALSE) {
        
            assertTrue(!is_null($key));
            // Why the f should I have to convert a string-key to a string?  
            $key = strval($key);   
            // Because apparently array_search() converted the string-of-digits key it was supposed to output, to an integer.
            // Reminiscent of confusion over what is_numeric() means, e.g   TRUE === is_numeric('123')   &&   TRUE === is_numeric(123)
            assertTrue(is_string($key));
            
            $that = new static(static::$tableGrid[$key]);
            $that->id = $key;
            return $that;
        }
        return FALSE;
    }
    static public function selectId($id) 
    {
        if (isset(static::$tableGrid[$id])) {
            $that = new static(static::$tableGrid[$id]);
			$that->id = $id;
            return $that;
		}
        return FALSE;
    }
    public function stow()
    {
        assertFailure("Preference::stow()");
    }
                    // public function is()
                    // {
                    // }
    public function id() 
    {
        return $this->id;
    }
    public function assertValid($classname = NULL) 
    {
        NounLean_assertValid($this, $classname);
    }
    static public function classname()
    {
        return NounClass::Preference;
    }
    
    
    
                    public function __used_to_be__construct($idorname) {
                        if (isset(static::$tableGrid[$idorname])) {
                            $this->id = $idorname;
                            $this->name = static::$tableGrid[$idorname];
                        } else if (($key = array_search($idorname, static::$tableGrid)) !== FALSE) {
                            $this->id = $key;
                            $this->name = static::$tableGrid[$key];
                        } else {
                            $this->id = NULL;
                            $this->name = NULL;
                        }
                    }
                    // public function id() {
                        // return $this->id;
                    // }
	public function name() {
		return $this->name;
	}
                    static public function __used_to_be__fromUser($client_id) {   // array(preference_name => trueORfalse, ...)
                        if ($client_id == User::NOUSERID) {
                            return static::$defaultFromUser;
                        }
                        $wheretests = array();
                        $queryparameters = array();
                        
                        $wheretests[] = "s.subject_class = ?";
                        $queryparameters[] = UserQiki::classname();
                        
                        $wheretests[] = "s.subject_id = ?";
                        $queryparameters[] = $client_id;
                        
                        $verbPrefer = new Verb('prefer');
                        $wheretests[] = "s.verb_id = ?";
                        $queryparameters[] = $verbPrefer->id();
                        
                        $wheretests[] = "s.object_class = ?";
                        $queryparameters[] = NounClass::Preference;

                        $WHEREclause = empty($wheretests) ? '' : "WHERE " . join(' AND ', $wheretests);
                        $id2value = Sentence::pdo()->column("
                            SELECT 
                                s.object_id,
                                s.value
                            FROM ".Sentence::tablename()." AS s
                            $WHEREclause
                        ", $queryparameters);
                        $name2bool = array();
                        foreach ($id2value as $id => $value) {
                            $pref = new static($id);
                            if ($value) {
                                $name2bool[$pref->name()] = TRUE;
                            } else {
                                $name2bool[$pref->name()] = FALSE;
                            }
                        }
                        return $name2bool;
                    }
}
