// source: https://github.com/MartinF/jQuery.Autosize.Input

var Plugins;
(function (Plugins) {
    var AutosizeInput = (function () {
        function AutosizeInput(input, options) {
            var _this = this;
            this._input = $(input);
            this._options = options;
            this._mirror = $('<span style="position:absolute; top:-999px; left:0; white-space:pre;"/>');
            $.each([
                'fontFamily',
                'fontSize',
                'fontWeight',
                'fontStyle',
                'letterSpacing',
                'textTransform',
                'wordSpacing',
                'textIndent'
            ], function (i, val) {
                _this._mirror[0].style[val] = _this._input.css(val);
            });
            $("body").append(this._mirror);
            this._input.bind("keydown input", function (e) {
                _this.update();
            });
            (function () {
                _this.update();
            })();
        }
        Object.defineProperty(AutosizeInput.prototype, "options", {
            get: function () {
                return this._options;
            },
            enumerable: true,
            configurable: true
        });
        Object.defineProperty(AutosizeInput, "instanceKey", {
            get: function () {
                return "autosizeInputInstance";
            },
            enumerable: true,
            configurable: true
        });
        AutosizeInput.prototype.update = function () {
            var value = this._input.val();
            if(!value) {
                value = this._input.attr("placeholder");
            }
            if(value === this._mirror.text()) {
                return;
            }
            this._mirror.text(value);
            var newWidth = this._mirror.width() + this._options.space;
            this._input.width(newWidth);
        };
        return AutosizeInput;
    })();
    Plugins.AutosizeInput = AutosizeInput;
    var AutosizeInputOptions = (function () {
        function AutosizeInputOptions(space) {
            if (typeof space === "undefined") { space = 30; }
            this._space = space;
        }
        Object.defineProperty(AutosizeInputOptions.prototype, "space", {
            get: function () {
                return this._space;
            },
            set: function (value) {
                this._space = value;
            },
            enumerable: true,
            configurable: true
        });
        return AutosizeInputOptions;
    })();
    Plugins.AutosizeInputOptions = AutosizeInputOptions;
    (function ($) {
        var pluginDataAttributeName = "autosize-input";
        var validTypes = [
            "text",
            "password",
            "search",
            "url",
            "tel",
            "email"
        ];
        $.fn.autosizeInput = function (options) {
            return this.each(function () {
                if(!(this.tagName == "INPUT" && $.inArray(this.type, validTypes) > -1)) {
                    return;
                }
                var $this = $(this);
                if(!$this.data(Plugins.AutosizeInput.instanceKey)) {
                    if(options == undefined) {
                        var options = $this.data(pluginDataAttributeName);
                        if(!(options && typeof options == 'object')) {
                            options = new AutosizeInputOptions();
                        }
                    }
                    $this.data(Plugins.AutosizeInput.instanceKey, new Plugins.AutosizeInput(this, options));
                }
            });
        };
        $(function () {
            $("input[data-" + pluginDataAttributeName + "]").autosizeInput();
        });
    })(jQuery);
})(Plugins || (Plugins = {}));

