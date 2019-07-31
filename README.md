
## MyLI WP Post Protection


### Description

On it's own, when this plugin is installed it merely allows use of the myli_wp class which is an extended class of the myLI PHP class which uses wordpress data to store its settings. 
You can use the functions, methods, actions and filters provided by this plugin to add additional functionality.

### Installation

1. Upload the plugin files to the `/wp-content/plugins/plugin-name` directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress

### Requirements

Requires MyLI WP Plugin fully setup

### Usage

This plugin provides a security box on all post types, 

- it lists all permissions types available to the MyLI Instance you have configured. 
- You can pick what permissions you want to lock this post down for viewing to
- When a user accesses a page, if they are not logged in, they will be sent to log in
- If they do not have the permission they are shown an error