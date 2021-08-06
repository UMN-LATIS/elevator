---
date: 2018-08-02
---


# New Release: 1.7.1

This release adds the ability to do bulk updates via CSV, and the ability to use iframe embeds in place of Elevator-hosted assets.

## Bulk CSV updates

To do a bulk update via CSV, we recommend first downloading a CSV for a given set of assets.  This is only available to instance admins.  Then remove any columns you won't be editing.  Be sure to leave the ObjectID column in place.  Make any necessary changes and save the CSV.

Use the "import CSV" functionality to bring that CSV back in, matching the ObjectID field with the ObjectID selection in the dropdown.  This will edit those assets rather than creating new assets.

## iFrame Embeds

Sometimes you might want to embed an asset from another site, rather than uploading a digital object directly to Elevator.  If you add the "enableIframe:true" key to your upload widget within your template, you'll get the option to add a URL for an embed, which will be delivered via an iFrame.  You must also add an image attachment which will be used as a thumbnail.


