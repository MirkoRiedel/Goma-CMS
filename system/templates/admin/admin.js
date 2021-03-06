/**
 *@package goma framework
 *@link http://goma-cms.org
 *@license: http://www.gnu.org/licenses/gpl-3.0.html see 'license.txt'
 *@Copyright (C) 2009 - 2013  Goma-Team
 * last modified: 27.02.2013
 */

function updateNav() {
	// Width of the entire page
	var headerWidth = $("#header").width();
	// Width of the userbar right
	var userbarWidth = $("#userbar").outerWidth();
	// Width of all navigation nodes
	var naviWidth = $("#navi").outerWidth();
	// Maximum available space for the navigation
	var naviWidthMax = headerWidth - userbarWidth - 15;
	var curNode;

	if (naviWidth < naviWidthMax) {
		if ($("#navMore-sub li").length != 0) {
			curNode = $("#navi li.nav-inactive").first();
			while (naviWidthMax - naviWidth > curNode.outerWidth(true) && curNode.length != 0) {
				$("#navMore-sub li").first().remove();
				curNode.removeClass("nav-inactive");
				curNode = $("#navi li.nav-inactive").first();
			}
			if ($("#navMore-sub li").length == 0)
				$("#navMore").css("display", "none");
		}
	} else {
		while ($("#navi").outerWidth() > naviWidthMax) {
			curNode = $("#navi").find(" > ul > li").not($("#navMore")).not($("#navi li.active")).not($("#navi li.nav-inactive")).last();
			curNode.clone().prependTo("#navMore-sub");
			curNode.addClass("nav-inactive");
			$("#navMore").css("display", "block");
		}
	}
}


$(document).ready(function() {
	$("#userbar").addClass("userbar-js");
	$("#userbar").removeClass("userbar-no-js");

	$(window).resize(function() {
		updateNav();
	});

	updateNav();

	$("#navMore > a").click(function() {
		$(this).parent().toggleClass("open");
		$("#navMore-sub").stop().slideToggle("fast");
		return false;
	});
	
	var hideNavMore = function() {
		$("#navMore").removeClass("open");
		$("#navMore-sub").stop().slideUp("fast");
	}
	
	CallonDocumentClick(hideNavMore, [$("#navMore"), $("#navMore-sub")]);
	
	goma.ui.setMainContent($("#contnet > content_inner"));
})
