<?php

// qiki404.php
// -----------
// What to run if file not found



error_reporting(E_ALL);
ini_set("display_errors", 1);



$SHOW_VERBBAR = FALSE;   // faded gear-icon next to every comment, when clicked presents all tools.
define('SHOW_VERB_SUMMARY', TRUE);



require_once('UserQiki.php');
User::$ACTIONPREFIX = $_SERVER['PHP_SELF'];
require_once('qiki.php'); 
require_once('Comment.php');
require_once('Noun.php'); 
require_once('Verb.php');
require_once('Sentence.php');
require_once('Scorer.php');
require_once('Preference.php');
require_once('IPAddress.php');
require_once('../toolqiki/php/parameter.php');


 
if (isset($_REQUEST['action'])) {

	header("HTTP/1.1 200 OK");   // not apparently necessary, but overriding the 404 for actions
	switch ($_REQUEST['action']) {
	
	case 'verb_state':
		$verbname = parameter('verbname');
		if (!UserQiki::client()->may(UserQikiAccess::create, Verb::classname(), $verbname)) {
			die("You aren't allowed to use the verb '$verbname'");
		}
		$objclass = parameter('objclass');
		$objid = parameter('objid');
		$value = parameter('value');
		$op = parameter('op');
        $object_s = NounLean_selectId($objclass, $objid);
        if (is_array($object_s)) {
            $objects = $object_s;
        } else {
            $objects = array($object_s);            
        }
        foreach ($objects as $object) {
            $sentence = Sentence::selectInfo(array(
                'subject' => UserQiki::client()->noun(),
                'verb' => Verb::selectInfo($verbname),
                'object' => $object,
            ));
            $sentence->state(array(   // aka $sentence->$op( ... ) except er, that could dangerously allow a mischevous HTTP request to execute any Sentence member
                'op' => $op,
                'value' => $value,
            ));
        }
        
                        // if (UserQiki::client()->is()) {   // TODO: !client()->isAnon()?
                            // $verb->state(array(
                                // 'subject' => new Noun(UserQiki::classname(), UserQiki::client()->id()),
                                // 'object'  => Noun::factory($objclass, $objid),
                                // 'value' => $value,
                                // 'op' => $op,
                            // ));
                        // } else {
                            // $verb->state(array(
                                // 'subject' => new Noun(IPAddress::classname(), strval(ip2long($_SERVER['REMOTE_ADDR']))),
                                // 'object'  => Noun::factory($objclass, $objid),
                                // 'value' => $value,
                                // 'op' => $op,
                            // ));
                        // }
                        
                        
                        
		echo "success";
		exit;
	case 'newcomment':
		$qomment = parameter('qomment');
		$kontext = parameter('kontext');
		if (!UserQiki::client()->may(UserQikiAccess::create, Comment::classname())) {
			die("You don't have access to write comments for this qiki.");
		}
        $qontributor = UserQiki::client()->noun();
		// if (UserQiki::client()->alreadyLoggedIn()) {
			// $qontributor = UserQiki::client()->id();
		// } else {
			// $qontributor = $_SERVER['REMOTE_ADDR'];
		// }
		Comment::insert('comment', $qomment, $qontributor, $kontext);
		// TODO:  support comments on other verbs
		echo "success";
		exit;
	case 'newqontext':   // no longer used, the ability retype the qiki e.g. "php/strlen" without having to edit the address bar
		$shouldqontext = parameter('shouldqontext');
		header('Location: ' . rawurlencode($shouldqontext));  // http://stackoverflow.com/questions/996139/php-urlencode-vs-rawurlencode
		header("Cache-Control: no-cache, must-revalidate");
		exit;
	case 'sleepingtool':   // obsolete, from file-stored HTML, javascript-generated tools?
		$qiki = parameter('qiki');
		if (!UserQiki::client()->may(UserQikiAccess::see, QikiQuestion::classname(), 'sleepingtool')) {
			exit;  // die("You aren't allowed to see details about the qiki '$qiki'.");
		}
		sleepingtool($qiki);
		exit;
	default:
		die("Unknown action '$_REQUEST[action]'.");
	}
}



