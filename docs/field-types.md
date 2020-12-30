---
sidebarDepth: 1
---

# Types of Fields

## Text
A text field is a simple one-line text entry box.

## Text Area
A text area is a larger text entry field which supports line returns and formatting.

## Date
A date field can contain either a single date (4/1/2014) or a range of dates.  Date fields also support dates in the form of “10000 BCE”.  

## Location
A location will be a latitude and longitude, along with a label.  You can look up a location by address, or by entering the coordinates.  If you don’t enter a label, the latitude and longitude will be displayed to your viewers.

## Select
A select dropdown.  The items in the drop down are defined in the template, using the JSON format.  A basic drop down is:

``` js
{
  "multiSelect": false,
  "selectGroup": 
    {
    "Option 1":"more text about option 1",
    "Option 2":"more text about option 2",
    "Option 3":"more text about option 3"
    }
}
```

The “more text” is the text that will be displayed to the viewer of an asset.  The “option” entry is what will be displayed to the person adding the asset.

The “more text” section can be omitted.    Sample JSON is displayed when adding this field to a template.

## Multi-Select
 A multiselect is a cascading select.  For example, you may first select a country, then a state, then a city.

These are complicated structures and it’s recommended that you define the JSON for them using a JSON editor.  Sample JSON is displayed when selecting this field in the template.

## Tag List
A tag list is a comma separated set of tags. Each tag will automatically be linked to a search for that term.

## CheckBox
A simple on/off checkbox

## Related Assets
This field allows you to link or embed other assets within one asset.  For example, you may create a “person” template for defining content creators, and then nest those records within records describing their content.  This type of field can be customized using some additional JSON, as follows.

``` js
{ 
    "nestData":true,
    "showLabel":true, 
    "collapseNestedChildren":false, 
    "thumbnailView":false, 
    "defaultTemplate": 0,
    "matchAgainst": [0], 
    "displayInline": false,
    "ignoreForDigitalAsset": false,
    "ignoreForLocationSearch": false,
    "ignoreForDateSearch": false
}
```

### nestData

Should the nested asset be displayed inline, or should the user click a link and open the asset in a new field.

### showLabel

In addition to creating the relationship to the asset, should a label be attached describing the relationship?

### collapseNestedChildren

If the related asset points to other related assets, should those all be flattened into a single record when displaying?

### thumbnailView

Instead of displaying the nested assets as a list, should be they displayed as thumbnails?

### defaultTemplate

Should the related assets default to a specific template?

### displayInline

Controls whether this template draws directly inside another template, or is opened in its own screen.

### matchAgainst

An array listing the other templates that this field should be matched against when doing autocomplete.

### ignoreForDigitalAsset

Sometimes, your parent record may have no upload field of its own. By default, Elevator will automatically load the digital asset from a related record in that case. Setting this value to true will disable this. It will also prevent the related asset from being used to populate a thumbnail.

### ignoreForLocationSearch

Normally, the location of a related record will impact the map location of the parent. Setting this value to true will prevent the related record from impacting the parent's location.

### ignoreForDateSearch

Normally, the location of a related record will impact the timeline location of the parent. Setting this value to true will prevent the related record from impacting the parent's timeline location.




## Upload
A file-attachment field.  This allows users to upload a file their computer.  JSON controls whether dates and locations should automatically be extracted from uploaded files.

``` js
 {
    "extractLocation":true, 
    "extractDate":true, 
    "enableTiling":true, 
    "enableDendro": false, 
    "enableIframe":false, 
    "enableAnnotation":false,
    "forceTiling": false,
    "interactiveTranscript": false
}
```

### extractLocation
Automatically extra location data from image EXIF data.

### extractDate
Automatically date/time data from image EXIF data.

### enableTiling
When images exceed ~35 megapixels, Elevator can generate tiled versions, allowing for high resolution zooming. Disabling this option will save some storage space.

### enableDendro
This enabled our specialized tree-core annotation functionality

### enableIframe
When enabled, this allows you to enter a URL for an asset, instead of attaching a file. The asset will then be embedded via an iframe tag.

### enableAnnotation
This enables markup tools that allow viewer to annotate images. Users with "curation" level privileges can also save those annotations to the server. 

### forceTiling
Normally, Elevator only generates tiles for images greater than 30 megapixels, or when a feature that requires tiles (like annotation) is enabled. This option allows you to force tiling for all image assets.

### interactiveTranscript
When this is enabled, video assets with attached captions will have an interactive transcript displayed below the movie. This allows users to search and navigate based on the caption text. If you also add chapter markers to the file, these will be used to add formatting to the transcript sections. 

### Sidecar Data
Some file formats (movies, 3d objects) will present an additional field when being uploaded.  In this case of movies, this is where you can add SRT or WebVTT subtitles, or WebVTT chapter markers.  For 3d Objects, a custom JSON attachment can describe points of interest.  