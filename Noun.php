<?php

// TODO: can these NounClass::xxx constants just go away?  
// Can the info be embodied instead in the descendant/derived/sub classes of Noun?  
// And provided by the Noun::classname() implementations
// And checked via is_subclass_of($classname, Noun_classname())
// Except then there would be no way to get allNounClasses() -- see the dynamic-load proviso at http://stackoverflow.com/a/436204/673991
// And if MySQL and PHP names were ever different there's no way to instantiate a class by asking "What's the subclass of Noun whose classname() member returns X?"
//      (and we COULD do that now by reverse-engineering (array_flip()ing) allNounClasses())
// Or we could make a global array instead, e.g.
//      $NounClasses = array();
// and at the end of every class definition do e.g.
//      $NounClasses[__CLASS__] = static::classname();
// or a singleton class NounClasses
//      NounClasses::register(__CLASS__, static::classname();
// and would that get called when it's autoloaded the first time before it was needed?
// ah there's no way to test if autoload WILL work before trying it
// except is_subclass_of() implies it will autoload if no 3rd paramter false or something, cool

interface NounClass {   // PHP symbols for MySQL names
	const Verb         = 'Verb';     	// don't be alarmed, the word "verb" is a noun, this might be used e.g. to state User X likes Verb Y
	const Comment      = 'Comment';     // human answers (clause only)
	const UserQiki     = 'UserQiki';	// DONE: how to handle new User versus new UserQiki?
 	const QikiQuestion = 'QikiQuestion';// 
 	const IPAddress    = 'IPAddress';	// and store the ID using *unsigned* ip2long() 
	const Preference   = 'Preference';	// user preference, e.g. seeing anonymous comments, or spam
	const UnusedNoun   = 'UnusedNoun';  // for sentences with 1 noun or 0 nouns??
	const Sentence     = 'Sentence';	// (a sentence can refer to another sentence, e.g. for 3 or more nouns)
	const Author       = 'Author';      // maybe it should be called Variant, but it tracks the different answers, or something

	const Script       = 'Script';		// for UserQiki::may(UserQikiAccess::blah, NounClass::Script)     (NOTE: only NounClass that isn't a php class name AND isn't a qiki database table name)
										// Instead: qiki/qiki/software/xxxxx.php?  Or let a sentence make that association?
	const QikiML       = 'QikiML';      // Qiki answers (clause only)
	
//  const Variant                       // instead of Author
// 	const URL
//	const Server or Site				// as in, a satellite qiki.somewheresite.com
//	const Scorer
//	const Feature instead of Preference?
//	const Language						// (human)
//	const Proposal						// a call for human action, e.g. development, censure, mediation, etc., including bounties and anti-bounties (donations if it fails)
//	const Time							// 64 bit DATETIME, for users to say when stuff happened, e.g. [Time]->(born)->[User/...]  or  ->[Qiki/qiki/human/...]
//	const AideMemoire					// VisiBone cheatsheet, example2 or syntax3
//	const Group or Badge				// Anonymous, Human, address-verified, bot, vouched-for, SuperUser, Developer, badges ala Stackexchange, native as in language
//  const Group                         // a company, organization, nation, family, ethnic group, etc.  Maybe 
//  const NounLean                      // can a NounLean or is it a NounClass ever be a type (class) of Noun?!?  Getting a headache...
}

// Note these NounClass names must be secure, as they are not protected from injection attack (no check made whether they have ' in them).
// TODO: move these constants to the Noun interface?
// These constants are used in PHP, e.g. NounClass::Verb, to represent the values of noun class "enum" columns in the MySQL DataTables:
//     qiki.Sentence.object_class
//     qiki.Sentence.subject_class
//     qiki.Clause.noun_class
// They must always be identical (constant symbol, and its value), 
// or all the "select" functions will have to do something more complicated than their scary, variable-class-name one-liners:
//     NounLean_selectId()  ...  return $classname::selectId($id);
//     Sentence::selectId() ...  $that = new static($info);
//     Clause::selectId()   ...  $noun = new $row['noun_class']($row['noun_info']);
// for example, hmm, they could reverse-engineer NounClass:  $classPhp = array_flip(allNounClasses())[$classMysql];  wowee

