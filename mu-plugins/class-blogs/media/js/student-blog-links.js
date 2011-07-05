(function($) {

var css = {
	'addLink':     "#add-student-blog-link",
	'allLinks':    "#student-blog-links",
	'deleteLinks': ".delete-link",
	'links':       ".link"
};

// Add another link field to the list
var addLink = function(e) {

	e.preventDefault();

	// Clone the last link field row
	var $links = $(css.allLinks);
	var $newLink = $links.find(css.links + ":visible:last").clone();
	$links.append($newLink);
	$newLink.find("input").val('');

	// Update the new field row's IDs
	var newID = $links.find(css.links).length - 1;
	var updateFields = {
		'label': ['for'],
		'input': ['name', 'id']
	};
	var fieldName, i, $el, attr;
	for (fieldName in updateFields) {
		if (updateFields.hasOwnProperty(fieldName)) {
			$.each($newLink.find(fieldName), function(i, el) {
				for (i=0; i<updateFields[fieldName].length; i++) {
					$el = $(el);
					attr = $el.attr(updateFields[fieldName][i]);
					$el.attr(updateFields[fieldName][i], attr.replace(/\d+$/, newID));
				}
			});
		}
	};
};

// Delete a link
var deleteLink = function(e) {

	e.preventDefault();

	// If we're clicking on a delete link, clear the text and hide the field row
	var $target = $(e.target);
	if ($target.is(css.deleteLinks)) {
		var $link = $target.parents(css.links);
		$link.find("input").val('');
		$link.hide();
	}
};

// Make clicking on the "add another link" link add a link
$(document).ready(function() {
	$(css.allLinks).click(deleteLink);
	$(css.addLink).click(addLink);
});

})(jQuery);
