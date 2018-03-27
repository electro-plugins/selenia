function nop () {}

/**
* Provides string interpolation for ES5 browsers.
* Usage: eval($$("expression"))
* @param {string} exp A string with interpolated javascript expresssions.
* Ex: "You have ${n} apples for ${price(n)+1} EUR"
* @returns {string} The resulting string is a javascript expression that can be evaluated on the
* current javascript scope using eval().
*/
function $$ (exp)
{
  return "'" + exp.replace (/'/g, "\\'").replace (/\$\{(.*?)\}/g, "'+($1)+'") + "'";
}

!(function () {
  if (!('$' in window))
    return console.error ("jQuery is required");

  $('input').focus(function () {
    $(this).select();
  });

  // Private vars

  var
    /** Pub/Sub topics registry */
    topics = {},
    /** The #selenia-form jQuery element */
    form;

  // The Selenia API

  window.selenia = {

    prevFocus: $ (),
    lang:      '',
    /**
     * Extensions and components plug-in into this namespace.
     */
    ext:       {},

    /**
     * Selenia's Pub-Sub system.
     * @param {string} id Topic ID.
     * @returns {Object} A topic interface.
     */
    topic: function (id) {
      var callbacks
        , topic = id && topics[id];

      if (!topic) {
        callbacks = jQuery.Callbacks ();
        topic     = {
          send:        callbacks.fire,
          subscribe:   callbacks.add,
          unsubscribe: callbacks.remove
        };
        if (id)
          topics[id] = topic;
      }
      return topic;
    },

    /**
     * Listens for a Selenia client-side event.
     * @param topic
     * @param handler
     * @returns {selenia}
     */
    on: function (topic, handler) {
      this.topic (topic).subscribe (handler);
      return this;
    },

    /**
     * Sets the POST action and submits the form.
     * @param {string} name
     * @param {string} param
     */
    doAction: function (name, param) {
      selenia.setAction (name, param);
      form.submit ();
    },

    /**
     * Sets the POST action for later submission.
     * @param {string} name
     * @param {string} param
     */
    setAction: function (name, param) {
			form.find ('input[name=selenia-action]').val (name + (param ? ':' + param : ''));
    },

    /**
     * Returns the POST action currently set for submission.
     * @returns {*}
     */
    getAction: function () {
      return form.find ('input[name=selenia-action]').val ().split (':')[0];
    },

    /**
     * Is invoked before #selenia-form is submitted.
     * Return false to cancel the submission.
     * @param {Event} ev
     * @returns {boolean|*}
     */
    onSubmit: function (ev) {
      // Re-enable all buttons if form sbmission is aborted.
      setTimeout (function () {
        if (ev.isDefaultPrevented ())
          selenia.enableButtons (true);
      });
      // Disable all buttons while for is being submitted.
      selenia.enableButtons (false);
      return selenia.getAction () != 'submit' || selenia.validateForm ();
    },

    /**
     * Validates all form inputs that have validation rules.
     * @returns {boolean} true if the for is valid and can be submitted.
     */
    validateForm: function () {
      var i18nInputs = $ ('input[lang]');
      i18nInputs.addClass ('validating'); // hide inputs but allow field validation

      // HTML5 native validation integration.
      // Note: validateInput() is provided by the Input component.
      var inputs = $ ('input,textarea,select');
      inputs.each (function () {
        $ (this).parents ('.Field').find ('.help-block').remove ();
      });
      if (selenia.validateInput)
        inputs.each (function () { selenia.validateInput (this) });
      var valid = form[0].checkValidity ();
      if (!valid) {
        var first = true;
        setTimeout (function () {
          inputs.each (function () {
            if (!this.checkValidity ()) {
              if (first) {
                // Check if it's inside a tab pane
                if ($ (this).parents ('.tab-pane').length) {
                  var tabID = $ (this).parents ('.tab-pane');
                  $ ('.nav-tabs a[href="#' + tabID.attr ('id') + '"]').tab ('show');
                }
                $ (this).focus ();
                first    = false;
                var lang = $ (this).attr ('lang');
                if (lang) selenia.setLang (lang, this);
              }
              var h = $ (this).parents ('.Field').find ('.help-block');
              if (!h.length)
                h = $ (this).parents ('.Field').append ('<span class="help-block"></span>').find ('.help-block');
              h.text (this.validationMessage);
            }
          });

          var e = document.activeElement;
          i18nInputs.removeClass ('validating'); // restore display:none state
          if (!e) return;
        }, 0);
        return false;
      }
      // restore display:none state
      i18nInputs.removeClass ('validating');
      return true;
    },

    /**
     * Disables or re-enables all buttons, but re-enables only those that were not previously disabled.
     * @param {boolean} enable
     */
    enableButtons: function (enable) {
      form.find ('button,input[type=button],input[type=submit]').each (function () {
        var btn      = $ (this)
          , disabled = btn.prop ('disabled');
        if (enable) {
          if (this.wasDisabled)
            return delete this.wasDisabled;
        }
        else this.wasDisabled = disabled;
        btn.prop ('disabled', !enable);
      });
    },

    go: function (url, /*Event*/ ev) {
      var base = $ ('base').attr ('href') || '';
      window.location.assign (url[0] == '/' ? url : base + url);
      if (ev) ev.stopImmediatePropagation ();
    },

    saveScrollPos: function (form) {
      form.elements.scroll.value = document.getElementsByTagName ("HTML")[0].scrollTop
        + document.body.scrollTop;
    },

    scroll: function (y) {
      if (y == undefined) y = 9999;
      setTimeout (function () {
        document.getElementsByTagName ("HTML")[0].scrollTop = y;
        if (document.getElementsByTagName ("HTML")[0].scrollTop != y)
          document.body.scrollTop = y;
      }, 1);
    },

    /**
     * Changes the active language for multilingual form inputs.
     * @param {string} lang
     * @param {boolean} inputsGroup
     */
    setLang: function (lang, inputsGroup) {
      selenia.lang = lang;

      var c = $ ('body')
        .attr ('lang', lang); //not currently used

      c.find ('[lang]').removeClass ('active');
      c.find ('[lang="' + lang + '"]').addClass ('active');

      // Focus input being shown.
      if (inputsGroup)
        $ (inputsGroup).find ('[lang=' + lang + ']').focus ();

      else {
        // Restore the focus to the previously focused element.
        if (selenia.prevFocus.attr ('lang'))
          selenia.prevFocus.parent ().find ('[lang="' + lang + '"]').focus ();
        else selenia.prevFocus.focus ();
      }

      selenia.topic ('languageChanged').send (lang);
    }

  };

  (function initSelenia () {

    // Memorize the previously focused input.
    var body = $ ('body')
      .focusout (function (ev) {
        if (ev.target.tagName == 'INPUT' || ev.target.tagName == 'TEXTAREA')
          selenia.prevFocus = $ (ev.target);
        else selenia.prevFocus = $ ();
      });

    var formClass = $('body').data("formclass") ? $('body').data("formclass") : '';
    if (!$('form').length > 0)
    {
      form = $ ('<form id="selenia-form" class="'+formClass+'" method="post" action="' + location.pathname + '" novalidate></form>')
        .submit (selenia.onSubmit)
        .append ('<input type="hidden" name="selenia-action" value="submit">')
        .append (body.children (':not(script)'))
        .prependTo (body);
    }
    else
    {
      form = $('form#selenia-form');
      if (form.length)
				form.prepend('<input type="hidden" name="selenia-action" value="submit">');
    }
  }) ();

})();

/*--------------------------------------------------------------------
 LOCAL STORAGE API
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
