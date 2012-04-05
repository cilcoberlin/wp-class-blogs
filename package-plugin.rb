#!/usr/bin/env ruby

=begin
Builds a local SVN repository needed to update the WordPress.org listing from
the local git working copy.  This does not automatically update the remote
repository, as certain non-SVN files might need to be added before updating,
such as the banner image used by the WordPress.org plugin directory.
=end

require 'fileutils'
require 'find'

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

# Copy the contents of a git directory to an SVN directory
def git_dir_to_svn_dir(src, dest)

  # Create the directory if needed
  if !File.directory?(dest)
    FileUtils.mkdir_p(dest)
  end

  # Delete all non-SVN files in each directory in the SVN destination
  Find.find(dest) do |f|
    if File.directory?(f) and File.basename(f) == ".svn"
      Find.prune
    else
      if !File.directory?(f)
        FileUtils.rm(f)
      end
    end
  end

  # Copy the git files over and delete any blank directories, as these will have
  # been removed from the git repository
  FileUtils.cp_r(Dir.glob(File.join(src, "*")), dest)
  Find.find(dest) do |f|
    if File.directory?(f) and File.basename(f) == ".svn"
      Find.prune
    end
    if File.directory?(f) and (Dir.entries(f) - [".", ".."]).empty?
      Dir.rmdir(f)
      Find.prune
    end
  end

end

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
  head_dir = File.join(GIT_SRC_DIR, PLUGIN_SRC_DIR)
  trunk_dir = File.join(SVN_SRC_DIR, 'trunk')
  git_dir_to_svn_dir(head_dir, trunk_dir)

  # Create SVN tags for each git tagged release
  git_tags = `git tag -l`
  git_tags.each do |tag|
    tag = tag.strip
    if !EXCLUDE_TAGS.include? tag
      wp_tag = tag.sub(/^v/, '')
      puts "Creating SVN clone of tag #{tag}..."
      Dir.chdir(GIT_SRC_DIR) do
        FileUtils.rm_rf(PLUGIN_SRC_DIR)
        `git checkout tags/#{tag} -- #{PLUGIN_SRC_DIR}`
      end
      git_tag = File.join(GIT_SRC_DIR, PLUGIN_SRC_DIR)
      svn_tag = File.join(SVN_SRC_DIR, 'tags', wp_tag)
      git_dir_to_svn_dir(git_tag, svn_tag)
    end
  end
end
