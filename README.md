# Atom Feeds

This CiviCRM extension provides dashlets for atom feeds, because the native
CiviCRM blog dashlet only supports rss. (I think.)

For the moment it supports only one feed. But I want (at some point) to be
able to support multiple feeds.

I also want to provide some hook that allows tampering with the
description/content of the feed items. Our use case is that items in our
feed refer to issues in our issue tracker; I want to be able to insert
hyperlinks.

## Configuration

Add this to your civicrm.settings.php:

$civicrm_setting['atomfeeds']['feed_urls'] = array('https://civicrm.org/blog/feed');

Of course, you should replace the url to the url of your feed. The url is in
an array: as soon as multiple feeds are supported, you can configure them by
putting more elements into the array.

## This is alpha software

A lot of functionality is missing, and this extension is not my primary
software project. So please send me pull requests :-)