if (UserQiki::client()->may(UserQikiAccess::see, Comment::classname(), 'anon')) {
	$minlevel = 'anon';
} else {
	$minlevel = 'user';
}

$limitrows = 50;
$comments = Comment::byKontext(kontext(), array(
	'limit' => $limitrows, 
	'totalrows' => &$totalcomments, 
	'minlevel' => $minlevel,
	// 'scorer' => 'spammy',
	// 'maxscore' => 0.5,
	'client_id' => UserQiki::client()->id(),
));



if (UserQiki::client()->isAnon()) {
	$northeast = "
		<span id='prefs' class='disabled-like'>
			<label title='Some users can see anonymous comments.'><input id='showanon' type='checkbox' disabled='disabled' />anon</label>
			<label title='Some users can see spam.'><input id='showspam' type='checkbox' disabled='disabled' />spam</label>
		</span>
	\n";
} else {
	// $prefs = Preference::fromUser(UserQiki::client()->id());
	// var_export($prefs);
	$anonchecked = UserQiki::client()->preference('anonymous') ? "checked='checked'" : '';
	$spamchecked = UserQiki::client()->preference('spam')      ? "checked='checked'" : '';
	$northeast = "
		<span id='prefs'>
			<label title='Show anonymous comments?'  ><input id='showanon' type='checkbox' autocomplete='off' $anonchecked />anon</label>
			<label title='Show comments marked spam?'><input id='showspam' type='checkbox' autocomplete='off' $spamchecked />spam</label>
		</span>
	\n";
}

// TODO:  show (no|mild|all) spam
//                 some
//                 strong
// Options:
//		show all spam
//      show no spam
//		spam filter spammy.visibone.com/spamfilter.php   (using a RESTful submission?)



$answersQiki = array();
if (UserQiki::client()->may(UserQikiAccess::see, QikiQuestion::classname(), 'answer')) {
	$qq = QikiQuestion::selectInfo(kontext());
	$answersQiki = $qq->answers();
}
$numAnswers = count($answersQiki);



if ($numAnswers > 0) {   // ?
	header("HTTP/1.1 200 OK Answered");   // 200 = we have answers
} elseif (count($comments) > 0) {   // ?
	header("HTTP/1.1 200 OK Commented but not Answered");   // 200 = no answers, but there's been some chatter
} else {
	header("HTTP/1.1 404 Not Answered nor Commented");   // 404 = no answers, no chatter (may displace a 400 Bad Request, e.g. 
}
// TODO: better way to set response code?  See http://stackoverflow.com/questions/3258634/php-how-to-send-http-response-code


$headerForAnswers = "
<link type='text/css' href='http://visibone.com/amutils.css.php' rel='stylesheet' />
<link type='text/css' href='//tool.qiki.info/cssbot/php.css' rel='stylesheet' />
";   // css for QikiML

htmlhead(htmlspecialchars(qontext()) . ' - qiki', array(
	'head' => array(headersJQueryUI(), $headerForAnswers), 
	'htmlNortheast' => $northeast,
));



if ($numAnswers > 0) {
    echo "<div class='codeBlock'>\n";
        foreach ($answersQiki as $sentence_id => $answer) {
            echo $answer->clause('QikiML')->info() . "\n";   // DONE: already contains a variant id for a scroll target in the answer, though that's not such a good thing for aggregating answers, e.g. on qiki/php
        }
    echo "</div>\n";
}


$qontext = QikiQuestion::selectInfo(kontext());
// $qontext->stow();   // TODO: track hit

