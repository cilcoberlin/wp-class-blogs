
WordPress Class Blogs
=====================

A collection of plugins and themes that can be used, along with WordPress 3's
multisite functionality, to create a blog for a class in WordPress.  The main
blog is intended to be controlled by the professor, and each student is then
given full administrative privileges over a child blog in the network.

Requirements
============

* PHP >= 5.2.0
* WordPress >= 3.0 running in multisite mode

For this plugin suite to function as intended, you must give the class its
own WordPress multisite instance, where the professor has admin rights on the
main blog, and each student in the class has a single child blog created for
them, on which they are the only user with administrative rights.  A configuration
that differs from this might function, but it is not supported.

Installation
============

To use the class blogs suite, copy the contents of the `mu-plugins` directory
to the `mu-plugins` directory in your WordPress multisite installation's content
directory, which will normally be `wp-content`.  If you do not have an
`mu-plugins` directory in your content directory, you can create one first or
simply copy the entire `mu-plugins` directory in this repo to the content directory.

To use the included themes, copy the contents of the `themes` directory to
the `themes` directory of your WordPress content directory.

Plugins
=======

The class blogs suite consists of many different plugins wrapped in a single
MU plugin.  The plugins and the functionality that they provide are as follows.

Classmate Comments
------------------
Automatically approves any comment left by a logged-in student on another
student's blog.

Disable Comments
----------------
Provides an admin option to disable commenting on all blogs used by this class.

Gravatar Signup
---------------
Adds a link for the user to sign up for a gravatar to each account activation
 email sent out.

New User Configuration
----------------------
Creates a first and last name for a newly added user based on their email address.

Random Image
------------
Provides a main-blog-only widget that displays a randomly selected image chosen
from all the images used on all blogs that are part of this class.

Sitewide Comments
-----------------
Provides a main-blog-only widget that shows recent comments left on all student
blogs, as well as a professor-only admin page showing a table of all student
comments and a student-only admin page showing a table of all comments that
they have left.

Sitewide Posts
--------------
Provides a main-blog-only widget that shows recent posts made on all student
 blogs and allows for displaying all recent sitewide posts on the main blog.

Sitewide Tags
-------------
Provides a main-blog-only widget sitewide tag cloud widget, and allows all usages
of a single tag on all student blogs to be viewed.

Student Blog Links
------------------
Provides an admin option that allows you to add links of your choosing as the
first sidebar widget on all student blogs.

Student Blog List
-----------------
Provides a main-blog-only widget that shows a list of all student blogs that
are part of this class.

Student Pseudonym
-----------------
Adds a page to the Users group on the admin side of any student blog that allows
them to quickly change their username, blog URL and display name.

Word Counter
------------
Adds a page for the professor on the admin side to view student word counts by
week, and adds a dashboard widget to each student blog that shows the word counts
for the current and previous weeks.  Word counts are drawn from any posts or
comments that students have made.

YouTube Class Playlist
----------------------
Allows you to link a YouTube playlist with this blog that is automatically
updated whenever students embed YouTube videos in a post.

Themes
======

The class blogs suite provides the **Bentham** theme that takes advantage
of the class blogs plugin suite to display data about all students posts on the
front page of the blog. As long as the plugins are installed and sitewide
aggregator is functioning, the front page will show a selection of recent
student posts grouped by student, with the column of the student who has made
the most recent post displayed first, with every successive column to the right
being the column of a student whose posts are older than the one to the left.
