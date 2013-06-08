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
				alert("Login error " + errorType + ", " + errorThrown + ":\n\n" + xhr.responseText);
			},
		});
		return false;
	});
	$("#loginform form").submit(function() {
		$.ajax({
			type: $(this).attr('method'),
			url: $(this).attr('action'),
			data: $(this).serialize(),
			success: function(responseText) {
				// TODO: not be so wasteful with big returned (nonsense) page that's trashed here?
				window.location.reload(true);
			},
			error: function(xhr, errorType, errorThrown) {
				alert("Login error " + errorType + ", " + errorThrown + ":\n\n" + xhr.responseText);
			},
		});
		return false;
	});
	$("#logoutlink").click(function() {
		$.ajax({
			type: 'get',
			url: $(this).attr('href'),
			success: function(responseText) {
				// TODO: not be so wasteful with big returned (nonsense) page that's trashed here?
				window.location.reload(true);
			},
			error: function(xhr, errorType, errorThrown) {
				alert("Login error " + errorType + ", " + errorThrown + ":\n\n" + xhr.responseText);
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
});