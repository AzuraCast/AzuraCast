Material Design Shadows
==============

The paper shadows, based on [Google's Material Design](http://www.google.com/design/spec/layout/layout-principles.html), for use in your project.

Support
--------------

Support for all popular css preprocessors: [Less](http://lesscss.org/), [Sass](http://sass-lang.com/) and [Stylus](http://learnboost.github.io/stylus/).

Installation
--------------

 * Download the files you need from the this repository;
 * Bower: `$ bower install material-shadows --save`;
 * Git: `$ git clone git://github.com/mrmlnc/material-shadows.git`;

How to use
--------------

Just import the file, which includes mixins in your project.

**Less:**

````Less
  @import "lib/material-shadows";
````

**Sass:**

````Sass
  @import "lib/material-shadows"
````

**Stylus:**

````Stylus
  @import "lib/material-shadows";
````

If you use Bower, the path would be:

````
  bower_components/material-shadows/..
````

**The build variable:**

`(. | @include | none)(prefix)-z-depth-(depth)-(orientation)`

  - **(. | @include | none)** - Sign of the variable in the preprocessor.
  - **(prefix)** - The prefix variable. Namespace of your variables and variables of the library. (With `material-shadows-prefixed` and without `material-shadows`)
  - **(depth)** - Depth 1..5.
  - **(orientation)** - None, Top or Bottom.

**Simple example (Less):**

````Less
  @import "lib/material-color";

  .example-1 {
    .z-depth();
    .z-depth-animation(3);
  }

  .example-2 {
    .z-depth-top(1);
    .z-depth-animation(5, top);
  }
````

HTML for `.z-depth-animation()`:

````HTML
  <div class="z-depth-animation">
    <div class="z-depth-2">.z-depth-2</div>
  </div>
````

**Class generator example (less):**

Options for animation:

 - @animation: [true (default) | false];
 - @time: .28s;
 - @function: cubic-bezier(.4, 0, .2, 1);

````Less
  @import "lib/material-color";
  
  // Class mixin
  .z-depth {
    .z-depth-class();
  }
  
  // => (output)
  .z-depth-1 {
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.16), 0 2px 10px rgba(0, 0, 0, 0.12);
  }
  .z-depth-1-top {
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.12);
  }
  .z-depth-1-bottom {
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.16);
  }
  // and more
````

More examples in the test directory.