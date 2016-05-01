Cross Browser Zoom and Pixel Ratio Detector
======
------

### Known issues:
* In Firefox on retina displays (Macbook Pros), the base zoom level is always 2.0. I'm aware of it and looking for a solution.
* In some multi-monitor enviroments where each monitor has a different 'pixel aspect ratio' windows that span accross both monitors might return false pixelAspectRatio values.

What is this for?
------
Detecting the browser zoom level and device pixel ratio relative to the zoom level.

It can be used to show higher-resolution `canvas` or `img` when necessary,
to warn users that your site's layout will be broken in their current zoom level,
and much more.
Personally I'm maintaining it to use Detect-zoom in [Wix.com](http://wix.com)'s editor to warn users
that their browser is in zoom mode before saving important changes to their website.

What happend to @yonran?
------
Don't worry, he is well.
As of January 2013 [@yonran](https://github.com/yonran) stopped maintaining his source of detect-zoom, and transferred the repository to me.
If you are looking to update previous versions note that there were some breaking changes

* **Major Changes from the latest yonran version:**
    * `DetectZoom` object name changed to `detectZoom`
    * `DetectZoom.ratio()` is no longer publicly accessible
    * Supported browsers: IE8+, FF4+, modern Webkit, mobile Webkit, Opera 11.1+
    * *IE6, IE7, FF 3.6 and Opera 10.x are no longer supported*
    * Added support to be loaded as an AMD and CommonJS module

Live Example
------
See the Live Example section in
http://tombigel.github.com/detect-zoom/

Usage
------
**Detect-zoom has only two public functions:**
* `zoom()`   Returns the zoom level of the user's browser using Javascript.
* `device()`   Returns the device pixel ratio multiplied by the zoom level (Read [more about devicePixelRatio](http://www.quirksmode.org/blog/archives/2012/07/more_about_devi.html) at QuirksMode)

```html
<script src="detect-zoom.js"></script>
<script>
  var zoom = detectZoom.zoom();
  var device = detectZoom.device();

  console.log(zoom, device);
</script>
```

**AMD Usage**

```javascript
require(['detect-zoom'], function(detectZoom){
    var zoom = detectZoom.zoom();
});
```

**Installing with NPM**

```bash
> npm install detect-zoom
```

Changelog
------

2013/4/01
* Changed WebKit detection from deprecated webkitTextSizeAdjust to webkitMarquee
* Changed WebKitMobile detection from unreliable 'ontouchstart' event (can be triggered on desktops too) to 'orientation' property that is hopefully more reliable
* Minor version bump to 1.0.4

2013/3/29
* Added package.json (thanks [@TCampaigne](https://github.com/TCampaigne))
* Some documentation fixes
* Added detect-zoom to npm package manager (again, thanks [@TCampaigne](https://github.com/TCampaigne)) 

2013/2/25
* Fixed a missing 'else' between ie8 and ie10 detection
* Minor version bump to 1.0.2

2013/2/15
* Added a fix for IE10 Metro (or whatever MS calls it these days..) by [@stefanvanburen](https://github.com/stefanvanburen)
* Minor version bump to 1.0.1
* Added minimized version

2013/2/05
* Merged a pull request that fixed zoom on IE being returned X100 (thanks [@kreymerman](https://github.com/kreymerman))
* Refactored the code some more, changed some function names
* Browser dependent main function is created only on initialization (thanks [@jsmaker](https://github.com/jsmaker))
* _Open Issue: Firefox returns `zoom` and `devicePixelRatio` the same. Still looking for a solution here._
* Started versioning - this is version 1.0.0

2013/1/27
* Added a fix to Mozilla's (Broken as I see it - https://bugzilla.mozilla.org/show_bug.cgi?id=809788)
implementation of window.devicePixel starting Firefox 18

2013/1/26
* Repository moved here
* Refactored most of the code
* Removed support for older browsers
* Added support for AMD and CommonJS


Help Needed
------

***Detect-zoom is not complete, many parts of the code are 6 to 12 months old and I'm still reviewing them
I need help testing different browsers, finding better ways to measure zoom on problematic browsers (ahm.. Firefox.. ahm)
patches are more than welcome.***


License
------

Detect-zoom is dual-licensed under the [WTFPL](http://www.wtfpl.net/about/) and [MIT](http://opensource.org/licenses/MIT) license, at the recipient's choice.
