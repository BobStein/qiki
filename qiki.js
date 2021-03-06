// Qiki JavaScript


window.CHROMESTUFF =   // What the Qoolopener can close
	 "#sectionComment,"
	+".commentform,"
	+".contextform,"
	+"#verb-summary,"
	+".footer-logo,"
	+".necorner";

$(function() {
	$(".commentform").submit(function() {
		$.ajax({
			type: $(this).attr('method'),
			url: $(this).attr('action'),
			data: $(this).serialize(),
			success: function(responseText) {
				if ($.trim(responseText) == 'success') {
					// TODO prepend the new comment
					window.location.reload(true);
				} else {
					alert(responseText);
				}
			},
			error: function(xhr, errorType, errorThrown) {
				alert("Comment error " + errorType + ", " + errorThrown + ":\n\n" + xhr.responseText);
			},
		});
		return false;
	});
	$("#loginform form").submit(function() {
		handleform($(this), 'Login');
		return false;
	});
	$("#signupform form").submit(function() {
		handleform($(this), 'Signup', function() { 
			alert('Thanks for signing up, now you may log in.');
		});
		return false;
	});
	function handleform($form, kind, success_handler) {   // intercept with ajax: return to current page, because form-action may be qiki404.php
		$.ajax({
			type: $form.attr('method'),
			url: $form.attr('action'),
			data: $form.serialize(),
            dataType: 'html',
			success: function(responseText) {
                if (kind == 'Login'  && $.trim(responseText) == 'loggedin'
                 || kind == 'Signup' && $.trim(responseText) == 'signedup') {
                    if (success_handler != null) {
                        success_handler();
                    } else {
                        window.location.reload(true);
                    }
                } else {
                    // alert(kind + " form reports: '" + responseText + "'");
                    var estart = responseText.indexOf(User_ERROR_START);
                    var eend   = responseText.indexOf(User_ERROR_END);
                    if (estart == -1 || eend == -1) {   // TODO: a more positive confirmation, than absence of error message
                        alert("Missing error message: '" + responseText + "'");
                        window.location.reload(true);   // to get back to the page you were on
                    } else {
                        var message = responseText.substring(estart + User_ERROR_START.length, eend);
                        alert(kind + " error: " + message); 
                    }
                }
				// TODO: not be so wasteful with big returned (nonsense http://qiki.info/qiki404.php) page that's trashed here? 
				// TODO: review all the special cases of logging in
				//       in modes anonymous allowed and not
				//       in pop up form or whole-page
				//       N.B. all these alternatives make User.php rickety.  Unit tests?  More like Wholeshebang tests?

                                // if (/class\=\'User-error\'/.test(responseText)) {  // DONE: more robust detection


			},
			error: function(xhr, errorType, errorThrown) {
				alert(kind + " failed " + errorType + ", " + errorThrown + ":\n\n" + xhr.responseText);
			},
		});
	}
	$("#logoutlink").click(function() {
		$.ajax({
			type: 'GET',   // so as not to be so wasteful with big returned page that must be trashed here (does that still run the PHP twice?)  But has no affect, duh, PHP doesn't distinguish GET from HEAD.  Oh but it could...
			url: $(this).attr('href'),
            dataType: 'html',
			success: function(responseText) {
                if ($.trim(responseText) == 'loggedout') {
                    window.location.reload(true);
                } else {
                    alert("Loggout resulted in '" + responseText + "'");
                }
				// window.location.reload(true);
			},
			error: function(xhr, errorType, errorThrown) {
				alert("Logout error " + errorType + ", " + errorThrown + ":\n\n" + xhr.responseText);
			},
		});
		return false;
	});
	$("#loginlink").click(function() {
		if ($("#loginform").is(':visible')
		 || $("#signupform").is(':visible')) {
			$("#loginform").hide();
			$("#signupform").hide();
		} else {
			$("#loginform").show();
			$("#signupform").hide();
		}
		return false;
	});
	$("#orsignup").click(function() {
		$("#loginform").hide();
		$("#signupform").show();
		return false;
	});
	$("#orlogin").click(function() {
		$("#signupform").hide();
		$("#loginform").show();
		return false;
	});
	
	
	
	
	
			// VERBBAR - still using?
			// -------
			jQuery.fn.verbparts = function() {
				verbcombo = $(this).closest(".verbcombo");
				verbopen = verbcombo.find(".verbopen");
				verbbar = verbcombo.find(".verbbar");
			};
			jQuery.fn.settleverbs = function() {
				$(this).verbparts();
				if (verbbar.is(':visible')) {   // when this verbbar becomes visible...
					$(".verbbar").not(verbbar).hide();   // hide all other verbbars
					$(".verbopen").not(verbopen).fadeverb();
					verbopen.darkenverb();
					verbopen.attr('title', 'hide verbs');
				} else {
					verbopen.fadeverb();
					verbopen.attr('title', 'see verbs');
				}
			};
			jQuery.fn.fadeverb = function() {
				$(this).removeClass('verbdarken');   
				$(this).addClass('verbfade');
			};
			jQuery.fn.darkenverb = function() {
				$(this).removeClass('verbfade');   
				$(this).addClass('verbdarken');
			};
			
			$(".verbopen").click(function() {
				$(this).verbparts();
				verbbar.toggle().settleverbs();
			});
			$(".verbbar").hide().settleverbs();
			
			$(".verbbar .verb-qiki").click(function() {
				$(this).verbparts();
				var $verb = $(this);
				verb_state($verb.data('verb'), verbcombo.data('obj'), verbcombo.data('objid'), 1, 'delta');
			});
	
	
	
	
	function verb_state(verb, objclass, objid, value, op, whendone) {   // insert/set/delta a sentence where the user (id or IP address) is the implied subject
		$.post(FORMSUBMITURL, {
			'action': 'verb_state',
			'verbname': verb,
			'objclass': objclass,
			'objid': objid,
			'value': value,
			'op': op,
		}, function(responseText, textStatus, jqXHR) {
			if ($.trim(responseText) === 'success') {
				if (whendone == null) {
					window.location.reload();   // TODO: instead update object model, for pure AJAX without refresh
				} else {
					whendone();
				}
			} else {
				alert('Unable to "' + verb + '-' + op + '" a ' + objclass + ': ' + responseText);
			}
		}).fail(function(jqXHR, textStatus, errorThrown) {
			alert('Failed to "' + verb + '-' + op + '" a ' + objclass + ': ' + textStatus + ', status ' + jqXHR.status + ', response ' + delta);
		});
	}
	
	// function uset(verb, oclass, oid, setting) {   // DONE:  MMM with verb_state()
		// $.post(FORMSUBMITURL, {
			// 'action': 'verb_set',
			// 'verbname': verb,
			// 'objclass': oclass,
			// 'objid': oid,
			// 'setting': setting,
		// }, function(responseText, textStatus, jqXHR) {
			// if ($.trim(responseText) === 'success') {
				// window.location.reload();   // TODO: instead update object model, for pure AJAX without refresh
			// } else {
				// alert('Unable to "' + verb + '-set" a ' + oclass + ': ' + responseText);
			// }
		// }).fail(function(jqXHR, textStatus, errorThrown) {
			// alert('Failed to "' + verb + '-set" a ' + oclass + ': ' + textStatus + ', status ' + jqXHR.status + ', response ' + setting);
		// });
	// }
	
	
	
	// Qoolbar
	
	 $.ui && $(".qoolbar .qool")	
		.draggable({   // qoolbar - to - noun:  ADD RATING
			helper: "clone", 
			cursor: "-moz-grabbing",   // -moz-grabbing works FF 12-22, maybe used to work Chrome 28   
			// TODO: make Chrome,IE,etc work (client sniffing??)   
			// TODO: hover-hint hand   (grab aka -webkit-grab aka -moz-grab)
			// TODO: abandon jQuery-UI??
			scroll: false,
			start: function() {
				associationInProgress();
			},
			stop: function() {
				associationResolved();
			},
		})
	;
	$(".qoolbar").addClass('fadeUntilHover');
	$.ui && $(".noun-object")
		.droppable({
			accept: ".qoolbar .qool",
			hoverClass: 'drop-hover',
			drop: function(event, ui) {
				$source = ui.draggable;
				$dest = $(event.target);
				verb = $source.data('verb');
				oclass = $dest.data('object-class')
				oid = $dest.data('object-id');
				sclass = $source.closest('.noun-object').data('object-class');
				sid = $source.closest('.noun-object').data('object-id');
				if (sclass != oclass || sid != oid) {
					verb_state(verb, oclass, oid, 1, 'delta');
				}
			},
		})
	;
	
	// Unverbing a comment
	
	$(".mezero")
		// .draggable('disable') // Error: cannot call methods on draggable prior to initialization; attempted to call method 'disable'
		.on('dragstart', function(event) { event.preventDefault(); })
	;
	
	dragoptions = {
		appendTo: 'body',
		cursor: "-moz-grabbing",   // -moz-grabbing works FF 12-22, maybe used to work Chrome 28   TODO: make Chrome,IE,etc work (client sniffing??)   TODO: hover-hint hand   TODO: abandon jQuery-UI??
		scroll: false,
		start: function(event, ui) {
			associationInProgress();
		},
		stop: function(event, ui) {
			associationResolved();
			$source = $(event.target).closest('.verb-qiki');
			verb = $source.data('verb');
			oclass = $source.closest('.noun-object').data('object-class');
			oid = $source.closest('.noun-object').data('object-id');
			verb_state(verb, oclass, oid, -1, 'delta');
		},
	};
	$.ui && $(".melast").draggable(dragoptions);   // noun - to - oblivion:  SUBTRACT RATING
	
	
	// window.hackvirgin = true;
	// $.extend(dragoptions, {
		// helper: function(event, wtf_nothingwecanuse) {
	$.extend(dragoptions, {
		               helper: 'clone',
		bustedassbrokenhelper: function(event, wtf_nothingwecanuse) {   // http://bugs.jqueryui.com/ticket/9461
			$source = $(this);
			// if (window.hackvirgin) {
				// window.hackvirgin = false;
				// $.getScript("http://visibone.com/javascript/utils.js", function(d,t,j) {
					//// alert(objectDissect(firstParameterOfHelperFunction));
					//// Output was:  object altKey(b) bubbles(b) button(n) buttons(u) cancelable(b) clientX(n) clientY(n) ctrlKey(b) currentTarget(o) data(o) 
					////              delegateTarget(o) eventPhase(n) fromElement(u) handleObj(o) isDefaultPrevented(f) isImmediatePropagationStopped(f) 
					////              isPropagationStopped(f) jQuery1102030696228839680484(b) metaKey(b) offsetX(u) offsetY(u) originalEvent(o) pageX(n) 
					////              pageY(n) preventDefault(f) relatedTarget(o) result(b) screenX(n) screenY(n) shiftKey(b) stopImmediatePropagation(f) 
					////              stopPropagation(f) target(o) timeStamp(n) toElement(u) type(s) view(o) which(n)
					//// So the first parameter is the effing event
				// });
				//// alert('class ' + event.target.className + ' tag ' + event.target.tagName + ' parent ' + event.target.parentNode.tagName)
				//// Output was:  "class  tag IMG parent SPAN"
				//// Conclusion:  event.target is the img originally clicked on, not the span.menozero we're conceptually dragging
				//alert(typeof($source.data('postsup')) + ' ' + typeof($source.data('postsub')));   // number or undefined
			// }
			//if ($source.data('postsup') === '1' && $source.data('postsub') === undefined) {   // return of undefined documented: http://api.jquery.com/data/
			if ($source.data('postsup') == 1 && $source.data('postsub') == undefined) {   // return of undefined documented: http://api.jquery.com/data/
				return $source;   // the last man standing is not cloned, he's just dragged away
			} else {
				var imageWithoutTheNumbers = $source.find('img');
				if (imageWithoutTheNumbers.length != 1) {
					alert('DANGER, NO IMG in ' + $source[0].tagName + ' ' + $source[0].className);
				}
				return imageWithoutTheNumbers.clone();   // 
			}
		}
	});
	$.ui && $(".menozero img").draggable(dragoptions);   // noun - to - oblivion:  SUBTRACT RATING

	// for mouseheld event see http://stackoverflow.com/a/4081293/673991 and http://jsfiddle.net/gnarf/pZ6BM/
	
	
	// Preference
	
	$('#showanon').change(function() {
		var setting = $(this).is(':checked') ? '1' : '0';
		verb_state('prefer', 'Preference', 1 /* new Preference('anonymous').id() */, setting, 'stow');
	});
	$('#showspam').change(function() {
		var setting = $(this).is(':checked') ? '1' : '0';
		verb_state('prefer', 'Preference', 2 /* new Preference('spam').id() */, setting, 'stow');
	});
	
	// Selection
	
    $('.selectable-noun a').click(function(event) {   // first of all, hyperlinks inside the selectable do NOT cause a selection event
        event.stopPropagation();   // (notice how we're not prevent[ing the ]Default behaviour of hyperlinks)
    });
	$('.selectable-noun').click(function(event) {
		event.shiftKey;
		event.ctrlKey;
		event.metaKey;
		if (event.ctrlKey || event.metaKey) {
			$(this).toggleClass('selected');
		} else if (event.shiftKey) {
			// TODO: range select
		} else {
			if ($(this).is('.selected') && $('.selected').length == 1) {
				unselection();
			} else {
				unselection();
				$(this).addClass('selected');
			}
		}
		selectionWrappup();
		event.stopPropagation();
	});
	$('html').click(function(event) {
		if (event.ctrlKey || event.metaKey) {
		} else if (event.shiftKey) {
		} else {
			unselection();
		}
		selectionWrappup();
	});
	associationResolved();
	$('.qoolbar .qool').click(function() {
		if ($(this).closest('.qoolbar').is('.raiseVerbs')) {
			verb = $(this).data('verb');
			oclasses = [];
			oids = [];
			$('.selected').each(function() {
				oclasses.push($(this).data('object-class'));
				oids.push($(this).data('object-id'));
			});
			verb_state(verb, oclasses, oids, 1, 'delta');
		} else {
			// Mean something else?  Elaborate on tool?
		}
	});
	
	// tool hider
	window.qoolopen = true;   // TODO: retrieve from PHP via htmlhead() inline javascript
	$('.qoolbar .verb-tool').click(function() {
		$sectionComment = $('#sectionComment');
		oldqo = window.qoolopen;
		window.qoolopen = !window.qoolopen ;
		newqo = window.qoolopen;
		if (newqo) {
			$(window.CHROMESTUFF).show(333);
		} else {
			$(window.CHROMESTUFF).hide(333);
		}
		verb_state('prefer', 'Preference', 3 /* Preference::factory('qoolopen')->id() */, newqo ? 1 : 0, 'stow', function(){ /* avoid reload */ });
	});
});

function associationResolved() {   // indicating normalcy
	$(document.body).css('background-color', '#F8F8F8');   // TODO: make a class instead
}

function associationInProgress() {   // indicating either (1) nouns are selected, or (2) a verb is dragging
	$(document.body).css('background-color', '#F0F0F0');
}
function unselection() {
	$('.selectable-noun').removeClass('selected');
}
function selectionWrappup() {
	numSelected = $('.selected').length;
	if (numSelected == 0) {
		associationResolved();
		$(".qoolbar").addClass('fadeUntilHover');
		$(".qoolbar").removeClass('raiseVerbs');
	} else {
		associationInProgress();
		$(".qoolbar").removeClass('fadeUntilHover');
		$(".qoolbar").addClass('raiseVerbs');
	}
}
