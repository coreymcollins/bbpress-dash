/**
 * Disable the Preview button until a Forum is selected.
 *
 * @author Corey M Collins
 */
window.topicPreviewStopper = {};
( function( window, $, app ) {

	// Constructor
	app.init = function() {
		app.cache();

		if ( app.meetsRequirements() ) {
			app.bindEvents();
		}
	};

	// Cache all the things
	app.cache = function() {
		app.$c = {
			window: $(window),
			forumSelector: $( '#parent_id' ),
			previewButton: $( '#post-preview' ),
			contentEditor: $( '.wp-editor-area' )
		};
	};

	// Combine all events
	app.bindEvents = function() {
		app.$c.forumSelector.on( 'change', app.maybeEnablePreviewButton );
		app.$c.contentEditor.on( 'keyup', app.maybeEnablePreviewButton );
		app.$c.window.on( 'load', app.disablePreviewButton );
	};

	// Do we meet the requirements?
	app.meetsRequirements = function() {
		return app.$c.forumSelector.length;
	};

	// Maybe enable the preview button if a forum is selected.
	app.maybeEnablePreviewButton = function() {

		// If no value, disable the button.
		if ( ! app.$c.forumSelector.val() ) {
			app.disablePreviewButton();
		} else {
			app.$c.previewButton.removeClass( 'disabled' );
		}
	};

	// Disable the preview button on load.
	app.disablePreviewButton = function() {

		if ( ! app.$c.forumSelector.val() ) {
			app.$c.previewButton.addClass( 'disabled' );
		}
	};

	// Engage
	$( app.init );

}( window, jQuery, window.topicPreviewStopper ) );
