
WordPress Class Blogs
=====================

Multiple plugins and a theme wrapped packaged as a single plugin that make it
easier to manage class blogs created using WordPress.  The plugin can work when
running WordPress in multisite mode, where the professor controls the main blog
and each student is given a dedicated blog, or when running normally, where the
professor is the administrator on the blog and students are reduced-privilege users.

Requirements
============

* PHP >= 5.2.0
* WordPress >= 3.0

Installation
============

Copy the entire `class-blogs` folder to the `wp-content/plugins` directory of a
valid WordPress installation, then go to the 'Plugins' administration page to
activate it.

Plugins
=======

The class blogs plugin is a wrapper around many different plugins, which are
as follows:

Classmate Comments
------------------
Automatically approves any comment left by a logged-in student on another
student's blog.

Disable Comments
----------------
Provides an admin option to disable commenting on current and future posts.

Gravatar Signup
---------------
Adds a link for the student to sign up for a gravatar to each account activation
email sent out.

New User Configuration
----------------------
Creates a first and last name for a newly added user based on their email address.

Random Image
------------
Provides a widget that displays a randomly selected image chosen from all the
images used in all student and professor posts.

Student Comments
----------------
Provides a widget that shows recent comments left on all student posts, a
professor-only admin page showing a table of all student comments, and a
student-only admin page showing a table of all comments that they have left.

Student Posts
-------------
Provides a widget that shows recent student posts, offers the ability to
override the main blog's posts with all recent student posts when running in
multisite mode, and a professor-only admin page showing a table of all student
posts that have been published.

Student Tags
------------
Provides a widget showing a tag cloud built from the tags used in all student
and professor posts, and, when running in multisite mode, allows usage of a tag
to be viewed across all student blogs.

Student Blog Links
------------------
When running in multisite mode, provides an admin option that allows an
unlimited number of arbitrary links to be added to the first widgetized area
on every student blog.

Student List
------------
Provides a widget that shows a list of all students, with each student name
linking to a page to show all of their posts.

Student Pseudonym
-----------------
When running in multisite mode, adds a page to the Users group on the admin side
of any student blog that allows them to quickly change their username, blog URL
and display name.

Word Counter
------------
Adds a professor-only admin page that allows them to view student word counts by
week, and adds a dashboard widget to each student blog that shows how may words
they have written for the current and previous weeks.

YouTube Class Playlist
----------------------
Maintains a list of all embedded YouTube videos, which can be shown via a widget
or a deciated page.

Themes
======

The plugin also provides a custom theme that takes advantage of the student data
aggregation and displays all recent students posts on the front page of the blog,
grouped by student.  When the plugin is active, the 'Class Blogging' theme will
show up in the list of themes that can be activated.
