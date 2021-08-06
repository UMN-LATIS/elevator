---
date: 2019-03-17
---

# New Release: 1.7.8

Elevator 1.7.8 adds a new per-instance option which allows you to show "previous" and "next" result links when viewing assets.  This way, if you do a search or browse a collection and then click into an asset, you'll be able to continue navigating through the available assets without returning to the index.

This option is off by default, and is enabled in instance settings.

Other changes in this release:

* Fix for an issue when doing bulk updates via CSV that included multiple related assets within a single field
* Fix for an issue that prevented administrators from viewing the API key of other users
* Better handling for corrupt unicode data in image file metadata
* Fix for possible infinite loop when creating circular related asset references in assets without any files (a -> b ->c -> a type relationships)
* Fix for handling MKV files with embedded subtitles
* Add date sorting to advanced search modal
* Gallery sizing tweaks
* Move to using "//" syntax for embeds instead of including the protocol (http:// or https://) to avoid browser warnings when mixing http and https

