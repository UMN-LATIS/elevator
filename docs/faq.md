# Frequently Asked Questions

## Can I keep some assets private?

Every Elevator instance starts out as fully locked down - only the admin has access. Permissions can then be granted to the overall instance or to individual collections. In some cases, you may want to keep some assets "hidden" or restricted to a subset of users. In these cases, you should manage your permissions at the collection level. For example, you can add an "all users" group to some collections, granting the world access to view derivatives in those collections. Other collections may be restricted to only specific users. If users don't have rights to a collection, it won't show up in their collection browse view, and they won't have access to those assets.

Because permissions are inherited by children collections, you can create a "Public" collection and a "Private" collection, and then add sub-collections under those. That makes it easy to manage your permissions.

## Can I prevent users from saving copies of our digital assets?

Right now, we take the approach of "if your visitors can see an asset, they can save a copy". There is an instance-level setting that allows you to hide the download links for video and audio media. Beyond that, Elevator doesn't attempt to prevent users from (for example) right clicking on an image and saving a copy. Because of the numerous browser plugins that make it easy to circumvent any of these restrictions, we encourage you to manage access to your content using permissions. 

## Can I hide the timeline and map view, or tweak other layout?

Most parts of the Elevator interface are selectable and tweak-able with CSS and Javascript. You can use the custom CSS and custom header fields in your instance to add inline styling and javascript, which you can use to hide items, automatically expand collapsable boxes, or otherwise customize the experience. If you need help or need selectors added, just let us know. 

## Can I supply fully custom CSS?

If you're one of our partner institutions, we can have your instances use an alternative base CSS set. We can supply a starting set based on our Bootstrap 3 foundation. 

## Can I customize the "all collections" screen

If you create an Instance Page titled "Collection Page", that content will automatically be drawn at the top of the "all collections" browsing page. 