$(function () {
	$('h2 a, h1 a, #menu a, .content-item img, #lastfm-feed a, ol.commentlist li div.reply a, #submit').hover(function() {
		$(this).fadeTo("fast", 0.7);
	}, function() {
		$(this).fadeTo("fast", 1);
	});
});