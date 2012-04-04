#!/usr/bin/env ruby

=begin
Builds a local SVN repository needed to update the WordPress.org listing from
the local git working copy.  This does not automatically update the remote
repository, as certain non-SVN files might need to be added before updating,
such as the banner image used by the WordPress.org plugin directory.
=end

require 'fileutils'

WP_REPO = "http://plugins.svn.wordpress.org/class-blogs/"
SVN_DIR = "svn"
SVN_SRC_DIR = "class-blogs"
GIT_SRC_DIR = "wp-class-blogs"
PLUGIN_SRC_DIR = "class-blogs"
base_dir = `pwd`.strip

# Tagged git releases that should not be mirrored into SVN due to not actually
# being structured for release as a WordPress plugin
EXCLUDE_TAGS = [
  "v0.1.0",
  "v0.2.0",
  "v0.3.0"
]

# Operate out of the SVN directory
FileUtils.rm_rf(SVN_DIR)
FileUtils.mkdir(SVN_DIR)
Dir.chdir(SVN_DIR) do

  # Check out a clean version of the remote SVN repository
  puts "Checking out WordPress plugin SVN repo..."
  `rm -Rf #{SVN_SRC_DIR}`
  `svn co #{WP_REPO}`

  # Clone the local git repository into the SVN directory
  puts "Cloning local git repository..."
  `rm -Rf #{GIT_SRC_DIR}`
  `git clone file:////#{base_dir} #{GIT_SRC_DIR}`

  # Copy over the core plugin of the git master to the SVN trunk
  puts "Mirroring git head into SVN trunk..."
  plugin_files = File.join(GIT_SRC_DIR, PLUGIN_SRC_DIR, "*")
  trunk_dir = File.join(SVN_SRC_DIR, 'trunk')
  FileUtils.cp_r(Dir.glob(plugin_files), trunk_dir)

  # Create SVN tags for each git tagged release
  git_tags = `git tag -l`
  git_tags.each do |tag|
    tag = tag.strip
    if !EXCLUDE_TAGS.include? tag
      wp_tag = tag.sub(/^v/, '')
      puts "Creating SVN clone of tag #{tag}..."
      Dir.chdir(GIT_SRC_DIR) do
        `git checkout tags/#{tag} -- #{PLUGIN_SRC_DIR}`
      end
      tag_files = File.join(GIT_SRC_DIR, PLUGIN_SRC_DIR)
      tags_dir = File.join(SVN_SRC_DIR, 'tags', wp_tag)
      FileUtils.cp_r(tag_files, tags_dir)
    end
  end
end
