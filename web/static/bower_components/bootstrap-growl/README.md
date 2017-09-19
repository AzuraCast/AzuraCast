#bootstrap-growl

Pretty simple jQuery plugin that turns standard Bootstrap alerts into hovering "Growl-like" notifications.

##Demo

I have a basic demo set up at jsfiddle for the time being which you can view here: http://jsfiddle.net/c0bra/Us6WX/1/

##Features

* Uses standard [Twitter Bootstrap alerts](http://twitter.github.com/bootstrap/components.html#alerts) which provides 'info', 'error', and 'success' styles.
* Multiple growls called consecutively are stacked up one after another in a list.
* Automatically fades growls away after a default of 4 seconds.

##Dependencies

1. Latest version of jQuery. (tested on 1.8.3)
2. [Twitter Bootstrap](http://twitter.github.com/bootstrap/index.html). (current rev tested with 2.2.2)

##Usage

Include the dependencies and `jquery.bootstrap-growl.min.js` into your page and call the following:

```javascript
$.bootstrapGrowl("My message");
```

##Available Options

By default, growls use the standard 'alert' Bootstrap style, are 250px wide, right aligned, and are positioned 20px from the top right of the page.

```javascript
$.bootstrapGrowl("another message, yay!", {
  ele: 'body', // which element to append to
  type: 'info', // (null, 'info', 'error', 'success')
  offset: {from: 'top', amount: 20}, // 'top', or 'bottom'
  align: 'right', // ('left', 'right', or 'center')
  width: 250, // (integer, or 'auto')
  delay: 4000,
  allow_dismiss: true,
  stackup_spacing: 10 // spacing between consecutively stacked growls.
});
```

Note: Previous ```top_offset``` is not broken by this latest change.

##Additional Contributors

* Jose Martinez https://github.com/callado4
* Lloyd Watkin https://github.com/lloydwatkin
* TruongSinh Tran-Nguyen https://github.com/tran-nguyen