// In Noun classes, the constructor takes a single "info" parameter, which may be an array.  The info() member returns this same content
// In NounLean classes, in addition to the above rule for the constructor, there's a "selectId()" constructing method (i.e. returns a new instance) that takes an id

// TODO: talk about the different forms that noun class names take, and which can be different and what it would mean
//      C - e.g. the PHP identifier 'Verb' for the PHP class Verb
//      I - e.g. the PHP identifier 'Verb' for the PHP interface constant NounClass::Verb
//      V - e.g. the value 'Verb' for the PHP interface constant NounClass::Verb
//          e.g. also the value 'Verb' stored in the MySQL noun class column Sentence.object_class (for a sentence meta-talking ABOUT verbs)
//          e.g. also the value 'Verb' returned by XXX::classname()
//      T - e.g. the MySQL table named 'Verb' in the schema named 'qiki'
//          e.g. also the value 'Verb' returned by XXX::tablename()
// e.g. the interface NounClass constants translate I => V, and that's association allNounClasses() returns an array of
// e.g. Noun::classname() gives the MySQL column, and effectively translates C => V
// So outside of a noun's class declaration, the PHP code only directly refers to the class identifier C.
// and it uses XXX::classname() for V
// and it uses XXX::tablename() for T
// and it never uses I because it can just call classname()


function allNounClasses()   // array('Verb' => 'Verb', ...)  PHP symbol to MySQL "enum" field values
{   
	$r = new ReflectionClass('NounClass');
	return $r->getConstants();
}
function isNounClass($nclass) {
    if (!is_string($nclass)) return FALSE;
    return isSubclass($nclass, Noun_classname()) !== FALSE;
}
function isNounLeanClass($nclass)
{
    if (!isNounClass($nclass)) return FALSE;
    return isSubclass($nclass, NounLean_classname()) !== FALSE;
}
function isSubclass($subclassname, $classname)   // returns TRUE or FALSE or 'MAYBE'
{
    if (version_compare(PHP_VERSION, '5.3.7') >= 0) {   // see http://php.net/manual/en/function.is-subclass-of.php#refsect1-function.is-subclass-of-changelog
        return is_subclass_of($subclassname, $classname);
    } elseif (version_compare(PHP_VERSION, '5.1.0') >= 0) {   // see http://php.net/class_implements#refsect1-function.class-implements-changelog
        return in_array($classname, class_implements($subclassname));
    } else {
        return 'MAYBE';
    }
}
/*

A lean Noun has an id, and can be in a Sentence, as either the subject or object.
A Noun that isn't lean can only be in a Clause, e.g. a Comment.  
Some lean nouns might also be in a Clause, e.g. a Qiki (but not all, e.g. a Sentence)

*/

// DONE: make Noun::classname() static BECAUSE $noun::classname() works via the late-static-binding run-time class of $noun determining which static classname() to call

interface Noun                           
{
    public function info();              // guts of the instance.  info() is the complement of the constructor:  what info() returns is what the constructor expects as a parameter   // TODO: does info() represent ALL the guts of the instance, or just what it wouild take to GET the guts from the database?  E.g. for sentence, subject,verb,object of course.  But value?  modified?  clauses??
    public function assertValid($classname = NULL);       // (move to a different interface?)
    static public function classname();  // for MySQL, one of the NounClass constants, it's the string that's stored in the DataTable noun class columns   
}
interface NounLean extends Noun           // A Noun with an id.  The object or subject of a Sentence.  A few may be the (non-lean) noun in a Clause (the constructor must take a string)
{
    public function id();                 // e.g. a DataTable id -- an all-decimal string -- or FALSE if not knowable  (id() usually calls is())  // TODO Make id() more efficient by calling and reading if info-generated, not if id-generated
    // public function is();                 // may ask a DataTable if the info is understood / known / familiar, e.g. exists already.  If so returns TRUE
    public function stow();               // bolder than id(), this sets an object in stone, e.g. either by getting it from a DataTable or storing it there.  Returns FALSE if unimplemented or impossible.  Otherwise TRUE, and then id() !== FALSE   TODO: exception rather than FALSE, and repackage MySQL exception too
    static public function selectId($id); // construct a lean noun by reading it from a DataTable by its id.  Returns noun or fatal error.  TODO: exception
                                          // selectId() is the complement of stow():  $id-->selectId()-->instance with content   instance with content-->stow()-->now id() works
    static public function selectInfo($info); // construct lean noun(s) by e.g. reading from a DataTable by content.  Returns noun or FALSE.  (Or Sentence returns array(noun...) or array()).  Clearly this will simplify someday.
                                          // TODO: move ->selectInfo() to Noun interface?  Allow selection of clause by content, e.g. outermost element ID in QikiML?
                                          // TODO: confusing, caller might not realize need for selectInfo(), may try instead to new SomeLeanClass($info) then call id() and wonder why it returns FALSE
}

