function initTooltips() {
    $("[rel='tooltip'][data-trigger]").tooltip({
        template: '<div class="tooltip jewlr-tooltip"><div class="tooltip-arrow"></div><div class="tooltip-inner"></div></div>'
    });
}

function number_with_delimiter(a, b, c) {
    try {
        var b = b || ",",
            c = c || ".",
            d = a.toString().split(".");
        return d[0] = d[0].replace(/(\d)(?=(\d\d\d)+(?!\d))/g, "$1" + b),
            d.join(c)
    } catch (e) {
        return a
    }
}
(function (a, b) {
    var c = function () {
        var b = a._data(document, "events");
        return b && b.click && a.grep(b.click,
                function (a) {
                    return a.namespace === "rails"
                }).length
    };
    c() && a.error("jquery-ujs has already been loaded!");
    var d;
    a.rails = d = {
        linkClickSelector: "a[data-confirm], a[data-method], a[data-remote], a[data-disable-with]",
        inputChangeSelector: "select[data-remote], input[data-remote], textarea[data-remote]",
        formSubmitSelector: "form",
        formInputClickSelector: "form input[type=submit], form input[type=image], form button[type=submit], form button:not([type])",
        disableSelector: "input[data-disable-with], button[data-disable-with], textarea[data-disable-with]",
        enableSelector: "input[data-disable-with]:disabled, button[data-disable-with]:disabled, textarea[data-disable-with]:disabled",
        requiredInputSelector: "input[name][required]:not([disabled]),textarea[name][required]:not([disabled])",
        fileInputSelector: "input[type=file]",
        linkDisableSelector: "a[data-disable-with]",
        CSRFProtection: function (b) {
            var c = a('meta[name="csrf-token"]').attr("content");
            c && b.setRequestHeader("X-CSRF-Token", c)
        },
        fire: function (b, c, d) {
            var e = a.Event(c);
            return b.trigger(e, d),
            e.result !== !1
        },
        confirm: function (a) {
            return confirm(a)
        },
        ajax: function (b) {
            return a.ajax(b)
        },
        href: function (a) {
            return a.attr("href")
        },
        handleRemote: function (c) {
            var e, f, g, h, i, j, k, l;
            if (d.fire(c, "ajax:before")) {
                h = c.data("cross-domain"),
                    i = h === b ? null : h,
                    j = c.data("with-credentials") || null,
                    k = c.data("type") || a.ajaxSettings && a.ajaxSettings.dataType;
                if (c.is("form")) {
                    e = c.attr("method"),
                        f = c.attr("action"),
                        g = c.serializeArray();
                    var m = c.data("ujs:submit-button");
                    m && (g.push(m), c.data("ujs:submit-button", null))
                } else c.is(d.inputChangeSelector) ? (e = c.data("method"), f = c.data("url"), g = c.serialize(), c.data("params") && (g = g + "&" + c.data("params"))) : (e = c.data("method"), f = d.href(c), g = c.data("params") || null);
                l = {
                    type: e || "GET",
                    data: g,
                    dataType: k,
                    beforeSend: function (a, e) {
                        return e.dataType === b && a.setRequestHeader("accept", "*/*;q=0.5, " + e.accepts.script),
                            d.fire(c, "ajax:beforeSend", [a, e])
                    },
                    success: function (a, b, d) {
                        c.trigger("ajax:success", [a, b, d])
                    },
                    complete: function (a, b) {
                        c.trigger("ajax:complete", [a, b])
                    },
                    error: function (a, b, d) {
                        c.trigger("ajax:error", [a, b, d])
                    },
                    crossDomain: i
                },
                j && (l.xhrFields = {
                    withCredentials: j
                }),
                f && (l.url = f);
                var n = d.ajax(l);
                return c.trigger("ajax:send", n),
                    n
            }
            return !1
        },
        handleMethod: function (c) {
            var e = d.href(c),
                f = c.data("method"),
                g = c.attr("target"),
                h = a("meta[name=csrf-token]").attr("content"),
                i = a("meta[name=csrf-param]").attr("content"),
                j = a('<form method="post" action="' + e + '"></form>'),
                k = '<input name="_method" value="' + f + '" type="hidden" />';
            i !== b && h !== b && (k += '<input name="' + i + '" value="' + h + '" type="hidden" />'),
            g && j.attr("target", g),
                j.hide().append(k).appendTo("body"),
                j.submit()
        },
        disableFormElements: function (b) {
            b.find(d.disableSelector).each(function () {
                var b = a(this),
                    c = b.is("button") ? "html" : "val";
                b.data("ujs:enable-with", b[c]()),
                    b[c](b.data("disable-with")),
                    b.prop("disabled", !0)
            })
        },
        enableFormElements: function (b) {
            b.find(d.enableSelector).each(function () {
                var b = a(this),
                    c = b.is("button") ? "html" : "val";
                b.data("ujs:enable-with") && b[c](b.data("ujs:enable-with")),
                    b.prop("disabled", !1)
            })
        },
        allowAction: function (a) {
            var b = a.data("confirm"),
                c = !1,
                e;
            return b ? (d.fire(a, "confirm") && (c = d.confirm(b), e = d.fire(a, "confirm:complete", [c])), c && e) : !0
        },
        blankInputs: function (b, c, d) {
            var e = a(),
                f,
                g,
                h = c || "input,textarea",
                i = b.find(h);
            return i.each(function () {
                f = a(this),
                    g = f.is("input[type=checkbox],input[type=radio]") ? f.is(":checked") : f.val();
                if (!g == !d) {
                    if (f.is("input[type=radio]") && i.filter('input[type=radio]:checked[name="' + f.attr("name") + '"]').length) return !0;
                    e = e.add(f)
                }
            }),
                e.length ? e : !1
        },
        nonBlankInputs: function (a, b) {
            return d.blankInputs(a, b, !0)
        },
        stopEverything: function (b) {
            return a(b.target).trigger("ujs:everythingStopped"),
                b.stopImmediatePropagation(),
                !1
        },
        callFormSubmitBindings: function (c, d) {
            var e = c.data("events"),
                f = !0;
            return e !== b && e.submit !== b && a.each(e.submit,
                function (a, b) {
                    if (typeof b.handler == "function") return f = b.handler(d)
                }),
                f
        },
        disableElement: function (a) {
            a.data("ujs:enable-with", a.html()),
                a.html(a.data("disable-with")),
                a.bind("click.railsDisable",
                    function (a) {
                        return d.stopEverything(a)
                    })
        },
        enableElement: function (a) {
            a.data("ujs:enable-with") !== b && (a.html(a.data("ujs:enable-with")), a.data("ujs:enable-with", !1)),
                a.unbind("click.railsDisable")
        }
    },
    d.fire(a(document), "rails:attachBindings") && (a.ajaxPrefilter(function (a, b, c) {
        a.crossDomain || d.CSRFProtection(c)
    }), a(document).delegate(d.linkDisableSelector, "ajax:complete",
        function () {
            d.enableElement(a(this))
        }), a(document).delegate(d.linkClickSelector, "click.rails",
        function (c) {
            var e = a(this),
                f = e.data("method"),
                g = e.data("params");
            if (!d.allowAction(e)) return d.stopEverything(c);
            e.is(d.linkDisableSelector) && d.disableElement(e);
            if (e.data("remote") !== b) {
                if ((c.metaKey || c.ctrlKey) && (!f || f === "GET") && !g) return !0;
                var h = d.handleRemote(e);
                return h === !1 ? d.enableElement(e) : h.error(function () {
                    d.enableElement(e)
                }),
                    !1
            }
            if (e.data("method")) return d.handleMethod(e),
                !1
        }), a(document).delegate(d.inputChangeSelector, "change.rails",
        function (b) {
            var c = a(this);
            return d.allowAction(c) ? (d.handleRemote(c), !1) : d.stopEverything(b)
        }), a(document).delegate(d.formSubmitSelector, "submit.rails",
        function (c) {
            var e = a(this),
                f = e.data("remote") !== b,
                g = d.blankInputs(e, d.requiredInputSelector),
                h = d.nonBlankInputs(e, d.fileInputSelector);
            if (!d.allowAction(e)) return d.stopEverything(c);
            if (g && e.attr("novalidate") == b && d.fire(e, "ajax:aborted:required", [g])) return d.stopEverything(c);
            if (f) {
                if (h) {
                    setTimeout(function () {
                            d.disableFormElements(e)
                        },
                        13);
                    var i = d.fire(e, "ajax:aborted:file", [h]);
                    return i || setTimeout(function () {
                            d.enableFormElements(e)
                        },
                        13),
                        i
                }
                return !a.support.submitBubbles && a().jquery < "1.7" && d.callFormSubmitBindings(e, c) === !1 ? d.stopEverything(c) : (d.handleRemote(e), !1)
            }
            setTimeout(function () {
                    d.disableFormElements(e)
                },
                13)
        }), a(document).delegate(d.formInputClickSelector, "click.rails",
        function (b) {
            var c = a(this);
            if (!d.allowAction(c)) return d.stopEverything(b);
            var e = c.attr("name"),
                f = e ? {
                    name: e,
                    value: c.val()
                } : null;
            c.closest("form").data("ujs:submit-button", f)
        }), a(document).delegate(d.formSubmitSelector, "ajax:beforeSend.rails",
        function (b) {
            this == b.target && d.disableFormElements(a(this))
        }), a(document).delegate(d.formSubmitSelector, "ajax:complete.rails",
        function (b) {
            this == b.target && d.enableFormElements(a(this))
        }), a(function () {
        var b = a("meta[name=csrf-token]").attr("content"),
            c = a("meta[name=csrf-param]").attr("content");
        a('form input[name="' + c + '"]').val(b)
    }))
})(jQuery),


    !
        function (a) {
            "use strict";
            var b = function (a, b) {
                this.init("tooltip", a, b)
            };
            b.prototype = {
                constructor: b,
                init: function (b, c, d) {
                    var e, f, g, h, i;
                    this.type = b,
                        this.$element = a(c),
                        this.options = this.getOptions(d),
                        this.enabled = !0,
                        g = this.options.trigger.split(" ");
                    for (i = g.length; i--;) h = g[i],
                        h == "click" ? this.$element.on("click." + this.type, this.options.selector, a.proxy(this.toggle, this)) : h != "manual" && (e = h == "hover" ? "mouseenter" : "focus", f = h == "hover" ? "mouseleave" : "blur", this.$element.on(e + "." + this.type, this.options.selector, a.proxy(this.enter, this)), this.$element.on(f + "." + this.type, this.options.selector, a.proxy(this.leave, this)));
                    this.options.selector ? this._options = a.extend({},
                        this.options, {
                            trigger: "manual",
                            selector: ""
                        }) : this.fixTitle()
                },
                getOptions: function (b) {
                    return b = a.extend({},
                        a.fn[this.type].defaults, this.$element.data(), b),
                    b.delay && typeof b.delay == "number" && (b.delay = {
                        show: b.delay,
                        hide: b.delay
                    }),
                        b
                },
                enter: function (b) {
                    var c = a.fn[this.type].defaults,
                        d = {},
                        e;
                    this._options && a.each(this._options,
                        function (a, b) {
                            c[a] != b && (d[a] = b)
                        },
                        this),
                        e = a(b.currentTarget)[this.type](d).data(this.type);
                    if (!e.options.delay || !e.options.delay.show) return e.show();
                    clearTimeout(this.timeout),
                        e.hoverState = "in",
                        this.timeout = setTimeout(function () {
                                e.hoverState == "in" && e.show()
                            },
                            e.options.delay.show)
                },
                leave: function (b) {
                    var c = a(b.currentTarget)[this.type](this._options).data(this.type);
                    this.timeout && clearTimeout(this.timeout);
                    if (!c.options.delay || !c.options.delay.hide) return c.hide();
                    c.hoverState = "out",
                        this.timeout = setTimeout(function () {
                                c.hoverState == "out" && c.hide()
                            },
                            c.options.delay.hide)
                },
                show: function () {

                    var b, c, d, e, f, g, h = a.Event("show");
                    if (this.hasContent() && this.enabled) {
                        this.$element.trigger(h);
                        if (h.isDefaultPrevented()) return;
                        b = this.tip(),
                            this.setContent(),
                        this.options.animation && b.addClass("fade"),
                            f = typeof this.options.placement == "function" ? this.options.placement.call(this, b[0], this.$element[0]) : this.options.placement,
                            b.detach().css({
                                top: 0,
                                left: 0,
                                display: "block"
                            }),
                            this.options.container ? b.appendTo(this.options.container) : b.insertAfter(this.$element),
                            c = this.getPosition(),
                            d = b[0].offsetWidth,
                            e = b[0].offsetHeight;

                        switch (f) {
                            case "bottom":
                                g = {
                                    top: c.top + c.height,
                                    left: c.left + c.width / 2 - d / 2
                                };
                                break;
                            case "top":

                                g = {
                                    top: c.top - e,
                                    left: c.left + c.width / 2 - d / 2
                                };
                                break;
                            case "left":
                                g = {
                                    top: c.top + c.height / 2 - e / 2,
                                    left: c.left - d
                                };
                                break;
                            case "right":
                                g = {
                                    top: c.top + c.height / 2 - e / 2,
                                    left: c.left + c.width
                                }
                        }
                        this.applyPlacement(g, f),
                            this.$element.trigger("shown")
                    }
                },
                applyPlacement: function (a, b) {
                    var c = this.tip(),
                        d = c[0].offsetWidth,
                        e = c[0].offsetHeight,
                        f,
                        g,
                        h,
                        i;
                    c.offset(a).addClass(b).addClass("in"),
                        f = c[0].offsetWidth,
                        g = c[0].offsetHeight,
                    b == "top" && g != e && (a.top = a.top + e - g, i = !0),
                        b == "bottom" || b == "top" ? (h = 0, a.left < 0 && (h = a.left * -2, a.left = 0, c.offset(a), f = c[0].offsetWidth, g = c[0].offsetHeight), this.replaceArrow(h - d + f, f, "left")) : this.replaceArrow(g - e, g, "top"),
                    i && c.offset(a)
                },
                replaceArrow: function (a, b, c) {
                    this.arrow().css(c, a ? 50 * (1 - a / b) + "%" : "")
                },
                setContent: function () {
                    var a = this.tip(),
                        b = this.getTitle();
                    a.find(".tooltip-inner")[this.options.html ? "html" : "text"](b),
                        a.removeClass("fade in top bottom left right")
                },
                hide: function () {
                    function e() {
                        var b = setTimeout(function () {
                                c.off(a.support.transition.end).detach()
                            },
                            500);
                        c.one(a.support.transition.end,
                            function () {
                                clearTimeout(b),
                                    c.detach()
                            })
                    }

                    var b = this,
                        c = this.tip(),
                        d = a.Event("hide");
                    this.$element.trigger(d);
                    if (d.isDefaultPrevented()) return;
                    return c.removeClass("in"),
                        a.support.transition && this.$tip.hasClass("fade") ? e() : c.detach(),
                        this.$element.trigger("hidden"),
                        this
                },
                fixTitle: function () {
                    var a = this.$element;
                    (a.attr("title") || typeof a.attr("data-original-title") != "string") && a.attr("data-original-title", a.attr("title") || "").attr("title", "")
                },
                hasContent: function () {
                    return this.getTitle()
                },
                getPosition: function () {
                    var b = this.$element[0];
                    return a.extend({},
                        typeof b.getBoundingClientRect == "function" ? b.getBoundingClientRect() : {
                            width: b.offsetWidth,
                            height: b.offsetHeight
                        },
                        this.$element.offset())
                },
                getTitle: function () {
                    var a, b = this.$element,
                        c = this.options;
                    return a = b.attr("data-original-title") || (typeof c.title == "function" ? c.title.call(b[0]) : c.title),
                        a
                },
                tip: function () {
                    return this.$tip = this.$tip || a(this.options.template)
                },
                arrow: function () {
                    return this.$arrow = this.$arrow || this.tip().find(".tooltip-arrow")
                },
                validate: function () {
                    this.$element[0].parentNode || (this.hide(), this.$element = null, this.options = null)
                },
                enable: function () {
                    this.enabled = !0
                },
                disable: function () {
                    this.enabled = !1
                },
                toggleEnabled: function () {
                    this.enabled = !this.enabled
                },
                toggle: function (b) {
                    var c = b ? a(b.currentTarget)[this.type](this._options).data(this.type) : this;
                    c.tip().hasClass("in") ? c.hide() : c.show()
                },
                destroy: function () {
                    this.hide().$element.off("." + this.type).removeData(this.type)
                }
            };
            var c = a.fn.tooltip;
            a.fn.tooltip = function (c) {
                return this.each(function () {
                    var d = a(this),
                        e = d.data("tooltip"),
                        f = typeof c == "object" && c;
                    e || d.data("tooltip", e = new b(this, f)),
                    typeof c == "string" && e[c]()
                })
            },
                a.fn.tooltip.Constructor = b,
                a.fn.tooltip.defaults = {
                    animation: !0,
                    placement: "top",
                    selector: !1,
                    template: '<div class="tooltip"><div class="tooltip-arrow"></div><div class="tooltip-inner"></div></div>',
                    trigger: "hover focus",
                    title: "",
                    delay: 0,
                    html: !1,
                    container: !1
                },
                a.fn.tooltip.noConflict = function () {
                    return a.fn.tooltip = c,
                        this
                }
        }(window.jQuery);
window.jQuery &&
function (a) {
    a.fn.mailcheck = function (a) {
        var b = this;
        if (a.suggested) {
            var c = a.suggested;
            a.suggested = function (a) {
                c(b, a)
            }
        }
        if (a.empty) {
            var d = a.empty;
            a.empty = function () {
                d.call(null, b)
            }
        }
        a.email = this.val(),
            Kicksend.mailcheck.run(a)
    }
}(jQuery),
    $(document).ready(function () {
        initTooltips(),
            $("#jewlrModal").on("hidden",
                function (a) {
                    $(".modal-body", $(this)).html("")
                })
    }),
    String.prototype.trim = function () {
        return this.replace(/^\s+|\s+$/g, "")
    },
    String.prototype.ltrim = function () {
        return this.replace(/^\s+/, "")
    },
    String.prototype.rtrim = function () {
        return this.replace(/\s+$/, "")
    },
    String.prototype.titleCase = function () {
        var a = "",
            b = this.replace("_", " ").split(" ");
        for (keyvar in b) a += " " + b[keyvar].substr(0, 1).toUpperCase() + b[keyvar].substr(1, b[keyvar].length).toLowerCase();
        return a.ltrim()
    }