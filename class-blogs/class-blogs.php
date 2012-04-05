<?php

/*
Plugin Name: Class Blogging
Plugin URI:  http://languages.oberlin.edu/cilc/projects/class-blogs/
Description: A suite of plugins that make it easier to use WordPress for class blogging.
Author:      Oberlin College's Cooper International Learning Center
Author URI:  http://languages.oberlin.edu/
License:     BSD New
Network:     true
Version:     0.5
Text Domain: classblogs
Domain Path: /languages/
*/

/*
Copyright (c) 2012, Oberlin College
All rights reserved.

Redistribution and use in source and binary forms, with or without
modification, are permitted provided that the following conditions are met:

    1. Redistributions of source code must retain the above copyright
       notice, this list of conditions and the following disclaimer.

    2. Redistributions in binary form must reproduce the above copyright
       notice, this list of conditions and the following disclaimer in the
       documentation and/or other materials provided with the distribution.

    3. Neither the name of Oberlin College nor the names of its contributors
       may be used to endorse or promote products derived from this software
       without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
DISCLAIMED. IN NO EVENT SHALL OBERLIN COLLEGE BE LIABLE FOR ANY
DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
(INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
(INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

*/

// Define the paths to the base plugin file in the class blogs suite
define( 'CLASS_BLOGS_FILE_REL', 'class-blogs/class-blogs.php' );
define( 'CLASS_BLOGS_FILE_ABS', WP_PLUGIN_DIR . '/' . CLASS_BLOGS_FILE_REL );
define( 'CLASS_BLOGS_DIR_ABS', dirname( CLASS_BLOGS_FILE_ABS ) );
define( 'CLASS_BLOGS_DIR_REL', dirname( CLASS_BLOGS_FILE_REL ) );

// Require the core class-blogs class and use it to load all required files, if
// the current WordPress instance is running in multisite mode
require_once( CLASS_BLOGS_DIR_ABS . '/ClassBlogs/ClassBlogs.php' );
ClassBlogs::initialize();
ClassBlogs::maybe_upgrade();

// Handle plugin activation and deactivation
function _classblogs_activate() { ClassBlogs::activate_suite(); }
function _classblogs_deactivate() { ClassBlogs::deactivate_suite(); }
register_activation_hook( CLASS_BLOGS_FILE_REL, '_classblogs_activate' );
register_deactivation_hook( CLASS_BLOGS_FILE_REL, '_classblogs_deactivate' );

?>