// $linkToQiki = qikilinkWhole(kontext());
if (UserQiki::client()->may(UserQikiAccess::create, Comment::classname())) {
	?>
		<?php echo "\n"; if ($numAnswers == 0) { ?>
			<form class='contextform' action='<?php echo $FORMSUBMITURL; ?>' method='post'>
				<input type='hidden' name='action' value='newqontext' />
				<span id='whatshouldbe' class='kare1'>
					You think something should be here?&nbsp; 
					<?php
						if (count($comments) > 0) {
							$title="Welcome GREAT THINKER!&#10;Other minds believe like you, an answer should be here.  Speak your mind too.";
							// TODO: randomized alternatives
							echo "Cool, <span class='eyecatchy' title='$title'>you're not alone!</span>";
						} else {
							$title="Welcome PIONEER!&#10;Write your thoughts here for those who follow.";
							echo "Cool, <span class='eyecatchy' title='$title'>you're the first!</span>";
						}
					?>
				</span><span id='whatwouldlook' class='kare2'>
					So what would a
					<?php
						echo $qontext->htmlLink();
						// TODO:  appear simply boldface like a wiki article self-reference?   As specified at http://en.wikipedia.org/wiki/Help:Link#Wikilinks
						// TODO:  clicking on it allows you to change it?
						// TODO:  ... otherwise dismantle the form and action=newqontext etc.
						// echo htmlspecialchars(qontext()); 
					?>
					qiki
					look like?
				</span>
			</form>
		<?php echo "\n"; } ?>
		<form class='commentform' action='<?php echo $FORMSUBMITURL; ?>' method='post'>
			<input type='hidden' name='action' value='newcomment' />
			<input type='hidden' name='kontext' value='<?php echo htmlspecialchars(kontext()); ?>' />

			<textarea id='qomment' name='qomment'></textarea>
			<br />
			
			<input type=submit value='<?php 
				if ($numAnswers == 0) {
					echo "make a qiki wish"; 
				} else {
					echo "comment";
				}
			?>'>
		</form>

	<?php echo "\n";
} else {
	// echo "<p class='nocomments'>(The qiki {$qontext->htmlLink()} is not currently taking comments.)</p>\n";
}



if (UserQiki::client()->may(UserQikiAccess::create, Verb::classname())) {
	echo Verb::qoolbar(UserQiki::client()->id());
}



if (count($comments) > 0 || $totalcomments > 0) {
	echo "<div id='sectionComment'>\n";

	if (UserQiki::client()->may(UserQikiAccess::see, Comment::classname(), 'anon')) {
		$whichcomments = "Comments";
	} else {
		$whichcomments = "Comments by users";
	}
	echo "<span class='Comment-Heading'>$whichcomments:</span>\n";   // TODO: tell how many anonymous comments were removed from this view

	$spammy = new Scorer('spammy');
	foreach ($comments as $comment_id => $comment) {

		$spamscore = $spammy->score(array(
			Sentence::classname() => $comment->id(), 
			'client_id' => UserQiki::client()->id(),
		));
		
		if ($comment->whotype() != UserQiki::classname() && !UserQiki::client()->preference('anonymous')) continue;
		if ($spamscore > 0.0                             && !UserQiki::client()->preference('spam'     )) continue;
		
		if ($spamscore <= 0.0)                                    { $spamclass = 'spamclass-0';    }
		elseif           (0.0 < $spamscore 
							 && $spamscore < 1.0)                 { $spamclass = 'spamclass-half'; }
		else                              /* 1.0 <= $spamscore */ { $spamclass = 'spamclass-1';    }
		
		echo "<span "
			."class='noun-object selectable-noun $spamclass' "
			."data-object-class='" . Sentence::classname() . "' "
			."data-object-id='$comment_id' "
			."data-spamscore='$spamscore' "
		.">";
			echo $comment->htmlContent();  // was htmlspecialchars($comment->clause(Comment::classname())->info());
			echo "&nbsp; ";
			echo "<span class='sayswho'>";
				echo "(by ";
				echo $comment->whoshort();
				echo ", ";
				echo $comment->ago();
				echo ")";
			echo "</span>\n";
			$assocs   = Verb::associations(array(Sentence::classname() => $comment->id()));
			$assocsMe = Verb::associations(array(Sentence::classname() => $comment->id(), 'subject' => UserQiki::client()));
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
					if ($valueMe <= 0.0) {
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
						$verb = Verb::selectInfo($verbname);
						echo $verb->img(array('tooltip' => $tooltip, 'postsup' => $numberMe, 'postsub' => $numberTotal, 'class' => $classes));   
								
						// TODO:  encapsulate somehow, e.g. 'subject_id' => UserQiki::client()->id(), 'showValues' => TRUE));
					}
				}
			}
			if ($SHOW_VERBBAR) {
				verbexpander(Sentence::classname(), $comment->id());
			}
			
		echo "</span>";
		echo "<br />\n";
	}
	$numRowsNotShown = max(0, $totalcomments - $limitrows);
	if ($numRowsNotShown > 0) {
		echo "<p>... $numRowsNotShown older comments ...</p>\n";
	}
	echo "</div>\n";
}



