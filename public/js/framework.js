//<![CDATA[


var flashTypes = new Array();
var flashMessages = new Array();

// AutoComplete module
var AutoComplete = (function()
{
    // returns formatted hashtag
    var formatObjectHashtag = function(string, expand)
    {
        if(expand)
        {
            var terms = string.split(':'),
                model = Util.ucFirst(terms[0]),
                id    = terms[1];
            // add additional info if it exists
            if(terms.length == 3)
            {
                var result = model+': '+id+': '+terms[2];
            }
            else
            {
                var result = model+': '+id;
            }
            return result;
        }
        else
        {
            var terms = string.split(': '),
                model = Util.lcFirst(terms[0]),
                id    = terms[1];
            return model+':'+id;
        }
    };

    // returns array of matches
    var getMatches = function(model, id)
    {
        var matches = [],
            action = Url.base() + '/objectAutoComplete',
            params = { model: model, id: id },
            method = 'POST',
            dataType = 'text',
            success = function(data, status)
            {
                if(data)
                {
                    matches = data.split(',');
                }
            };

        $.ajax({
            async: false,
            url: action,
            data: params,
            type: method,
            dataType: dataType,
            error: Framework.handleAJAXError,
            success: success
        });

        return matches;
    };

    var init = function()
    {
        $('textarea').textcomplete([
            {
                match: /\B#([a-zA-Z]{3,}:[0-9]{3,})$/,
                search: function (term, callback) {
                    // split the term by ":"
                    var terms = term.split(':'),
                        model = terms[0],
                        id    = terms[1];
                    // check to see if model is allowed
                    var validModels = ['quote', 'ticket', 'changeControl', 'delivery', 'customer', 'asset'];
                    var matches = [];
                    // if model is valid
                    $.map(validModels, function (validModel) {
                        if(validModel.indexOf(model) === 0)
                        {
                            // get object matches
                            matches = AutoComplete.getMatches(validModel, id);
                        }
                    });
                    // display matches
                    callback($.map(matches, function (match) {
                        return match.indexOf(model) === 0 ? AutoComplete.formatObjectHashtag(match, true) : null;
                    }));
                },
                template: function (value) {
                    return value;
                },
                index: 1,
                replace: function (value) {
                    // insert formatted text into textarea
                    return '#' + AutoComplete.formatObjectHashtag(value) + ' ';
                }
            }
        ]);
    };

    return {
        formatObjectHashtag: formatObjectHashtag,
        getMatches: getMatches,
        init: init
    };
})();


// FlashMessage module
var FlashMessage = (function()
{
    var fadeout = function(id)
    {
        $(id).delay(4000).fadeOut(200);
    };

    var show = function(type, message, fadeout)
    {
        var i = Math.floor(Math.random()*1000000);
        // remove success messages when adding a new one to prevent stacking
        if(type == 'success')
        {
            $('.alert-'+type).remove();
        }
        $('#flash').append('<div class="alert alert-'+type+' align-center" style="display:none;" id="flash-'+i+'"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>'+message+'</div>');
        $('#flash-'+i).fadeIn(200);
        if(fadeout && fadeout === true) {
            this.fadeout('#flash-'+i);
        }
    };

    var showError = function(message, fadeout)
    {
        Util.loadStop();
        this.show('danger', message, fadeout);
    };

    var showSuccess = function(message, fadeout)
    {
        this.show('success', message, fadeout);
    };

    var showWarning = function(message, fadeout)
    {
        this.show('warning', message, fadeout);
    };

    var showInfo = function(message, fadeout)
    {
        this.show('info', message, fadeout);
    };

    return {
        fadeout: fadeout,
        show: show,
        showError: showError,
        showSuccess: showSuccess,
        showWarning: showWarning,
        showInfo: showInfo
    }
})();


var Modal = (function()
{
    var closeForm = function()
    {
        this.close('#form-modal');
    };

    var close = function(id)
    {
        $(id).modal('hide');
    };

    var init = function()
    {
        //fix modal force focus for select 2
        /*
         $.fn.modal.Constructor.prototype.enforceFocus = function () {
           var that = this;
           $(document).on('focusin.modal', function (e) {
             if ($(e.target).hasClass('select2-input')) {
               return true;
             }

             if (that.$element[0] !== e.target && !that.$element.has(e.target).length) {
               that.$element.focus();
             }
           });
         };
         */
    };

    var showInfo = function(title, content, size)
    {
        var allowedSizes = ['sm', 'md', 'lg'],
            target = '#info-modal';

        $(target + ' .modal-dialog').removeClass('modal-sm modal-md modal-lg');

        if(size && $.inArray(size, allowedSizes))
        {
            // use size if specified
            $(target + ' .modal-dialog').addClass('modal-' + size);
        }
        else
        {
            // default is large
            $(target + ' .modal-dialog').addClass('modal-lg');
        }

        $(target + ' .modal-title').html(title);
        $(target + ' .modal-body').html(content);
        $(target).modal('show');
    };

    var showError = function(message, fadeout)
    {
        Util.loadStop();

        this.showMessage('danger', message, fadeout);

        Framework.enableButton('#form-modal-submit');
    };

    var showMessage = function(type, message, fadeout)
    {
        var i = Math.floor(Math.random()*1000000);
        $('#modal-flash').append('<div class="alert alert-'+type+' align-center" style="display:none;" id="modal-flash-'+i+'"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>'+message+'</div>');
        $('#modal-flash-'+i).fadeIn();
        if(fadeout && fadeout === true) {
            this.fadeout('#modal-flash-'+i);
        }
    };

    return {
        close: close,
        closeForm: closeForm,
        init: init,
        showError: showError,
        showInfo: showInfo,
        showMessage: showMessage
    };
})();

