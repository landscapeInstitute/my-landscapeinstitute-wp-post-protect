
# MyLI WP Extension Post ProtectPlugin

## Introduction

Requires MyLI WP Plugin fully setup

##

This plugin provides a security box on all post types, it lists all permissions types available to the MyLI Instance you have configured. 

When a user accesses a page, if they do not have a token, they are sent to get one using the base plugin, their token is then checked to see if it has
permission to view the given page. They will be shown a message if they do not, if they do, they will be shown the content. 