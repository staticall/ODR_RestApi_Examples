var PAGE = new function ()
{
    var that = this;

    this.jsons = {};

    this.page     = {};
    this.timeouts = {
        "is_loading": undefined
    };
    this.template             = null;
    this.template_home        = null;
    this.template_abstract    = null;
    this.template_get_started = null;
    this.default_title        = document.title;
    this.get_started          = {
        "data": {
            "now":                Math.round(new Date().getTime() / 1000),
            "user_id":            1,
            "example_code":       "https://github.com/staticall/ODR_RestApi_Examples/archive/master.zip",
            "domain_name":        "test",
            "domain_tld":         "nl",
            "contact_id":         1,
            "contact_role_id":    "nl",
            "nameserver_id":      1,
            "nameserver_role_id": "nl",
            "user":               {
                "api_key":    "#API_KEY#",
                "api_secret": "#API_SECRET#"
            }
        },
        "contact_role_id": [
            "nl",
            "be",
            "eu",
            "universal",
            "universal2"
        ],
        "nameserver_role_id": [
            "nl",
            "be",
            "eu",
            "universal",
            "universal2"
        ],
        "tlds": [
            "nl",
            "be",
            "eu",
            "net",
            "com"
        ],
        "is_val_selected": function () {
            return function (text, scope) {
                if (PAGE.getGetStartedData().data.domain_tld === scope) {
                    return ' selected="selected"';
                }

                return '';
            };
        },
        "is_val_selected_cnt_role": function () {
            return function (text, scope) {
                if (PAGE.getGetStartedData().data.domain_tld === scope) {
                    return ' selected="selected"';
                }

                return '';
            };
        },
        "is_val_selected_ns_role": function () {
            return function (text, scope) {
                if (PAGE.getGetStartedData().data.ns_role_id === scope) {
                    return ' selected="selected"';
                }

                return '';
            };
        },
        "uppercase": function () {
            return function (text, scope) {
                text     = $.trim(text);
                var _txt = Hogan.compile(text);
                text     = _txt.render(scope);

                return text.toUpperCase();
            };
        }
    };
    this.is_page_loading = true;
    this.is_initialized  = false;

    this.initialize = function ()
    {
        if (this.is_initialized === true) {
            throw 'Application already initialized. Don\'t do this twice, believe me, nothing good will come out of this';
        }

        $('#main-menu a[data-loader]').click(function (e) {
            var $link = $(this);

            that.is_page_loading = true;

            document.title = $link.text() +' — '+ that.default_title;

            $('#main-menu').find('li.active').removeClass('active');

            var link_href = $link.attr('href').replace('#', '');

            if (link_href === 'home') {
                that.renderHome();

                window.location.hash = '';
            } else if (link_href === 'abstract') {
                that.renderAbstract();

                window.location.hash = 'abstract';
            } else if (link_href === 'get-started') {
                that.renderGetStarted();

                $('#back-to-top').click();

                window.location.hash = 'get-started';
            } else {
                if (typeof that.jsons[link_href] !== 'object') {
                    throw 'Unknown method';
                }

                that.page = that.jsons[link_href];
                that.renderPage();

                $('#back-to-top').click();

                $('.bs-docs-sidenav').affix({
                    offset: {
                        top: function () {
                            return $(window).width() <= 980 ? 290 : 210;
                        },
                        bottom: 270
                    }
                });
            }

            $link.parent().addClass('active');

            $('section').find('a[href^="#"]').click(function () {
                var $this = $(this);
                var href  = $this.attr('href').replace('#', '');

                var split = href.split('|');

                var hash   = split[0];
                var action = null;

                if (split.length === 2) {
                    action = split[1];
                }

                var $act = $('#main-menu').find('a[href="#'+ hash +'"]');

                if ($act.length === 0) {
                    that.renderHome();

                    return that;
                }

                $act.click();

                setTimeout(function () {
                    var $action = $('#sidebar-nav').find('a[href="#'+ action +'"]');

                    if ($action.length) {
                        $action.click();
                    } else {
                        $('#sidebar-nav > :first-child').addClass('active');
                    }
                }, 5);
            });

            e.preventDefault();

            window.location.hash = link_href;

            that.is_page_loading = false;

            return false;
        });

        $('#back-to-top').click(function () {
            $('html, body').animate({scrollTop: 0}, 'fast', function () {
                $('body').scrollspy('refresh');
            });

            return false;
        });

        var hash   = (window.location.hash).replace('#', '');
        var action = '';

        if (hash === '') {
            that.renderHome();

            return that;
        }

        var _hash_split = hash.split('|');

        hash   = _hash_split[0];
        action = _hash_split[1];

        var $act = $('#main-menu').find('a[href="#'+ hash +'"]');

        if ($act.length === 0) {
            that.renderHome();

            return that;
        }

        $act.click();

        setTimeout(function () {
            var $action = $('#sidebar-nav').find('a[href="#'+ action +'"]');

            if ($action.length) {
                $action.click();
            } else {
                $('#sidebar-nav > :first-child').addClass('active');
            }

            that.is_page_loading = false;
            that.is_initialized    = true;
        }, 1);
    };

    this.setJsons = function (jsons)
    {
        if (typeof jsons !== 'object') {
            throw 'Passed jsons variable must be an object';
        }

        that.jsons = jsons;
    };

    this.renderPage = function ()
    {
        var data = that.page;
        var tpl  = that.template;

        data = that.prefilterData(data);

        $('#current-page-content').html(tpl.render(data));

        setTimeout(function () {
            window.prettyPrint && prettyPrint();
            $('.container small abbr').popover();

            that.applySidebarLinkEvent();
        }, 5);
    };

    this.renderHome = function ()
    {
        var tpl = that.template_home;
        $('#current-page-content').html(tpl.render());
    };

    this.renderAbstract = function ()
    {
        var tpl = that.template_abstract;
        $('#current-page-content').html(tpl.render());
    };

    this.renderGetStarted = function ()
    {
        var data = that.get_started;

        var tpl  = that.template_get_started;
        var html = tpl.render(data);

        $('#current-page-content').html(html);

        setTimeout(function () {
            that.applySidebarLinkEvent();

            $('[data-select-changer-tld]').change(function () {
                var $this = $(this);

                var _tld = $.trim($this.val());

                if (_tld === '') {
                    return;
                }

                that.get_started.data.domain_tld = _tld;

                switch (_tld) {
                    case 'net':
                            that.get_started.data.contact_role_id = 'universal2';
                        break;
                    case 'com':
                            that.get_started.data.contact_role_id = 'universal';
                        break;
                    case 'eu':
                    case 'nl':
                    case 'be':
                    default:
                            that.get_started.data.contact_role_id = _tld;
                        break;

                }

                $('#main-menu li.active a').click();

                if (that.is_page_loading === false) {
                    $('body').trigger('page.get_started.tld_changed', [_tld]);
                }
            });

            $('body').bind('page.get_started.tld_changed', function (obj, tld) {
                $('[data-tld-dependable]').addClass('hide');

                $('[data-show-'+ tld +']').removeClass('hide');

                if (typeof that.timeouts.is_loading !== 'undefined') {
                    clearTimeout(that.timeouts.is_loading);
                }

                that.timeouts.is_loading = setTimeout(function () {
                    that.is_page_loading = false;
                }, 1000);
            });

            $('#modal-settings').on('show', function () {
                var $this = $(this);

                var $inp_domainname    = $this.find('#modal-settings-input-domainname');
                var $sel_domaintld     = $this.find('#modal-settings-select-domaintld');
                var $inp_userapikey    = $this.find('#modal-settings-input-userapikey');
                var $inp_userapisecret = $this.find('#modal-settings-input-userapisecret');
                var $inp_contactid     = $this.find('#modal-settings-input-contactid');
                var $inp_cnttypeid     = $this.find('#modal-settings-input-contacttypeid');
                var $inp_nameserverid  = $this.find('#modal-settings-input-nameserverid');
                var $inp_nstypeid      = $this.find('#modal-settings-input-nameservertypeid');

                $inp_domainname   .val(that.get_started.data.domain_name);
                $sel_domaintld    .val(that.get_started.data.domain_tld);
                $inp_userapikey   .val(that.get_started.data.user.api_key);
                $inp_userapisecret.val(that.get_started.data.user.api_secret);
                $inp_contactid    .val(that.get_started.data.contact_id);
                $inp_cnttypeid    .val(that.get_started.data.contact_role_id);
                $inp_nameserverid .val(that.get_started.data.nameserver_id);
                $inp_nstypeid     .val(that.get_started.data.nameserver_role_id);
            });

            $('[data-show-settings]').click(function () {
                $('#modal-settings').modal('show');

                return false;
            });

            $('#sidebar-nav').affix({
                offset: {
                    top: function () {
                        return $(window).width() <= 980 ? 290 : 210;
                    },
                    bottom: 270
                }
            });

            window.prettyPrint && prettyPrint();
            $('body').trigger('page.get_started.tld_changed', [that.get_started.data.domain_tld]);
        }, 5);
    };

    this.saveGetStartedSettings = function ()
    {
        var $modal = $('#modal-settings');

        var $inp_domainname    = $modal.find('#modal-settings-input-domainname');
        var $sel_domaintld     = $modal.find('#modal-settings-select-domaintld');
        var $inp_userapikey    = $modal.find('#modal-settings-input-userapikey');
        var $inp_userapisecret = $modal.find('#modal-settings-input-userapisecret');
        var $inp_contactid     = $modal.find('#modal-settings-input-contactid');
        var $inp_cnttypeid     = $modal.find('#modal-settings-input-contacttypeid');
        var $inp_nameserverid  = $modal.find('#modal-settings-input-nameserverid');
        var $inp_nstypeid      = $modal.find('#modal-settings-input-nameservertypeid');

        if ($inp_domainname.val()) {
            that.get_started.data.domain_name = $inp_domainname.val();
        }

        if ($sel_domaintld.val()) {
            that.get_started.data.domain_tld = $sel_domaintld.val();
        }

        if ($inp_userapikey.val()) {
            that.get_started.data.user.api_key = $inp_userapikey.val();
        }

        if ($inp_userapisecret.val()) {
            that.get_started.data.user.api_secret = $inp_userapisecret.val();
        }

        if ($inp_contactid.val()) {
            that.get_started.data.contact_id = $inp_contactid.val();
        }

        if ($inp_cnttypeid.val()) {
            that.get_started.data.contact_role_id = $inp_cnttypeid.val();
        }

        if ($inp_nameserverid.val()) {
            that.get_started.data.nameserver_id = $inp_nameserverid.val();
        }

        if ($inp_nstypeid.val()) {
            that.get_started.data.nameserver_role_id = $inp_nstypeid.val();
        }

        $modal.modal('hide');

        $('#main-menu li.active a').click();

        if (that.is_page_loading === false) {
            $('body').trigger('page.get_started.tld_changed', [that.get_started.data.domain_tld]);
        }
    };

    this.setTemplate = function (name, tpl_data)
    {
        var compiled = Hogan.compile(tpl_data);
        that.template = compiled;

        return compiled;
    };

    this.setHomeTemplate = function (tpl_data)
    {
        var compiled = Hogan.compile(tpl_data);
        that.template_home = compiled;

        return compiled;
    };

    this.setAbstractTemplate = function (tpl_data)
    {
        var compiled = Hogan.compile(tpl_data);
        that.template_abstract = compiled;

        return compiled;
    };

    this.setGetStartedTemplate = function (tpl_data)
    {
        var compiled = Hogan.compile(tpl_data);
        that.template_get_started = compiled;

        return compiled;
    };

    this.prefilterData = function (data)
    {
        return {
            "data": data,
            "uppercase": function () {
                return function (text, scope) {
                    text     = $.trim(text);
                    var _txt = Hogan.compile(text);
                    text     = _txt.render(scope);

                    return text.toUpperCase();
                };
            },
            "alert_headsup": function () {
                return function (text, scope) {
                    return '<div class="alert alert-info"><strong>Heads up!</strong> '+ text +'</div>';
                };
            },
            "nl2br": function () {
                return function (text, scope) {
                    text     = $.trim(text);
                    var _txt = Hogan.compile(text);
                    text     = _txt.render(scope);

                    return (text +'').replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1' + '<br />' + '$2');
                };
            },
            "add_attribute": function () {
                return function (text, scope) {
                    text = scope.url;

                    var regex = /\:\w+/gi, result, ind = [];

                    while (result = regex.exec(text)) {
                        ind.push(result[0]);
                    }

                    $.each(ind, function (k, v) {
                        var rgx    = new RegExp(v);
                        var _descr = null;

                        if (typeof scope.attribute[v] !== 'undefined') {
                            _descr = scope.attribute[v].descr;
                        }

                        if (_descr) {
                            text = text.replace(rgx, '<abbr title=\"'+ scope.attribute[v].name +'\" data-content=\"'+ _descr +'\" data-placement="top" data-html="true" data-trigger="hover" data-delay="10" data-container="footer">'+ v +'</abbr>');
                        }
                    });

                    if (text === null) {
                        text = '';
                    }

                    return text;
                };
            }
        };
    };

    this.applySidebarLinkEvent = function ()
    {
        $('#sidebar-nav').find('a').click(function (e) {
            var _id     = ($(this).attr('href')).replace('#', '');
            var $active = $('#main-menu').find('li.active a');

            if ($active.length) {
                window.location.hash = $active.attr('href').replace('#', '') +'|'+ _id;
            }

            $('html, body').animate(
                {
                    scrollTop: $('#'+ _id).offset().top
                },
                350
            );

            e.preventDefault();
            e.stopPropagation();

            return false;
        });
    };

    this.getGetStartedData = function ()
    {
        return that.get_started;
    };
};