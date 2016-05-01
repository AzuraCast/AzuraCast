Farbtastic: jQuery color picker plug-in
=======================================

Farbtastic is a [jQuery](http://jquery.com/) plug-in that can add one or more color picker widgets into a page. Each widget is then linked to an existing element (e.g. a text field) and will update the element's value when a color is selected.

Farbtastic 2 uses the html5 canvas element to render a saturation/luminance gradient inside of a hue circle. In order to work with Internet Explorer, which does not currently support the canvas element, [Explorer Canvas](http://code.google.com/p/explorercanvas) is needed to translate the canvas usage into features native to Internet Explorer.

**Notice:** *The 2.x branch is under development and considered not production ready. If you are interested in a production tested version see the 1.x branch.*

Farbtastic was originally written by [Steven Wittens](http://acko.net/) and is licensed under the GPL.

Basic Usage
-----------

1) Include farbtastic.js in your HTML:

     <script type="text/javascript" src="farbtastic.js"></script>

2) Add a placeholder div and a text field to your HTML, and give each an ID:

    <form><input type="text" id="color" name="color" value="#123456" /></form>
    <div id="colorpicker"></div>

3) Add a `ready()` handler to the document which initializes the color picker and link it to the text field with the following syntax:

    <script type="text/javascript">
      $(document).ready(function() {
        $('#colorpicker').farbtastic('#color');
      });
    </script>

See demo/test.html for an example.

Advanced Usage
--------------

### jQuery Method

	$(...).farbtastic()
	$(...).farbtastic(callback)
    
This creates color pickers in the selected objects. `callback` is optional and can be a:

* DOM Node, jQuery object or jQuery selector: the color picker will be linked to the selected element(s) by syncing the value (for form elements) and color (all elements).
* Function: this function will be called whenever the user chooses a different color.

### Object

	$.farbtastic(placeholder)
	$.farbtastic(placeholder, callback)
    
Invoking `$.farbtastic(placeholder)` is the same as using `$(placeholder).farbtastic()` except that the Farbtastic object is returned instead of the jQuery object. This allows you to use the Farbtastic methods and properties below.
Note that there is only one Farbtastic object per placeholder. If you call `$.farbtastic(placeholder)` twice with the same placeholder, you will get the same object back each time.

The optional callback argument behaves exactly as for the jQuery method.

### Options

	$(...).farbtastic(options)
	$.farbtastic(placeholder, options)

Farbtastic 2 provides the ability to pass in other options beyond a callback. The possible options are:

* callback: The callback as described previously.
* height: The height of the widget.
* width: The width of the widget.

An example usage would be `$(...).farbtastic({ callback: '#color2', width: 150 })`.

### Methods

`.linkTo(callback)` - Allows you to set a new callback. Any existing callbacks are removed. See above for the meaning of callback.

`.setColor(string)` - Sets the picker color to the given color in hex representation.

`.setColor([h, s, l])` - Sets the picker color to the given color in normalized HSL (0..1 scale).

### Properties

`.linked` - The elements (jQuery object) or callback function this picker is linked to.

`.color` - Current color in hex representation.

`.hsl` - Current color in normalized HSL.