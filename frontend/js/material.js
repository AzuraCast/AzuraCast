/*!
 * Daemonite Material v4.1.1 (http://daemonite.github.io/material/)
 * Copyright 2011-2018 Daemon Pty Ltd
 * Licensed under MIT (https://github.com/Daemonite/material/blob/master/LICENSE)
 */

(function (global, factory) {
  typeof exports === 'object' && typeof module !== 'undefined' ? factory(exports, require('jquery')) :
  typeof define === 'function' && define.amd ? define(['exports', 'jquery'], factory) :
  (factory((global.material = {}),global.jQuery));
}(this, (function (exports,$) { 'use strict';

  $ = $ && $.hasOwnProperty('default') ? $['default'] : $;

  /*
   * Expansion panel plugins expands a collapsed panel in full upon selecting
   */

  var ExpansionPanel = function ($$$1) {
    // constants >>>
    var DATA_KEY = 'bs.collapse';
    var EVENT_KEY = "." + DATA_KEY;
    var ClassName = {
      SHOW: 'show',
      SHOW_PREDECESSOR: 'show-predecessor'
    };
    var Event = {
      HIDE: "hide" + EVENT_KEY,
      SHOW: "show" + EVENT_KEY
    };
    var Selector = {
      PANEL: '.expansion-panel',
      PANEL_BODY: '.expansion-panel .collapse' // <<< constants

    };
    $$$1(document).on("" + Event.HIDE, Selector.PANEL_BODY, function () {
      var target = $$$1(this).closest(Selector.PANEL);
      target.removeClass(ClassName.SHOW);
      var predecessor = target.prev(Selector.PANEL);

      if (predecessor.length) {
        predecessor.removeClass(ClassName.SHOW_PREDECESSOR);
      }
    }).on("" + Event.SHOW, Selector.PANEL_BODY, function () {
      var target = $$$1(this).closest(Selector.PANEL);
      target.addClass(ClassName.SHOW);
      var predecessor = target.prev(Selector.PANEL);

      if (predecessor.length) {
        predecessor.addClass(ClassName.SHOW_PREDECESSOR);
      }
    });
  }($);

  /*
   * Floating label plugin moves inline label to float above the field
   * when a user engages with the assosciated text input field
   */

  var FloatingLabel = function ($$$1) {
    // constants >>>
    var DATA_KEY = 'md.floatinglabel';
    var EVENT_KEY = "." + DATA_KEY;
    var NAME = 'floatinglabel';
    var NO_CONFLICT = $$$1.fn[NAME];
    var ClassName = {
      IS_FOCUSED: 'is-focused',
      HAS_VALUE: 'has-value'
    };
    var Event = {
      CHANGE: "change" + EVENT_KEY,
      FOCUSIN: "focusin" + EVENT_KEY,
      FOCUSOUT: "focusout" + EVENT_KEY
    };
    var Selector = {
      DATA_PARENT: '.floating-label',
      DATA_TOGGLE: '.floating-label .custom-select, .floating-label .form-control' // <<< constants

    };

    var FloatingLabel =
    /*#__PURE__*/
    function () {
      function FloatingLabel(element) {
        this._element = element;
        this._parent = $$$1(element).closest(Selector.DATA_PARENT)[0];
      }

      var _proto = FloatingLabel.prototype;

      _proto.change = function change() {
        if ($$$1(this._element).val() || $$$1(this._element).is('select') && $$$1('option:first-child', $$$1(this._element)).html().replace(' ', '') !== '') {
          $$$1(this._parent).addClass(ClassName.HAS_VALUE);
        } else {
          $$$1(this._parent).removeClass(ClassName.HAS_VALUE);
        }
      };

      _proto.focusin = function focusin() {
        $$$1(this._parent).addClass(ClassName.IS_FOCUSED);
      };

      _proto.focusout = function focusout() {
        $$$1(this._parent).removeClass(ClassName.IS_FOCUSED);
      };

      FloatingLabel._jQueryInterface = function _jQueryInterface(event) {
        return this.each(function () {
          var _event = event ? event : 'change';

          var data = $$$1(this).data(DATA_KEY);

          if (!data) {
            data = new FloatingLabel(this);
            $$$1(this).data(DATA_KEY, data);
          }

          if (typeof _event === 'string') {
            if (typeof data[_event] === 'undefined') {
              throw new Error("No method named \"" + _event + "\"");
            }

            data[_event]();
          }
        });
      };

      return FloatingLabel;
    }();

    $$$1(document).on(Event.CHANGE + " " + Event.FOCUSIN + " " + Event.FOCUSOUT, Selector.DATA_TOGGLE, function (event) {
      FloatingLabel._jQueryInterface.call($$$1(this), event.type);
    });
    $$$1.fn[NAME] = FloatingLabel._jQueryInterface;
    $$$1.fn[NAME].Constructor = FloatingLabel;

    $$$1.fn[NAME].noConflict = function () {
      $$$1.fn[NAME] = NO_CONFLICT;
      return FloatingLabel._jQueryInterface;
    };

    return FloatingLabel;
  }($);

  function _defineProperties(target, props) {
    for (var i = 0; i < props.length; i++) {
      var descriptor = props[i];
      descriptor.enumerable = descriptor.enumerable || false;
      descriptor.configurable = true;
      if ("value" in descriptor) descriptor.writable = true;
      Object.defineProperty(target, descriptor.key, descriptor);
    }
  }

  function _createClass(Constructor, protoProps, staticProps) {
    if (protoProps) _defineProperties(Constructor.prototype, protoProps);
    if (staticProps) _defineProperties(Constructor, staticProps);
    return Constructor;
  }

  function _defineProperty(obj, key, value) {
    if (key in obj) {
      Object.defineProperty(obj, key, {
        value: value,
        enumerable: true,
        configurable: true,
        writable: true
      });
    } else {
      obj[key] = value;
    }

    return obj;
  }

  function _objectSpread(target) {
    for (var i = 1; i < arguments.length; i++) {
      var source = arguments[i] != null ? arguments[i] : {};
      var ownKeys = Object.keys(source);

      if (typeof Object.getOwnPropertySymbols === 'function') {
        ownKeys = ownKeys.concat(Object.getOwnPropertySymbols(source).filter(function (sym) {
          return Object.getOwnPropertyDescriptor(source, sym).enumerable;
        }));
      }

      ownKeys.forEach(function (key) {
        _defineProperty(target, key, source[key]);
      });
    }

    return target;
  }

  /*
   * Global util js
   * Based on Bootstrap's (v4.1.X) `util.js`
   */

  var Util = function ($$$1) {
    var MAX_UID = 1000000;
    var MILLISECONDS_MULTIPLIER = 1000;
    var TRANSITION_END = 'transitionend';

    function getSpecialTransitionEndEvent() {
      return {
        bindType: TRANSITION_END,
        delegateType: TRANSITION_END,
        handle: function handle(event) {
          if ($$$1(event.target).is(this)) {
            return event.handleObj.handler.apply(this, arguments); // eslint-disable-line prefer-rest-params
          }

          return undefined; // eslint-disable-line no-undefined
        }
      };
    }

    function setTransitionEndSupport() {
      $$$1.fn.emulateTransitionEnd = transitionEndEmulator;
      $$$1.event.special[Util.TRANSITION_END] = getSpecialTransitionEndEvent();
    }

    function toType(obj) {
      return {}.toString.call(obj).match(/\s([a-z]+)/i)[1].toLowerCase();
    }

    function transitionEndEmulator(duration) {
      var _this = this;

      var called = false;
      $$$1(this).one(Util.TRANSITION_END, function () {
        called = true;
      });
      setTimeout(function () {
        if (!called) {
          Util.triggerTransitionEnd(_this);
        }
      }, duration);
      return this;
    }

    var Util = {
      TRANSITION_END: 'mdTransitionEnd',
      getSelectorFromElement: function getSelectorFromElement(element) {
        var selector = element.getAttribute('data-target');

        if (!selector || selector === '#') {
          selector = element.getAttribute('href') || '';
        }

        try {
          var $selector = $$$1(document).find(selector);
          return $selector.length > 0 ? selector : null;
        } catch (err) {
          return null;
        }
      },
      getTransitionDurationFromElement: function getTransitionDurationFromElement(element) {
        if (!element) {
          return 0;
        }

        var transitionDuration = $$$1(element).css('transition-duration');

        if (!transitionDuration) {
          return 0;
        }

        transitionDuration = transitionDuration.split(',')[0];
        return parseFloat(transitionDuration) * MILLISECONDS_MULTIPLIER;
      },
      getUID: function getUID(prefix) {
        do {
          // eslint-disable-next-line no-bitwise
          prefix += ~~(Math.random() * MAX_UID);
        } while (document.getElementById(prefix));

        return prefix;
      },
      isElement: function isElement(obj) {
        return (obj[0] || obj).nodeType;
      },
      reflow: function reflow(element) {
        return element.offsetHeight;
      },
      supportsTransitionEnd: function supportsTransitionEnd() {
        return Boolean(TRANSITION_END);
      },
      triggerTransitionEnd: function triggerTransitionEnd(element) {
        $$$1(element).trigger(TRANSITION_END);
      },
      typeCheckConfig: function typeCheckConfig(componentName, config, configTypes) {
        for (var property in configTypes) {
          if (Object.prototype.hasOwnProperty.call(configTypes, property)) {
            var expectedTypes = configTypes[property];
            var value = config[property];
            var valueType = value && Util.isElement(value) ? 'element' : toType(value);

            if (!new RegExp(expectedTypes).test(valueType)) {
              throw new Error(componentName.toUpperCase() + ": " + ("Option \"" + property + "\" provided type \"" + valueType + "\" ") + ("but expected type \"" + expectedTypes + "\"."));
            }
          }
        }
      }
    };
    setTransitionEndSupport();
    return Util;
  }($);

  /*
   * Navigation drawer plguin
   * Based on Bootstrap's (v4.1.X) `modal.js`
   */

  var NavDrawer = function ($$$1) {
    // constants >>>
    var DATA_API_KEY = '.data-api';
    var DATA_KEY = 'md.navdrawer';
    var ESCAPE_KEYCODE = 27;
    var EVENT_KEY = "." + DATA_KEY;
    var NAME = 'navdrawer';
    var NO_CONFLICT = $$$1.fn[NAME];
    var ClassName = {
      BACKDROP: 'navdrawer-backdrop',
      OPEN: 'navdrawer-open',
      SHOW: 'show'
    };
    var Default = {
      breakpoint: '',
      keyboard: true,
      show: true,
      type: 'default'
    };
    var DefaultType = {
      keyboard: 'boolean',
      show: 'boolean',
      type: 'string'
    };
    var Event = {
      CLICK_DATA_API: "click" + EVENT_KEY + DATA_API_KEY,
      CLICK_DISMISS: "click.dismiss" + EVENT_KEY,
      FOCUSIN: "focusin" + EVENT_KEY,
      HIDDEN: "hidden" + EVENT_KEY,
      HIDE: "hide" + EVENT_KEY,
      KEYDOWN_DISMISS: "keydown.dismiss" + EVENT_KEY,
      MOUSEDOWN_DISMISS: "mousedown.dismiss" + EVENT_KEY,
      MOUSEUP_DISMISS: "mouseup.dismiss" + EVENT_KEY,
      SHOW: "show" + EVENT_KEY,
      SHOWN: "shown" + EVENT_KEY
    };
    var Selector = {
      CONTENT: '.navdrawer-content',
      DATA_DISMISS: '[data-dismiss="navdrawer"]',
      DATA_TOGGLE: '[data-toggle="navdrawer"]' // <<< constants

    };

    var NavDrawer =
    /*#__PURE__*/
    function () {
      function NavDrawer(element, config) {
        this._backdrop = null;
        this._config = this._getConfig(config);
        this._content = $$$1(element).find(Selector.CONTENT)[0];
        this._element = element;
        this._ignoreBackdropClick = false;
        this._isShown = false;
        this._typeBreakpoint = this._config.breakpoint === '' ? '' : "-" + this._config.breakpoint;
      }

      var _proto = NavDrawer.prototype;

      _proto.hide = function hide(event) {
        var _this = this;

        if (event) {
          event.preventDefault();
        }

        if (this._isTransitioning || !this._isShown) {
          return;
        }

        var hideEvent = $$$1.Event(Event.HIDE);
        $$$1(this._element).trigger(hideEvent);

        if (!this._isShown || hideEvent.isDefaultPrevented()) {
          return;
        }

        this._isShown = false;
        this._isTransitioning = true;

        this._setEscapeEvent();

        $$$1(document).off(Event.FOCUSIN);
        $$$1(document.body).removeClass(ClassName.OPEN + "-" + this._config.type + this._typeBreakpoint);
        $$$1(this._element).removeClass(ClassName.SHOW);
        $$$1(this._element).off(Event.CLICK_DISMISS);
        $$$1(this._content).off(Event.MOUSEDOWN_DISMISS);
        var transitionDuration = Util.getTransitionDurationFromElement(this._content);
        $$$1(this._content).one(Util.TRANSITION_END, function (event) {
          return _this._hideNavdrawer(event);
        }).emulateTransitionEnd(transitionDuration);

        this._showBackdrop();
      };

      _proto.show = function show(relatedTarget) {
        var _this2 = this;

        if (this._isTransitioning || this._isShown) {
          return;
        }

        this._isTransitioning = true;
        var showEvent = $$$1.Event(Event.SHOW, {
          relatedTarget: relatedTarget
        });
        $$$1(this._element).trigger(showEvent);

        if (this._isShown || showEvent.isDefaultPrevented()) {
          return;
        }

        this._isShown = true;

        this._setEscapeEvent();

        $$$1(this._element).addClass(NAME + "-" + this._config.type + this._typeBreakpoint);
        $$$1(this._element).on(Event.CLICK_DISMISS, Selector.DATA_DISMISS, function (event) {
          return _this2.hide(event);
        });
        $$$1(this._content).on(Event.MOUSEDOWN_DISMISS, function () {
          $$$1(_this2._element).one(Event.MOUSEUP_DISMISS, function (event) {
            if ($$$1(event.target).is(_this2._element)) {
              _this2._ignoreBackdropClick = true;
            }
          });
        });

        this._showBackdrop();

        this._showElement(relatedTarget);
      };

      _proto.toggle = function toggle(relatedTarget) {
        return this._isShown ? this.hide() : this.show(relatedTarget);
      };

      _proto._enforceFocus = function _enforceFocus() {
        var _this3 = this;

        $$$1(document).off(Event.FOCUSIN).on(Event.FOCUSIN, function (event) {
          if (document !== event.target && _this3._element !== event.target && $$$1(_this3._element).has(event.target).length === 0) {
            _this3._element.focus();
          }
        });
      };

      _proto._getConfig = function _getConfig(config) {
        config = _objectSpread({}, Default, config);
        Util.typeCheckConfig(NAME, config, DefaultType);
        return config;
      };

      _proto._hideNavdrawer = function _hideNavdrawer() {
        this._element.style.display = 'none';

        this._element.setAttribute('aria-hidden', true);

        this._isTransitioning = false;
        $$$1(this._element).trigger(Event.HIDDEN);
      };

      _proto._removeBackdrop = function _removeBackdrop() {
        if (this._backdrop) {
          $$$1(this._backdrop).remove();
          this._backdrop = null;
        }
      };

      _proto._setEscapeEvent = function _setEscapeEvent() {
        var _this4 = this;

        if (this._isShown && this._config.keyboard) {
          $$$1(this._element).on(Event.KEYDOWN_DISMISS, function (event) {
            if (event.which === ESCAPE_KEYCODE) {
              event.preventDefault();

              _this4.hide();
            }
          });
        } else if (!this._isShown) {
          $$$1(this._element).off(Event.KEYDOWN_DISMISS);
        }
      };

      _proto._showBackdrop = function _showBackdrop() {
        var _this5 = this;

        if (this._isShown) {
          this._backdrop = document.createElement('div');
          $$$1(this._backdrop).addClass(ClassName.BACKDROP).addClass(ClassName.BACKDROP + "-" + this._config.type + this._typeBreakpoint).appendTo(document.body);
          $$$1(this._element).on(Event.CLICK_DISMISS, function (event) {
            if (_this5._ignoreBackdropClick) {
              _this5._ignoreBackdropClick = false;
              return;
            }

            if (event.target !== event.currentTarget) {
              return;
            }

            _this5.hide();
          });
          Util.reflow(this._backdrop);
          $$$1(this._backdrop).addClass(ClassName.SHOW);
        } else if (!this._isShown && this._backdrop) {
          $$$1(this._backdrop).removeClass(ClassName.SHOW);

          this._removeBackdrop();
        }
      };

      _proto._showElement = function _showElement(relatedTarget) {
        var _this6 = this;

        if (!this._element.parentNode || this._element.parentNode.nodeType !== Node.ELEMENT_NODE) {
          document.body.appendChild(this._element);
        }

        this._element.style.display = 'block';

        this._element.removeAttribute('aria-hidden');

        Util.reflow(this._element);
        $$$1(document.body).addClass(ClassName.OPEN + "-" + this._config.type + this._typeBreakpoint);
        $$$1(this._element).addClass(ClassName.SHOW);

        this._enforceFocus();

        var shownEvent = $$$1.Event(Event.SHOWN, {
          relatedTarget: relatedTarget
        });

        var transitionComplete = function transitionComplete() {
          _this6._element.focus();

          _this6._isTransitioning = false;
          $$$1(_this6._element).trigger(shownEvent);
        };

        var transitionDuration = Util.getTransitionDurationFromElement(this._content);
        $$$1(this._content).one(Util.TRANSITION_END, transitionComplete).emulateTransitionEnd(transitionDuration);
      };

      NavDrawer._jQueryInterface = function _jQueryInterface(config, relatedTarget) {
        return this.each(function () {
          var _config = _objectSpread({}, Default, $$$1(this).data(), typeof config === 'object' && config ? config : {});

          var data = $$$1(this).data(DATA_KEY);

          if (!data) {
            data = new NavDrawer(this, _config);
            $$$1(this).data(DATA_KEY, data);
          }

          if (typeof config === 'string') {
            if (typeof data[config] === 'undefined') {
              throw new TypeError("No method named \"" + config + "\"");
            }

            data[config](relatedTarget);
          } else if (_config.show) {
            data.show(relatedTarget);
          }
        });
      };

      _createClass(NavDrawer, null, [{
        key: "Default",
        get: function get() {
          return Default;
        }
      }]);

      return NavDrawer;
    }();

    $$$1(document).on(Event.CLICK_DATA_API, Selector.DATA_TOGGLE, function (event) {
      var _this7 = this;

      var selector = Util.getSelectorFromElement(this);
      var target;

      if (selector) {
        target = $$$1(selector)[0];
      }

      var config = $$$1(target).data(DATA_KEY) ? 'toggle' : _objectSpread({}, $$$1(target).data(), $$$1(this).data());

      if (this.tagName === 'A' || this.tagName === 'AREA') {
        event.preventDefault();
      }

      var $target = $$$1(target).one(Event.SHOW, function (showEvent) {
        if (showEvent.isDefaultPrevented()) {
          return;
        }

        $target.one(Event.HIDDEN, function () {
          if ($$$1(_this7).is(':visible')) {
            _this7.focus();
          }
        });
      });

      NavDrawer._jQueryInterface.call($$$1(target), config, this);
    });
    $$$1.fn[NAME] = NavDrawer._jQueryInterface;
    $$$1.fn[NAME].Constructor = NavDrawer;

    $$$1.fn[NAME].noConflict = function () {
      $$$1.fn[NAME] = NO_CONFLICT;
      return NavDrawer._jQueryInterface;
    };

    return NavDrawer;
  }($);

  exports.Util = Util;
  exports.ExpansionPanel = ExpansionPanel;
  exports.FloatingLabel = FloatingLabel;
  exports.NavDrawer = NavDrawer;

  Object.defineProperty(exports, '__esModule', { value: true });

})));
