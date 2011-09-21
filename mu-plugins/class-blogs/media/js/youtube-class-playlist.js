(function($) {

/**
 * A delayed YouTube video loader that is associated with a thumbnail image of
 * a video.  When the thumbnail is clicked on, it is transformed into a playable
 * embedded YouTube video.
 *
 * This takes a DOM element that contains the video's thumbnail as its argument.
 */
var DelayedYouTubeLoader = function(thumbnail) {

	// Cause clicking on the thumbnail to turn it into a playable video
	$(thumbnail).bind('click.playlist', this.enableOnClick);
};

/** Initialize loaders for every thumbnail on the page. */
DelayedYouTubeLoader.initializeLoaders = function() {
	var loaders = [];
	$(DelayedYouTubeLoader.CSS.thumbnails).each(function(i, thumbnail) {
		loaders.push(new DelayedYouTubeLoader(thumbnail));
	});
};

/** Gets the YouTube ID of a video from the ID of a video wrapper element. */
DelayedYouTubeLoader.getVideoIDFromElementID = function(id) {
	return id.replace(DelayedYouTubeLoader.VIDEO_ID_PREFIX, "");
};

/**
 * The prefix of the YouTube video ID contained in the `id` attribute of the
 * video wrapper element.
 */
DelayedYouTubeLoader.VIDEO_ID_PREFIX = "video__";

/** CSS selectors for the loader. */
DelayedYouTubeLoader.CSS = {
	'thumbnails': ".cb-youtube-local-playlist-page-video-thumbnail"
};

/** The base URL for the embed iframe's source URL. */
DelayedYouTubeLoader.EMBED_URL_BASE = "http://www.youtube.com/embed/";

/** The default width of an embedded iframe. */
DelayedYouTubeLoader.IFRAME_WIDTH = 560;

/** The default height of an embedded iframe. */
DelayedYouTubeLoader.IFRAME_HEIGHT = 349;

/**
 * Transform the thumbnail into an iframe when clicked on.
 *
 * This takes advantage of the fact that the YouTube video's ID is stored in
 * the ID attribute of the DOM element that uses this as a click listener.
 */
DelayedYouTubeLoader.prototype.enableOnClick = function(e) {

	e.preventDefault();

	// Inject the iframe
	var $thumbnail = $(this);
	iframe = '<iframe width="' + DelayedYouTubeLoader.IFRAME_WIDTH + '" height="';
	iframe += DelayedYouTubeLoader.IFRAME_HEIGHT + '" src="';
	iframe += DelayedYouTubeLoader.EMBED_URL_BASE + DelayedYouTubeLoader.getVideoIDFromElementID($thumbnail.attr('id')) + '"';
	iframe += ' frameborder="0" allowfullscreen></iframe>';
	$thumbnail.html(iframe);

	// Disable any further click listening on the thumbnail
	$thumbnail.unbind('click.playlist');
};

$(document).ready(function() {

	// Create the delayed loaders
	DelayedYouTubeLoader.initializeLoaders();
});

})(jQuery);
