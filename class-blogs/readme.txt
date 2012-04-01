=== Class Blogs ===
Contributors: oberlincilc
Tags: classblogs, class, blogging, academic, students, professor, teacher, course
Requires at least: 3.0
Tested up to: 3.3.1
Stable tag: 0.3

Makes it easy to use blogs in your teaching.

== Description ==

The Class Blogs plugin is meant to simplify the process of blogging as a class.
It accomplishes this by making it easier to find, organize and analyze the
work created by your students.  The functions available to you to make this
happen are provided and categorized below.

Widgets
-------

If you are running WordPress in multisite mode, these widgets are only available
on the main blog.  If you are unsure about what this means, you don't need to
worry about it.  The available widgets allow you to display the following:

* Recent student posts.
* Recent student comments.
* A tag cloud built from the tags used on your students' posts.
* YouTube videos embedded in your student's posts.
* An image randomly taken from your students' posts.
* Links to view posts by each of your students.

Teacher Admin Pages
-------------------

The Class Blogs plugin adds pages that will be visible only to you whenever you
log in to the administrative side of the blog.  These pages allow you to perform
the following actions:

* View a table of all student posts, sorted by date.
* View a table of all student comments, sorted by date.
* View word counts for all student posts and comments, broken down by week.
* Add links to all student blogs if running in multisite mode.
* Optionally disable comments on all current and future posts.

Student Admin Pages
-------------------

The Class Blogs plugin adds pages that will be visible to each student whenever
they log in to create content.  These pages allow students to do the following:

* Create a pseudonym by changing their username and full name.
* See how many words they have written for the current week.

Other
-----

In addition to the above plugins, the Class Blogs plugin provides a few
additional features that do the following:

* Add a link to create a gravatar to the bottom of each user-activation email.
* Set a student's first and last name based on their email address.
* Automatically approve all comments left by students on other students' blogs.

== Installation ==

1. Place the `class-blogs` folder in your `/wp-content/plugins/` directory.
2. Activate Class Blogs.
3. Click on the items in the 'Class Blogs' item in the admin menu to set options.
4. Optionally enable the 'Class Blogging' theme on the 'Appearance -> Themes' page.
5. Optionally add class-blogging widgets on the 'Appearance -> Widgets' page.

== Frequently Asked Questions ==

= Can I use this if I'm not running WordPress in multisite mode? =

While this plugin works best when running WordPress in multisite mode and giving
each student their own blog, it will run just fine if you have a single blog and
are adding students as users with limited permissions.

== Changelog ==

= 0.3 =
* Cleaned up development / production media structure.
* Plugins can now be selectively disabled from the admin page.
* Plugins can now run arbitrary code when upgrading the main plugin.
* Better dependency and loading management.
* Plugins can now define options on a per-blog basis.
* Word counter now properly counts words if there are comments but no posts.
* Student blog links widget now handles different layouts better.
* More areas of the theme are links now.
* Plugins that don't define media no longer break.
* Deferred plugin initialization.
* The YouTube class playlist now only maintains a local playlist.
* Added an explicit license.

= 0.2 =
* Added plugin admin media system.
* Better student name detection in blog list.
* Added plugin table schema abstraction.
* Better sitewide data tracking with manual resyncing capability.
* Plugin table schema cleanup.
* YouTube class playlist now better handles quota and request errors.
* Better plugin admin notification messages.
* Refactored student blog links JavaScript.
* Improved documentation for all plugins.
* Simplified caching logic.
* Better internal widget system.
* Abstracted the private plugin page functionality.
* Added icons for all plugin admin pages.
* Added a build system for packaging media and creating translation files.

= 0.1 =
* Initial release
