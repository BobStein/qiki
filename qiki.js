// Qiki JavaScript

$(function() {
	$(".commentform").submit(function() {
		$.ajax({
			type: $(this).attr('method'),
			url: $(this).attr('action'),
			data: $(this).serialize(),
			success: function(responseText) {
				if (jQuery.trim(responseText) == 'success') {
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
	$("#loginform form").submit(function() {   // intercept with ajax: return to current page, because form-action may be qiki404.php
		$.ajax({
			type: $(this).attr('method'),
			url: $(this).attr('action'),
			data: $(this).serialize(),
			success: function(responseText) {
				// TODO: not be so wasteful with big returned (nonsense http://qiki.info/qiki404.php) page that's trashed here? 
				// TODO: review all the special cases of logging in
				//       in modes anonymous allowed and not
				//       in pop up form or whole-page
				//       N.B. all these alternatives make User.php rickety.  Unit tests?  More like Wholeshebang tests?

				if (/class\=\'User-error\'/.test(responseText)) {  // TODO: more robust detection
					alert("Login failed");   // TODO: show the actual error message
					window.location.reload(true);
					
					
					//// The following is from http://stackoverflow.com/questions/2825586/inplace-replace-entire-html-document
					//// but it leads to several strange "Resend" popups, even on subsequent ajax requests, I guess because reload() is called in the else-clause
					// $('body').html(responseText);   // something went wrong, display whatever came back
					
					
					// Ideally what would happen here is to act as if the form-submit had never been intercepted
					
					
				} else {
					window.location.reload(true);   // reload in order to get back to the page you were on
				}
			},
			error: function(xhr, errorType, errorThrown) {
				alert("Login error " + errorType + ", " + errorThrown + ":\n\n" + xhr.responseText);
			},
		});
		return false;
	});
	$("#logoutlink").click(function() {
		$.ajax({
			type: 'head',   // so as not to be so wasteful with big returned page that must be trashed here (does that still run the PHP twice?)
			url: $(this).attr('href'),
			success: function(responseText) {
				window.location.reload(true);
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
	
	
	
	jQuery.fn.toolparts = function() {
		toolcombo = $(this).closest(".toolcombo");
		toolopen = toolcombo.find(".toolopen");
		toolbar = toolcombo.find(".toolbar");
	};
	jQuery.fn.settletools = function() {
		$(this).toolparts();
		if (toolbar.is(':visible')) {   // when this toolbar becomes visible...
			$(".toolbar").not(toolbar).hide();   // hide all other toolbars
			$(".toolopen").not(toolopen).fadetool();
			toolopen.darkentool();
			toolopen.attr('title', 'hide tools');
		} else {
			toolopen.fadetool();
			toolopen.attr('title', 'see tools');
		}
	};
	jQuery.fn.fadetool = function() {
		$(this).removeClass('tooldarken');   
		$(this).addClass('toolfade');
	};
	jQuery.fn.darkentool = function() {
		$(this).removeClass('toolfade');   
		$(this).addClass('tooldarken');
	};
	
	
	
	$(".toolopen").click(function() {
		$(this).toolparts();
		toolbar.toggle().settletools();
	});
	$(".toolbar").hide().settletools();
	
	$(".toolbar .tool-qiki").click(function() {
		$(this).toolparts();
		var $tool = $(this);
		$.post(FORMSUBMITURL, {
			'action': 'tool_associate',
			'toolname': $tool.data('tool'),
			'obj': toolcombo.data('obj'),
			'objid': toolcombo.data('objid'),
		}, function(responseText, textStatus, jqXHR) {
			if ($.trim(responseText) === 'success') {
				//alert($tool.data('tool'));
				window.location.reload();
			} else {
				alert('Unable to ' + $tool.data('tool') + '-associate a ' + toolcombo.data('obj') + ': ' + responseText);
			}
		}).fail(function(jqXHR, textStatus) {
			alert('Failed to ' + $tool.data('tool') + '-associate a ' + toolcombo.data('obj') + ': ' + textStatus + ', status ' + jqXHR.status);
		});
	});
});