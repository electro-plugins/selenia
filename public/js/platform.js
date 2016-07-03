$('input').focus(function () {
  $(this).select();
});

/*--------------------------------------------------------------------
 JQUERY EXTENSIONS
 --------------------------------------------------------------------*/

$.fn.bindInputToSetting = function (key) {
  var e = $ (this);
  switch (e.attr ('type')) {
    case 'checkbox':
      e.change (function () { mem.set (key, e.prop ('checked')) })
        .prop ('checked', mem.get (key));
      break;
    case 'text':
      e.change (function () { mem.set (key, e.val ()) })
        .val (mem.get (key));
      break;
  }
};

// Prevent errors from old plugins.
$.browser = {};

/*--------------------------------------------------------------------
 MISC
 --------------------------------------------------------------------*/

function bindInputsToSettings (map) {
  Object.keys (map).forEach (function (k) {
    $ (k).bindInputToSetting (map[k]);
  });
}

/**
 * Transforms an interpolated string into an expression that can be evaluated by `eval()`.
 *
 * Usage:&nbsp; <code>eval (tpl ('text ${exp} text'))</code>
 *
 * Embedded expressions can be any valid javascript expressions, referencing any javascript variable in scope.
 * @type {function(string):string}
 */
var tpl = (function () {
  var last, cache;

  return function (str) {
    if (str == last) return cache;
    last = str;
    return cache = "'" + str.replace (/\$\{(.*?)}/g, function (m, exp) {
        return "'+(" + exp + ")+'"
      }) + "'";
  }
}) ();

/**
 * Event handler that posts a checkbox toggle via XHR.
 * @param ev
 * @param id
 * @param action
 */
function check (ev, id, action) {
  action = action || 'check';
  ev.stopPropagation ();
  $.post (location.href, { _action: action, id: id });
}

/*--------------------------------------------------------------------
 LOCAL STORAGE
 --------------------------------------------------------------------*/

var mem = {
  listeners: {},

  init: function () {
    $ (window).on ('storage', function (ev) {
      this.onChange (ev.key, this.get (ev.key));
    }.bind (this));
  },

  onChange: function (key, val) {
    var list = this.listeners[key];
    if (list && list.length)
      list.forEach (function (l) { l (val) });
  },

  get: function (key, defaultVal) {
    var v = localStorage[key];
    if (v === undefined)
      return defaultVal !== undefined ? this.set (key, defaultVal) : null;
    return JSON.parse (v);
  },

  set: function (key, val) {
    var g = key.lastIndexOf ('.');
    if (g >= 0) {
      var group = key.substr (0, g);
      var k     = key.substr (g + 1);
      var keys  = JSON.parse (localStorage[group] || '{}');
      if (!keys[k]) {
        keys[k]             = 1;
        localStorage[group] = JSON.stringify (keys);
      }
    }
    var s = JSON.stringify (val);
    if (localStorage[key] != s) {
      localStorage[key] = s;
      this.onChange (key, val);
    }
    return val;
  },

  getGroup: function (group) {
    var keys = this.get (group, {});
    var o    = {};
    for (var k in keys)
      if (keys.hasOwnProperty (k))
        o[k] = JSON.parse (localStorage[group + '.' + k] || '{}');
    return o;
  },

  setGroup: function (group, obj) {
    var keys = JSON.parse (localStorage[group] || '{}');
    for (var k in obj)
      if (obj.hasOwnProperty (k)) {
        localStorage[group + '.' + k] = JSON.stringify (obj[k]);
        keys[k]                       = 1;
      }
    localStorage[group] = JSON.stringify (keys);
  },

  listen: function (key, handler) {
    (this.listeners [key] = (this.listeners [key] || [])).push (handler);
  }
};

/*--------------------------------------------------------------------

 --------------------------------------------------------------------*/