// TODO: does NounLean break LSP?
// Maybe yes because a Clause can store any non-lean noun, but not some lean nouns, e.g. a Sentence
// Maybe no because we *could* store a Sentence id in the Clause.noun_info column verbatim, as a decimal integer.  So any lean noun could be claused.
// Maybe yes because, well, will Clause::stow() ever be that quirky?
// Maybe no because the capability of a noun to be stored in a Clause may not be a "provable property" of the noun in the strict LSP sense
// Maybe no because the fact that a Comment can be a Noun is definitely not a provable property of a noun, that no NounLean could ever possess
// Maybe no because "can be stored in a Clause" is not a universal, provable property of all nouns, e.g. Sentence may be an exception



function Noun_assertValid(Noun $noun, $classname = NULL) 
{
    assertTrue($noun instanceof Noun);   // redundant to type hinting?
    assertTrue(isNounClass($noun::classname()));
    if (!is_null($classname)) {
        assertIsa($noun, $classname);   // was , "Not a '$classname': " . var_export($noun, TRUE));   // Fatal error: Nesting level too deep - recursive dependency? 
    }
    // $noun->classname()::assertValid($noun);   // Oh phooey, this could be infinite recursion!?!  How do we call that when it wasn't the caller?
}
function Noun_factory($classname_or_array, $info_or_array = NULL)   // construct a noun, or an array of nouns   (Not sure when I'll need the array aspect, so that's untried.)
{
    if (is_array($classname_or_array) && is_array($info_or_array)) {
        $classnames = $classname_or_array;
        $infos = $info_or_array;
        assertTrue(count($classnames) == count($infos), "Noun::factory() array count mismatch, " 
            . "class has " . count($classnames) 
            . " and info has " . count($infos)
        );
        $retval = array();
        while ($classnames != array()) {
            $retval[] = Noun_factory(array_shift($classnames), array_shift($infos));
        }
        return $retval;
    } else {
        $classname = $classname_or_array;
        $info = $info_or_array;
        assertTrue(isNounClass($classname));
        return new $classname($info);
    }
}
function Noun_classname()
{   
    return 'Noun';
}


function NounLean_assertValid(NounLean $nounlean, $classname = NULL) 
{
    Noun_assertValid($nounlean, $classname);
    assertTrue($nounlean instanceof NounLean);   // redundant to type hinting?
    assertTrue($nounlean->id() === FALSE || alldigits($nounlean->id()), "Id neither digits nor FALSE: " . var_export($nounlean->id(), TRUE));
}
function NounLean_selectId($classname_or_array, $id_or_array)    // "select" meaning read the info of a lean noun (any lean noun) from a DataTable
{
    if (is_array($classname_or_array) && is_array($id_or_array)) {
        $classnames = $classname_or_array;
        $ids = $id_or_array;
        assertTrue(count($classnames) == count($ids), 
            "NounLean_selectId() array count mismatch, " 
            . "classname array(" . count($classnames) . ") and "
            .        "id array(" . count($ids       ) . ")"
        );
        $retval = array();
        foreach ($classnames as $classname) {
            $id = array_shift($ids);
            $retval[] = NounLean_selectId($classname, $id);
        }
        return $retval;
    } else if (is_string($classname_or_array) && is_string($id_or_array)) {
        $classname = $classname_or_array;
        $id = $id_or_array;
        assertTrue(isNounClass($classname));
        assertTrue(alldigits($id));
        return $classname::selectId($id);        
        // TODO: reverse-lookup allNounClasses() before calling $classname here
        // TODO? Noun_selectId($classname, $id_or_info)
	} else {
        assertFailure("Invalid types, NounLean_selectId(".gettype($classname_or_array).",".gettype($id_or_array).")");
    }
}
function NounLean_classname()
{
    return 'NounLean';   // TODO?  return NounClass::NounLean;
}
