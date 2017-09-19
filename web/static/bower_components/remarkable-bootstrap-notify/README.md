# Bootstrap Notify
This is a simple plugin that turns standard Bootstrap alerts into "Growl-like" notifications.

## Bower Officially Supported
I would like to thank [Błażej Krysiak](https://github.com/IjinPL) for doing this!
```
bower install remarkable-bootstrap-notify
```

## Meteor Officially Supported
Meteor integration by [zimme](https://github.com/zimme).

```sh
meteor add mouse0270:bootstrap-notify
```

## Changelog
##### Version 3.1.3
- Added Meteor Support
- Fixed issue with Glyphicons Pro
- Updating version pattern. 
```
x.y.z
x = Main version of the plugin
y = New features were added to the plugin
z = Fixes/patches to existing features of the plugin
```

##### [Version 3.0.2](http://bootstrap-notify.remabledesigns.com/3.0.2/)
- Fixed update for backwards compatibility

##### [Version 3.0.1](http://bootstrap-notify.remabledesigns.com/3.0.1/)
- Add the ability to update multiple values in one call
- Turn off Progress bar
- Set Progress bar value / Progress bar not shown by default
``` javascript
//Update
var notify = $.notify('<strong>Saving</strong> Do not close this page...', { allow_dismiss: false });
notify.update({ type: 'success', '<strong>Success</strong> Your page has been saved!' });

// Turn of Progress bar on
$.notify('I have a progress bar', { showProgressbar: true });

// Update Progress bar
var notify = $.notify('<strong>Saving</strong> Do not close this page...', { allow_dismiss: false });
notify.update({ type: 'warning', '<strong>Oops</strong> Something happened. Correcting Now', progress: 20 });
```

##### [Version 3.0.0](http://bootstrap-notify.remabledesigns.com/3.0.0/)
- New template structure
- Better event handling for onShow, onShown, onClose, onClosed
- updating notification content will reposition growls below it
- Fixed updating icon images
- Fixed IE Issues with Growl URL not being able to be clicked on
- Added the ability to show progress bars
- Added the ability to pass position in the settings
- Added *_newest_on_top_* option that allows new growls to push down old growls
- Added Transition CSS to plugin
```css
tranition: all 0.5 ease-in-out;
```
- Remember to read to documenation. I use custom css style's for the progress bar that you can find there. This was left out of the plugin so you could choose to use the default progressbar styles provided for bootstrap or write your own.

##### [Version 2.0.1](http://bootstrap-growl.remabledesigns.com/2.0.1/)
- Added the ability to set an X and Y value within the offset option
- Added callback options onShow, onShown, onHide and onHidden
- Added a close all method to close all open growls

##### [Version 2.0.0a3](http://bootstrap-growl.remabledesigns.com/2.0.0a3/)
- Fixed issue with growl not closing if there was no CSS animations

##### [Version 2.0.0a2](http://bootstrap-growl.remabledesigns.com/2.0.0a2/) (with IE8 Support)
- Changed animate.in to animate.enter for IE8 compatibility
- Changed animate.out to animate.exit for IE8 compatibility
- Modified .is(':hover') for IE8 compatibility

##### [Version 2.0.0a1](http://bootstrap-growl.remabledesigns.com/2.0.0a1/)
- Better Minification

##### [Version 2.0.0a](http://bootstrap-growl.remabledesigns.com/2.0.0a1/)
- Major rewright of the plugin file.
- Added the ability to pass the growl a link making it clickable.
- Added the ability to control the growl animations in and out using css.
- Added the ability to set growl settings globally.
- Removed jQuery fadeIn (use css to control growl animations)

##### [Version 1.0.6](http://bootstrap-growl.remabledesigns.com/1.0.6/)
- Added onGrowlShow and onGrowlShown callback functionality.

##### Version 1.0.5
- Better positioning when using CSS animations after growl closes.

##### Version 1.0.4
- Updated $.growl() to return a wrapper object with a small API to let you control individual notifications after they have been created.
- Added onGrowlClose and onGrowlClosed callback functionality.

##### Version 1.0.3
- Made jQuery $.extend() Recursive so when you change just one option under position or template the script wont fail

##### Version 1.0.2
- Fixed an issue where $.growl("message") would thrown an exception | Provided by [DannyJo](https://github.com/DannyJo/bootstrap-growl)

##### Version 1.0.0
- Initial Release

## Demo
I have created a small demo to show off some of the features that you can use with this plugin. http://bootstrap-growl.remabledesigns.com/

## Dependencies
- [jQuery v1.10.2](http://jquery.com/)
- [Bootstrap v2.0.0 - 3.2.0](http://getbootstrap.com/)


## Documentation
There is alot of documentation on the website. Click the link below to read it.
- [Documentation](http://bootstrap-notify.remabledesigns.com/#documentation)

## Copyright and License
The MIT License (MIT)
Copyright (c) 2014 Robert McIntosh

Permission is hereby granted, free of charge, to any person obtaining a copy of
this software and associated documentation files (the "Software"), to deal in
the Software without restriction, including without limitation the rights to
use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of
the Software, and to permit persons to whom the Software is furnished to do so,
subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS
FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER
IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