// Search module
var Search = (function()
{
    var go = function(e)
    {
        if (e.which == 38 || e.which == 40 || e.which == 13) {
            // ignore these
            return;
        }
        if (!$('#quick-search').val()) {
            $('#quick-search-content').hide();
            return;
        }
        diff = 3-$('#quick-search').val().length;
        if (diff > 0) {
            $('#quick-search-content').html('<div class="padding-10">Please enter <strong>'+diff+'</strong> more character'+((diff>1)?'s':'')+'</div>').show();
            return;
        }
        if (!$('#quick-search-results').is(':visible')) {
            $('#quick-search-content').html('<div class="padding-10">Searching...</div>').show();
        }
        $.ajax({
            data: {
                q: $('#quick-search').val()
            },
            url: $('#quick-search').data('quick-url'),
            success: function(data) {
                var h = $(window).height()-40;
                $('#quick-search-content').html(data).css('max-height', h+'px');
            }
        });
    };

    var browse = function(e)
    {
        if (!$('#quick-search-results').is(':visible') && e.which != 13) {
            return;
        }
        var tbl = $('#quick-search-results');
        var cur = $('.select', tbl).first();
        switch(e.which) {
            case 38:
                if (cur.length) {
                    cur.removeClass('select');
                    cur.prevAll('.search-result').first().addClass('select');
                } else {
                    tbl.children('.search-result').last().addClass('select');
                }
                break;

            case 40:
                if (cur.length) {
                    cur.removeClass('select');
                    cur.nextAll('.search-result').first().addClass('select');
                } else {
                    tbl.children('.search-result').first().addClass('select');
                }
                break;

            case 13:
                if (cur.length) {
                    window.location = cur.find('a').attr("href");
                } else {
                    Search.redirect();
                }
                break;

            default: return;
        }
        e.preventDefault();
    };

    var init = function()
    {
        // search form expand and contract
        $('#navbar-search input').on('focusin', function()
        {
            $(this).animate({width:'+=60'}, 100);
            $(this).attr('placeholder', '');
        });
        $('#navbar-search input').on('focusout', function()
        {
            $(this).animate({width:'-=60'}, 100);
            $(this).attr('placeholder', 'Search');
        });

        // quicksearch
        $(document.body).click(function(e) {
            if($(e.target).is('.quick-search, .quick-search *, #quick-search-content *'))return;
            $('#quick-search-content').hide();
        });
        $(document.body).keyup(function(e) {
            // ignore hot-keys while in input
            if ( $('input:focus, textarea:focus').length > 0 ) {  return; }
            // hot-key: search
            if (e.which == 83) {
                $('#quick-search').focus().select();
            }
            e.preventDefault();
        });
        $('#quick-search-submit').click(function(e) {
            Search.redirect();
        });
        $(document.body).on('keyup focus', '#quick-search', $.debounce(250, function(e) {
            Search.go(e);
        }));
        $(document).on({
            mouseenter: function () {
                $('#quick-search-results li').removeClass('select');
                $(this).addClass('select');
            },
            mouseleave: function () {
                $(this).removeClass('select');
            }
        }, '#quick-search-results li');
        $('#quick-search').on('keydown', function(e) {
            if (e.which == 38 || e.which == 40 || e.which == 13) {
                Search.browse(e);
            }
        });
    };

    var redirect = function()
    {
        window.location.href = $('#quick-search').data('url')+'?q='+encodeURIComponent($('#quick-search').val());
    };

    return {
        browse: browse,
        go: go,
        init: init,
        redirect: redirect
    };
})();

// Util module
var Util = (function()
{
    var downCount = function(target)
    {
        updateCount(target,'down');
    };

    var loadStart = function()
    {
        // disable since we aren't using this
        //NProgress.configure({ showSpinner: false });
        //NProgress.start();
    };

    var loadStop = function()
    {
        // disable since we aren't using this
        //NProgress.done();
    };

    var lcFirst = function(string)
    {
        return string.charAt(0).toLowerCase() + string.substring(1);
    };

    /*
     * converts a string to an object
     * string must be in this format: key:value, key:value, etc
     */
    var stringToObject = function(string)
    {
        var object = {};
        if(string)
        {
            var properties = string.split(',');
            properties.forEach(function(property) {
                var prop = property.split(':');
                object[prop[0]] = prop[1];
            });
        }
        return object;
    };


    /**
     * Converts string values of null, integers and booleans to their primitive values
     */
    var stringToPrimitive = function(string)
    {
        if(string === "null") {
            return null;
        }
        else if(!isNaN(string)) {
            return +string;
        }
        else if(string === "true") {
            return true;
        }
        else if(string === "false") {
            return false;
        }
        else {
            return string;
        }
    };

    var ucFirst = function(string)
    {
        return string.charAt(0).toUpperCase() + string.substring(1);
    };

    var upCount = function(target)
    {
        updateCount(target);
    };

    var updateCount = function(target,direction,amount)
    {
        // get count and convert to int
        var count = parseInt( $(target).text() );

        // set default amount
        var amount = typeof amount !== 'undefined' ? amount : 1;

        // choose opperator
        if(direction && direction === 'down') {
            $(target).text(count - amount);
        } else {
            $(target).text(count + amount);
        }
    };

    var updateURL = function(stateObject, title, url)
    {
        // checks to see if pushState method is available - fixes IE 9 and below
        if(typeof window.history.pushState == 'function')
        {
            window.history.pushState(stateObject, title, url);
        }
    };

    return {
        downCount: downCount,
        lcFirst: lcFirst,
        loadStart: loadStart,
        loadStop: loadStop,
        stringToObject: stringToObject,
        stringToPrimitive: stringToPrimitive,
        ucFirst: ucFirst,
        upCount: upCount,
        updateCount: updateCount,
        updateURL: updateURL
    };
})();

