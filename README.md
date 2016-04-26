# Atom Feeds

This CiviCRM extension provides dashlets for atom feeds, because the native
CiviCRM blog dashlet only supports rss. (I think.)

For the moment it supports only one feed. But I want (at some point) to be
able to support multiple feeds.

## Configuration

Add this to your civicrm.settings.php:

    $civicrm_setting['atomfeeds']['feed_urls'] = array('https://civicrm.org/blog/feed');

Of course, you should replace the url to the url of your feed. The url is in
an array: as soon as multiple feeds are supported, you can configure them by
putting more elements into the array.

## Hook

You can implement `hook_atomfeeds_alter_content(&$content)` to tamper with the
feed content before it is displayed. Our particular use case is replacing
issue numbers by hyperlinks to issues in our issue tracker.

## This is alpha software

A lot of functionality is missing, and this extension is not my primary
software project. So please send me pull requests :-)