if (SHOW_VERB_SUMMARY && UserQiki::client()->isSuper()) {
	echo "<div id='verb-summary'>\n";
		echo "<hr />\n";
		
		$verbsMe = Verb::associations(array('subject_class' => UserQiki::classname(), 'subject' => UserQiki::client()));
		$verbsEveryone = Verb::associations();
		$verbsUsedByOthers = array_diff_assoc($verbsEveryone, $verbsMe);   // aka verbs used by somebody other than me, that I'm not the only one who uses them
		$verbsUnusedByMe = array_diff_key($verbsEveryone, $verbsMe);

        if (!empty($verbsMe)) {
            echo "My verbs: ";
            echo Verb::showverbs($verbsMe, array('postsup' => $verbsMe, 'postsub' => $verbsUsedByOthers));
            echo "<br />\n";
        }
        
        if (!empty($verbsMe) && !empty($verbsUnusedByMe)) {
            echo "All verbs: ";
            echo Verb::showverbs($verbsEveryone, array('postsup' => $verbsMe, 'postsub' => $verbsUsedByOthers));
            echo "<br />\n";
        }
        
        if (!empty($verbsUnusedByMe)) {
            echo "Verbs I haven't used yet: ";
            echo Verb::showverbs($verbsUnusedByMe, array('postsub' => $verbsEveryone));
            echo "\n";
        }
        
		echo "<hr />\n";
	echo "</div>\n";
}



htmlfoot();



		function verbexpander($obj, $objid) {   // VERBBAR - still using?
			echo "\t<span class='verbcombo' data-obj='$obj' data-objid='$objid'>\n";
				$verbTool = Verb::selectInfo('tool');
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



function sleepingtool($qiki) {   // obsolete, from file-stored answers in HTML, an ajax-javascript-generated tool
	if (UserQiki::client()->may(UserQikiAccess::see, Comment::classname(), 'anon')) {
		$minlevel = 'anon';
	} else {
		$minlevel = 'user';
	}
	$limitrows = 10;
	$comments = Comment::byKontext($qiki, array(
		'limit' => $limitrows, 
		'totalrows' => &$totalrows, 
		'minlevel' => $minlevel,
		'client_id' => UserQiki::client()->id(),
	));

	$verbTool = Verb::selectInfo('tool');
	echo "\t<span class='nebox'>\n";
		echo "\t\t<div class='sleepingtool'>\n";
			echo "\t\t" . $verbTool->img(array('title' => 'see verbs', 'class' => 'toolwaker')) . "\n";
		echo "\t\t</div>\n";
		echo "\t\t<div class='sleepercaption'>\n";
			if ($totalrows != 0) {
				echo "\t\t$totalrows comments\n";
			} else {
				echo "\t\t(no comments yet)\n";
			}
		echo "\t\t</div>\n";
	echo "\t</span>\n";
}