// View module
var View = (function()
{
    var init = function()
    {
        this.setupVisibilityToggle();
    };

    var setupVisibilityToggle = function()
    {
        $('#content').off('click', '.visibility-toggle');

        $('#content').on('click', '.visibility-toggle', function() {

            var target = $(this).data('target'),
                label  = $(this).data('label'),
                action = $(this).data('action');

            View.setVisibility(this, target, label, action);
        });
    };

    var setVisibility = function(toggle, target, label, action)
    {
        if(action == 'show')
        {
            $(target).removeClass('hide');
            $(toggle).data('action', 'hide');
            $(toggle).text('Hide ' + label);

            sessionStorage[lcFirst(label.replace(' ', '')) + 'Visibility'] = 1;
        }
        else if(action == 'hide')
        {
            $(target).addClass('hide');
            $(toggle).data('action', 'show');
            $(toggle).text('Show ' + label);

            sessionStorage[lcFirst(label.replace(' ', '')) + 'Visibility'] = 0;
        }
    };

    return {
        init: init,
        setupVisibilityToggle: setupVisibilityToggle,
        setVisibility: setVisibility
    }
})();

// Url module
var Url = (function()
{
    var _parts = null;

    var _val = null;

    var _split = function(url)
    {
        return url.split('/');
    };

    var base = function()
    {
        return '/' + $('body').data('module');
    };

    var build = function(module, objectId, action, subAction)
    {
        var result = '/' + module + '/' + objectId;
        if(action && action != 'show')
        {
            result += '/' + action;
        }
        result += '/' + subAction;
        return result;
    };

    var getAction = function() {

        if(_parts.length == 5)
        {
            var action = 'show';
        }
        else
        {
            var action = _parts[2];
        }
        return action;
    };

    var getModule = function()
    {
        return _parts[0];
    };

    var getObjectId = function()
    {
        return _parts[1];
    };

    var getRouteAction = function()
    {
        if(_parts.length == 4)
        {
            return _parts[3];
        }
        return null;
    };

    var getSubAction = function()
    {
        if(_parts.length == 4)
        {
            return _parts[3];
        }
        return _parts[2];
    };

    var init = function(url)
    {
        if(!url)
        {
            var url = window.location.href,
                protocolAndHost = 'https://' + window.location.host,
                url = url.replace(protocolAndHost, '');
        }

        // remove dev controller from url and set _val
        _val = url.replace(/\/[a-z_]+.php/i, '');

        // split url by "/"
        _parts = _split(_val);

        // remove first part if empty string
        if(_parts[0] == "")
        {
            _parts.shift();
        }
    };

    return {
        base: base,
        build: build,
        getAction: getAction,
        getModule: getModule,
        getObjectId: getObjectId,
        getSubAction: getSubAction,
        init: init
    };
})();


