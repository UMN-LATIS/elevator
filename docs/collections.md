# Collections
Collections are groupings for assets.  You must have at least one collection in order to add assets.  

## Creating a Collection
To create a collection, select “edit collections” from the “admin” drop down and then click “Create new collection”

Generally, you’ll only need to populate the title for your collection.  If you’d like this collection stored in a separate location in the cloud, you can populate appropriate S3 information as well.  By default, it’s copied from your instance.

Sometimes you may wish to use a separate bucket for a given collection.  For example, you may have most of your collections to automatically migrate original files to “glacier” storage, which is much less expensive, but much slower to access.  You may then wish to keep one collection’s original assets always available.  You’d do this by creating a using the bucket creation button after clearing out the existing S3 fields.  

Collections can be nested within other collections by assigning a collection parent.  They will be grouped hierarchically in dropdown and in the collection browsing interface.

You can attach a “collection description” which will be displayed when browsing the collections on your instance.  You can also add a collection preview.  This will be the Object ID for an upload attachment within your site.  To find the object ID for a specific attachment, click the “i” inspector icon for the attachment, and then look for the “Object ID” field.

## Sharing Collections
Collections may be shared between instances.  By sharing a collection, you’ll be granting the receiving instance’s admin full power over your collection.

