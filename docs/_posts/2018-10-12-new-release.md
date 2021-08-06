---
date: 2018-10-12
---


# New Release: 1.7.3

This release adds explicit control over search engine indexing, as well as the ability to specify a custom thumbnail for audio embeds.

## Search Engine Indexing

Within your instance settings, you can now control whether search engines like Google are permitted to index your instance.  This will be off by default.

## Audio Thumbnail Support

By default, Elevator shows an audio waveform as a placeholder for audio assets.  You can now optionally specify another image in place of the waveform.  To enable this, add a "thumbnailTarget" key your upload widget, specifying the field to use for the thumbnail.  Generally, you'd add a second upload widget to your template to host the thumbnail, and set it not to display.

To find the field name, use the control-command-h or control-alt-h keyboard combination when editing the template.  The "internal title" field is the item you need.