// Framework - a version of the framework module tools
var Framework = (function()
{
    var _confirm = function(options)
    {
        if (!options) { options = {}; }

        var show = function(el, text) {
            if (text) { el.html(text); el.show(); } else { el.hide(); }
        }

        var ok = options.ok ? options.ok : 'Ok';
        var cancel = options.cancel ? options.cancel : 'Cancel';
        var title = options.title
        var text = options.text;
        var dialog = $('#confirm-modal');
        var header = dialog.find('.modal-header');
        var footer = dialog.find('.modal-footer');

        show(dialog.find('.modal-body p'), text);
        show(dialog.find('.modal-header h4'), title);
        footer.find('.dialog-confirm').unbind('click').html(ok);
        footer.find('.dialog-cancel').unbind('click').html(cancel);
        dialog.modal('show');

        var $deferred = $.Deferred();
        var is_done = false;
        footer.find('.dialog-confirm').on('click', function(e) {
            is_done = true;
            dialog.modal('hide');
            $deferred.resolve();
        });
        dialog.on('hidden.bs.modal', function() {
            if (!is_done) { $deferred.reject(); }
        });

        return $deferred.promise();
    };


    var ajaxAction = function(elem)
    {
        // start loading indicator
        Util.loadStart();

        // determine which type of element is clicked
        var tagName = elem.prop('tagName').toLowerCase();

        // hide lingering tooltips
        elem.tooltip('hide');

        // set defaults
        var defaults = {
            dataType: 'json',
            method: 'post'
        };

        // get configured options
        var options = {
            callback: elem.data('callback'), // string - Module.function(params);
            target: elem.data('target'), // string
            dataType: elem.data('type'), // string
            confirm: elem.data('confirm'), // string
            append: elem.data('append'), // boolean - whether to append rather than completely replace html in target
            preCallback: elem.data('pre-callback') // string - Module.function(params);
        };

        // conditional options
        if(tagName == 'form')
        {
            options.url = elem.attr('action'); // string
            options.method = elem.attr('method'); // string
            options.params = elem.serialize(); // use form input as params
        }
        else
        {
            options.url = elem.attr('href'); // string
            options.method = elem.data('method'); // string
            options.params = Util.stringToObject(elem.data('parameters')); // string key:value, key:value
        }

        // merge defaults and options
        var settings = $.extend(defaults, options);

        // ajax success
        settings.success = function(data)
        {
            Framework.closeLoadingModal();

            Util.loadStop();

            if(data)
            {
                // merge data into settings - this allows for overrides from controller action
                if(settings.dataType == 'html')
                {
                    settings.html = data;
                }
                else
                {
                    $.extend(settings, data);
                }

                // flash message
                Framework.handleFlash(data);

                // html append or replace
                Framework.handleHTML(settings);
            }

            // reset form protect
            if(tagName == 'form' && elem.hasClass('form-protect'))
            {
                Framework.formHasChanged = false;
            }

            // callback function
            if(settings.callback)
            {
                Framework.handleCallback(settings.callback);
            }
        }

        // confirm dialog
        if (settings.confirm) {

            Framework._confirm({
                title: 'Please Confirm',
                text: settings.confirm,
                ok: 'Yes',
                cancel: 'Cancel'
            }).done(function() {

                // pre callback function
                if(settings.preCallback)
                {
                    Framework.handleCallback(settings.preCallback);
                }

                // perform ajax request
                $.ajax({

                    url: settings.url,

                    data: settings.params,

                    type: settings.method,

                    dataType: settings.dataType,

                    error: Framework.handleAJAXError,

                    success: settings.success
                });

            }).fail(function() {

                // exit if user clicks no
                Util.loadStop();
                return false;

            });
        }
        else
        {
            // pre callback function
            if(settings.preCallback)
            {
                Framework.handleCallback(settings.preCallback);
            }

            // perform ajax request
            $.ajax({

                url: settings.url,

                data: settings.params,

                type: settings.method,

                dataType: settings.dataType,

                error: Framework.handleAJAXError,

                success: settings.success
            });
        }
    };

    var clearFieldValue = function(target)
    {
        $(target).val('');
    };

    // hides loading modal if visible
    var closeLoadingModal = function()
    {
        $('#loading-modal').modal('hide');
    };

    var disableButton = function(e)
    {
        // disable submit button, inject spinner
        $(e).prepend("<i id='spinner' class='fa fa-spinner fa-spin fa-1x fa-fw'></i> ");
        $(e).prop('disabled', true);
    };

    var enableButton = function(e)
    {
        // disable submit button, inject spinner
        $('#spinner').remove();
        $(e).prop('disabled', false);
    };


    var formatEntityResults = function(data)
    {
        return '<span class="text-500">' + data.text + '</span>' + '<br>' + data.type + ' ' + data.status;
    };

    var formatEventResults = function(data)
    {
        return '<span class="text-500">' + data.text + '</span>' + '<br>' + data.customer + ' - ' + data.title;
    };

    var formatSeriesResults = function(data)
    {
        return '<span class="text-500">' + data.text + '</span>' + '<br>' + data.customer + ' - ' + data.title;
    };

    var formatUserResults = function(data)
    {
        return '<span class="text-500">' + data.text + '</span>' + '<br>' + data.customer + ' - ' + data.title;
    };

    var formHasChanged = false;

    var getAction = function()
    {
        return $('body').data('action');
    };

    var getModule = function()
    {
        return $('body').data('module');
    };

    var getObjectOrder = function(target)
    {
        var objectOrder = [];

        $(target + ' li').each(function(index) {

            var objectId = $(this).data('object');
            if(objectId != undefined)
            {
                objectOrder.push(objectId);
            }
        });

        return objectOrder;
    };

    var handleAJAXError = function(jqXHR, textStatus, errorThrown)
    {
        Framework.closeLoadingModal();

        Util.loadStop();

        if(jqXHR.status == 401) {
            this.loadView();
        } else {
            if(jqXHR.responseText)
            {
                if($('#form-modal').hasClass('in')) {
                    Modal.showError(jqXHR.responseText);
                    // re-enable form button
                } else {
                    FlashMessage.showError(jqXHR.responseText);
                }
            }
        }
    };

    var handleCallback = function(callback)
    {
        if(callback)
        {
            var callbacks = callback.split(/\s*\|\s*/g);

            callbacks.forEach(function(callback) {

                var func,
                    mod,
                    params
                parts = callback.match(/(?:[^.\(;]+|\([^()]*\))+/g);

                // split module and function
                if(parts.length == 2)
                {
                    mod  = parts[0],
                        func = parts[1];
                }
                else
                {
                    func = parts[0];
                }

                // split function and parameters
                if((parts = func.split('(')).length == 2)
                {
                    func   = parts[0];
                    params = parts[1].replace(/[)'"]+/g, '');
                }

                // convert params comma separated string to array
                var paramsArray = [];

                if(params) {
                    paramsArray = params.split(/\s*,\s*/g);

                    // convert string values to primitives
                    for (var i = 0, len = paramsArray.length; i < len; i++)
                    {
                        paramsArray[i] = Util.stringToPrimitive(paramsArray[i]);
                    }
                }

                // dynamically call function
                if(mod)
                {
                    if(params && paramsArray.length) {
                        window[mod][func].apply(null, paramsArray);
                    } else {
                        window[mod][func]();
                    }
                } else {
                    if(params && paramsArray.length) {
                        window[func].apply(null, paramsArray);
                    } else {
                        window[func]();
                    }
                }

            });
        }
        else
        {
            console.error('No callback provided.');
        }
    };

    var handleFlash = function(data)
    {
        console.log('handleFlash '+data);

        if(data.message) {
            FlashMessage.showSuccess(data.message, true);
        }
        if(data.success) {
            FlashMessage.showSuccess(data.success, true);
        }
        if(data.warning) {
            FlashMessage.showWarning(data.warning, true);
        }
        if(data.error) {
            FlashMessage.showError(data.error);
        }
    };

    var handleHTML = function(settings)
    {
        if(settings.target && settings.html)
        {
            if(settings.append) {
                $(settings.target).append(settings.html);
            } else {
                $(settings.target).html(settings.html);
            }

            // re-attach event listeners to new dom elements
            this.setupControls(settings.target);
        }
    };

    var init = function()
    {
        // AutoComplete.init();
        Modal.init();
        View.init();
        Search.init();

        this.setupAjaxActions();
        this.setupControls();
        this.setupFormChangeListener();
        this.setupFormModal();
        this.setupGlobalAlert();
        this.setupGroupCheck();
        this.setupItemActions();
        this.setupMenu();
        this.setupShowVideo();
        this.setupLoadingModal();

        // notification handling
        for (i in flashTypes)
        {
            if(flashTypes[i] == 'success')
            {
                var fadeout = true;
            } else
            {
                var fadeout = false;
            }
            FlashMessage.show(flashTypes[i], flashMessages[i], fadeout);
        }
    };

    var loadContent = function(url, target)
    {
        Util.loadStart();

        $.ajax
        ({
            error: Framework.handleAJAXError,

            type: 'get',

            success: function(data)
            {
                $(target).html(data);

                Util.loadStop();
            },

            url: url
        });
    };

    var loadView = function(url)
    {
        if(!url)
        {
            var url = $(location).attr('href');
        }
        window.location.href = url;
    };

    var refreshPage = function()
    {
        window.location.reload();
    };

    var appendOptionResults = function(target, results)
    {
        // use regex to re-add the double quotes around the key, which must be treated as a string
        //var objKeysRegex = /({|,)(?:\s*)(?:')?([0-9A-Za-z_$\.\\\/][A-Za-z0-9_ \-\.\\\/$]*)(?:')?(?:\s*):/g;  // just double quotes the first
        var objKeysRegex = /({|,)(?:\s*)(?:')?([0-9A-Za-z_$\.\/\\][A-Za-z0-9_ \-\.\/\\$]*)(?:')?(?:\s*):(?:\s*)(?:')?([0-9A-Za-z_$\.\/\\][A-Za-z0-9_ \-\.\/\\$]*)(?:')?(?:\s*)(}|,)*/g;

        var newResults = results.replace(objKeysRegex, "$1\"$2\":\"$3\"")+'}';

        var exampleMulti = $(target).select2();  // name of the select
        var selected = $(target).select2("val");  // array of selected values

        // DEBUG the selected options
        console.log('Selected: '+ selected);
        console.log('Results: '+ results);
        console.log('newResults: '+ newResults);


        // parses the JSON string and appends to the select list options
        var str = JSON.parse(newResults, (key, value) => {
            if (key)
            {
                console.log('key: '+key);
                console.log('value: '+value);

                var option = new Option(key, value);

                $(target).append(option);

                // add the value to the selected array
                selected.push(value);
            };


    });

        // set the selected values by triggeting a change
        exampleMulti.val(selected).trigger("change");
    };

    var selectMultiResults = function(target, results)
    {
        var exampleMulti = $(target).select2();  // name of the select
        exampleMulti.val(results).trigger("change");
        console.log('selectMultiResults fired on '+target);
    };


    var setPageTitle = function()
    {
        var arr = [];
        $('.page-title-text').each(function() {
            arr.push($(this).text());
        });
        document.title = arr.join(' - ') + ' - Expedient Quote Tracker';
    };

    var setupAjaxActions = function()
    {
        $('body').on('click', '.object-action', function(e) {

            // prevent default browser action
            e.preventDefault();

            Framework.ajaxAction($(this));

        }).on('submit', '.form-framework-ajax', function(e) {

            // prevent default browser action
            e.preventDefault();

            // disable submit button, inject spinner
            Framework.disableButton('#form-modal-submit');

            Framework.ajaxAction($(this));

        }).on('change', '.input-ajax', function(e) {

            // prevent default browser action
            e.preventDefault();

            Framework.ajaxAction($(this).closest('form'));

        });
    };

    var setupContextMenus = function()
    {
        // initially hide all anytime context menu is triggered
        $('body').on('contextmenu', function() {
            $('#context-menu').addClass('hide');
        });

        // context menu is triggered within the designated listener
        $('body').on('contextmenu', '.context-menu-toggle', function(e) {

            e.preventDefault();

            var target = $(this).data('context-target'),
                content = $(target).html();

            // set postion, add content and show
            $('#context-menu').css({
                left: e.pageX,
                top: e.pageY
            }).html(content).removeClass('hide');

            return false;
        });

        // hide context menu when clicking anywhere and clear its content
        $('body').on('click', function() {
            $('#context-menu').addClass('hide').html('');
        });
    };

    var setupControls = function(target)
    {
        if(!target)
        {
            var target = 'body';
        }

        // select2
        $(target + ' .select2').select2();

        // disable since we aren't using this
        //this.setupEditable(target);

        // enable popover
        // disable since we aren't using this
        /*
        $(target + ' .popover-able').popover({
          container: 'body'
        });
        */

        // enable tooltips
        $(target).tooltip({
            selector: '.tip',
            container: 'body',
            html: true,
            delay: { show: 500 }
        });

        // enable copying
        // disable since we aren't using this
        //new Clipboard('.clipboard-trigger');

        this.tabAutoLayout();

        // setup the form change listener
        this.setupFormChangeListener();

        this.setupContextMenus();
    };

    var setupEditable = function(target)
    {
        // editable
        $(target + ' .editable').editable({
            container: 'body'
        });

        // this auto selects text in x-editable when data-selected is set to true
        $(target + ' .editable').on('shown', function(e, editable) {
            if($(this).data('selected') == true)
            {
                editable.input.postrender = function() {
                    editable.input.$input.select();
                };
            }
        });
    };

    // listens for changes to elements in a form
    var setupFormChangeListener = function()
    {
        var formClass = '.form-protect';
        form = $('#show-content').find(formClass);

        if(form.length)
        {
            // textareas and inputs
            $(formClass).on('keyup', 'textarea, input', function() {
                if(!Framework.formHasChanged)
                {
                    Framework.formHasChanged = true;
                }
            });

            // select, hidden, checkbox, radio
            $(formClass).on('change', 'select, hidden, input[type=checkbox], input[type=radio]', function() {
                if(!Framework.formHasChanged)
                {
                    Framework.formHasChanged = true;
                }
            });

            // instances of ckeditors
            var ckeditors = CKEDITOR.instances;
            for (var key in ckeditors) {
                ckeditors[key].on('change', function() {
                    if(!Framework.formHasChanged)
                    {
                        Framework.formHasChanged = true;
                    }
                });
            }
        }
    };

    var setupFormModal = function()
    {
        // fix for using form inside of a bootstrap modal
        $.fn.modal.Constructor.prototype.enforceFocus = function() {};

        $('body').on('click', '.form-modal', function(e)
        {
            e.preventDefault();
            Util.loadStart();
            $('#modal-flash .alert').remove();

            var defaults = {
                modal: '#form-modal',
                url: Url.base() + '/ModalForm',
                formClass: 'form-ajax',
                method: 'get',
                submitLabel: 'Save',
                modalTitle: 'Form Modal',
                callback: 'Modal.closeForm()',
                loadingMessage: 'Loading Form...'
            };

            var options = {
                form: $(this).data('form'),
                formAction: $(this).attr('href'),
                formClass: $(this).data('form-class'),
                formTemplate: $(this).data('form-template'),
                modalTitle: $(this).data('title'),
                submitLabel: $(this).data('submit-label'),
                url: $(this).data('modal-url'),
                target: $(this).data('form-target'),
                callback: $(this).data('callback'),
                preCallback: $(this).data('preCallback'),
                loadingMessage: $(this).data('loading-message'),
                selectTarget: $(this).data('form-select-target')
            };

            var settings = $.extend(defaults, options);

            // update html in modal
            $(settings.modal + ' .modal-content').html('<div class="modal-loading"><div class="modal-loading-spinner"><i class="fa fa-spinner fa-spin fa-3x fa-fw"></i></div><div class="modal-loading-message">' + settings.loadingMessage +'</div></div>');

            // show modal
            $(settings.modal).modal('show');

            var params = Util.stringToObject($(this).data('parameters'));

            // add to params object
            params.modalTitle = settings.modalTitle;
            params.submitLabel = settings.submitLabel;
            params.form = settings.form;
            params.formTemplate = settings.formTemplate;
            params.formAction = settings.formAction;
            params.target = settings.target;
            params.callback = settings.callback;
            params.formClass = settings.formClass;
            params.preCallback = settings.preCallback;
            params.selectTarget = settings.selectTarget;

            // load form
            $.ajax
            ({
                data: params,

                error: Framework.handleAJAXError,

                type: settings.method,

                success: function(data)
                {
                    // update html in modal
                    $(settings.modal + ' .modal-content').html(data);

                    // focus on first .form-control element
                    $('#form-modal').on('shown.bs.modal', function () {
                        $('#modal-form .form-control:first').focus();
                    });

                    Util.loadStop();
                },

                url: settings.url
            });

        });

    };


    var setupGlobalAlert = function()
    {
        $('.info-modal-toggle').on('click', function(e) {

            e.preventDefault();

            var title = $(this).data('title'),
                contentTarget = $(this).data('content-target'),
                content = $(contentTarget).html();

            Modal.showInfo(title, content);
        });

        $('#global-alert').on('click', '.global-alert-toggle', function(e) {

            e.preventDefault();

            var url = $(this).attr('href'),
                target = $(this).data('target'),
                success = function(data) {

                    if($('#global-alert li').length == 1)
                    {
                        $('#global-alert').remove();
                        // backend css update
                        $('#content').removeClass('top-80');
                        // frontend css updates
                        $('#header').removeClass('top-80-desktop');
                        $('#content').removeClass('header-offset-tabs-alert');
                    }
                    else
                    {
                        $(target).remove();
                    }
                };

            $.ajax({
                url: url,
                type: 'post',
                error: Framework.handleAJAXError,
                success: success
            });
        });

        this.setupGlobalAlertCycle();
    };

    var setupGlobalAlertCycle = function()
    {
        // if there is more than 1 global alert
        if($('#global-alert li').length > 1)
        {
            // cycle through
            window.setInterval(function() {
                $('#global-alert li').first().appendTo('#global-alert ul');
            }, 5000);
        }
    };

    var setupGroupCheck = function()
    {
        // group check for select all or none
        $(document).on('change', '.group-check', function(e)
        {
            var groupID = $(this).data('group');

            if(groupID)
            {
                // when specifying a certain list
                var batchClass = '.group-'+groupID;
            }
            else
            {
                // generic class
                var batchClass = '.batch-check';
            }

            if($(this).prop("checked"))
            {
                $(batchClass).prop("checked", true);
            } else {
                $(batchClass).prop("checked", false)
            }

            // handle batch checked for backend primary list
            if(groupID && (groupID == "primary-list")) {
                FrameworkBackend.primaryBatchChecked(e);
            }
        });
    };

    var setupItemActions = function()
    {
        // confirm action
        $('#content').on('click', '.confirm-action', function(e)
        {
            e.preventDefault();
            $(this).tooltip('hide');
            Util.loadStart();

            var defaults = {
                redirect: true
            };

            var options = {
                href: $(this).attr('href'),
                confirm: $(this).data('confirm'),
                callback: $(this).data('callback'),
                redirect: $(this).data('redirect'),
                msg: $(this).data('loading-modal')
            };

            var settings = $.extend(defaults, options);

            Framework._confirm({
                title: 'Please Confirm',
                text: settings.confirm,
                ok: 'Yes',
                cancel: 'Cancel'
            }).done(function() {
                //loading modal check
                if(settings.msg && settings.msg !== "false")
                {
                    //show loading modal with message
                    Framework.showLoadingModal(settings.msg);
                }

                Util.loadStop();

                if(settings.callback)
                {
                    // use custom callback if specified
                    Framework.handleCallback(settings.callback);
                }

                if(settings.redirect)
                {
                    window.location.href = settings.href;
                }

            }).fail(function() {

                // optional code when the user clicks "No" or hides the dialog
                Util.loadStop();

            });
        });
    };

    var setupLoadingModal = function()
    {
        $('#content').on('click', '.loading-modal', function(e) {
            e.preventDefault();
            var href = $(this).attr('href');
            var msg = $(this).data('loading-modal');
            Framework.showLoadingModal(msg);
            window.location.href = href;
        });
    };

    var setupMenu = function()
    {
        // menu panel
        $('body').on('click', '.menu-panel-toggle a', function(e)
        {
            e.preventDefault();
            $(this).parent('li').toggleClass('active');
            $('.menu-panel').toggleClass('hide');
            $(this).toggleClass('object-action');
        });
        // menu panel filter
        $('body').on('change', '#filter-events-form input:checkbox', function(e)
        {
            $('#filter-events-form').submit();
        });
        // submenu
        var submenu = '.dropdown-menu > li.dropdown-submenu';

        $(submenu + ' > a').on('click', function() {

            var parent   = $(this).parent(),
                isActive = parent.hasClass('open');

            // if clicked submenu is active
            if(isActive) {
                // remove active
                parent.removeClass('open');
                $(this).blur();
            } else {
                // remove active for all submenus
                $(submenu).removeClass('open');
                // add active for just the one clicked
                parent.addClass('open');
            }
            return false;
        });

        // removes active submenu when clicking outside dropdown
        $(document).on('click', function() {
            $(submenu).removeClass('open');
        });

        // removes active submenu when clicking parent menu item
        $('.dropdown > .dropdown-toggle').on('click', function() {
            $(submenu).removeClass('open');
        });
    };

    var setupShowVideo = function()
    {
        $('.show-video').click(function(e) {
            e.preventDefault();
            var src    = $(this).attr('href'),
                title  = $(this).attr('title'),
                width  = $(this).data('width'),
                height = $(this).data('height');

            if(!width)
            {
                var width = 890;
            }

            if(!height)
            {
                var height = 500;
            }

            var html = '<video id="video" controls width="' + width + '" height="' + height + '">';
            html += '<source src="' + src + '.webm" type="video/webm" />';
            html += '<source src="' + src + '.mp4" type="video/mp4" />';
            html += 'To view this video please enable JavaScript or upgrade to a web browser that supports HTML5 video.';
            html += '</video>';

            $('#info-modal').on('hidden.bs.modal', function (e) {
                $('#info-modal .modal-title').html('');
                $('#info-modal .modal-body').html('');
            });

            Modal.showInfo(title, html);
        });
    };

    /*
     * focus on error field if one exists
     * if not, focus on username field by default
    */
    var setupSigninForm = function()
    {
        if($('#signin_email').hasClass('has-error') || $('#signin_password').hasClass('has-error'))
        {
            $(".has-error input").first().focus();
        }
        else
        {
            $("#signin_email").focus();
        }
    };

    var setupSortable = function()
    {
        $('.sortable').sortable({
            forcePlaceholderSize: true,
            handle: '.handle'
        }).bind('sortupdate', function(e, ui) {
            var parent = $(ui.item).parent('ul');
            Framework.updateObjectOrder(parent);
        });
    };

    // set up the time zone usage
    var setupTimezone = function(defaultTimezone)
    {
        // check if the timezone has been set, if not, set it
        if (!sessionStorage.timeZone) {
            sessionStorage.timeZone = jstz.determine().name();

            // check if the user timezone matches the session time zone, if not save it
            if (sessionStorage.timeZone != defaultTimezone) {

                $.ajax({
                    type: 'post',
                    dataType: 'json',
                    data: { timezone: sessionStorage.timeZone },
                    url: '/settings/setDefaultTimezone',
                    success: function(data) {
                        if(data.success)
                        {
                            FlashMessage.showSuccess('Your timezone has been updated to <i>'+sessionStorage.timeZone+'</i>.', true);
                        }
                    }
                });
            }
        }
    };

    var showLoadingModal = function(message)
    {
        $('#loading-modal .modal-body p').html('<div class="modal-loading"><div class="modal-loading-spinner"><i class="fa fa-spinner fa-spin fa-3x fa-fw"></i></div><div class="modal-loading-message">' + message +'</div></div>');
        $('#loading-modal').modal({
            backdrop: 'static',
            keyboard: 'false'
        });
    };


    var tabAutoLayout = function()
    {
        var containerWidth = $('.nav-tabs-auto').width(),
            moreWidth = $('#nav-tabs-more').width(),
            tabTotalWidth = moreWidth,
            moreCount = 0;

        // hide initially
        $('#nav-tabs-more').addClass('hide');

        $('.nav-tabs-auto li:not(.dropdown)').each(function() {
            tabTotalWidth = tabTotalWidth + $(this).width();
            if(tabTotalWidth > containerWidth)
            {
                $(this).detach();
                $('#nav-tabs-more-menu-contents').append(this);

                moreCount++;
            }
        });

        if(moreCount > 0)
        {
            $('#nav-tabs-more').removeClass('hide');
            // shows tab labels if hidden by default
            $('#nav-tabs-more .tab-label').removeClass('hide');
        }
    };

    var updateObjectOrder = function(parent)
    {
        var parentId = '#' + parent.attr('id'),
            target = parent.data('target');

        var objectOrder = this.getObjectOrder(parentId),
            url = parent.data('url');
        // ajax
        $.ajax({
            type: 'post',
            url: url,
            data: { order: objectOrder },
            success: function(data) {
                // refreshes list
                if(data.html)
                {
                    $(target).html(data.html);
                }
            }
        });
    };

    return {
        _confirm: _confirm,
        ajaxAction: ajaxAction,
        appendOptionResults: appendOptionResults,
        clearFieldValue: clearFieldValue,
        closeLoadingModal: closeLoadingModal,
        disableButton: disableButton,
        enableButton: enableButton,
        formatCustomerResults: formatCustomerResults,
        formatTicketResults: formatTicketResults,
        formatDeliveryResults: formatDeliveryResults,
        formatHasProductFilterResults: formatHasProductFilterResults,
        formHasChanged: formHasChanged,
        getAction: getAction,
        getModule: getModule,
        getObjectOrder: getObjectOrder,
        handleAJAXError: handleAJAXError,
        handleCallback: handleCallback,
        handleFlash: handleFlash,
        handleHTML: handleHTML,
        init: init,
        loadContent: loadContent,
        loadView: loadView,
        refreshPage: refreshPage,
        selectMultiResults: selectMultiResults,
        setPageTitle: setPageTitle,
        setupAjaxActions: setupAjaxActions,
        setupContextMenus: setupContextMenus,
        setupControls: setupControls,
        setupEditable: setupEditable,
        setupFormChangeListener: setupFormChangeListener,
        setupFormModal: setupFormModal,
        setupGlobalAlert: setupGlobalAlert,
        setupGlobalAlertCycle: setupGlobalAlertCycle,
        setupGroupCheck: setupGroupCheck,
        setupItemActions: setupItemActions,
        setupLoadingModal: setupLoadingModal,
        setupMenu: setupMenu,
        setupShowVideo: setupShowVideo,
        setupSigninForm: setupSigninForm,
        setupSortable: setupSortable,
        setupTimezone: setupTimezone,
        showLoadingModal: showLoadingModal,
        tabAutoLayout: tabAutoLayout,
        updateObjectOrder: updateObjectOrder
    };
})();

// init framework module on document load
$(function()
{
    console.log('framework init');
    Framework.init();
});


//]]>