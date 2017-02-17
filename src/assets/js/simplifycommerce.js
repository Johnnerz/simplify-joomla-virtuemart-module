window.SimplifyPlugin = (function () {
    var _ignore = false;
    var _active = false;
    var _$btn;
    var _$form;
    var _params = {
        'sc-key': "YOUR_HOSTED_PAYMENTS_ENABLED_PUBLIC_KEY",
        name: "NAME",
        description: "Order total",
        reference: "REFERENCE",
        amount: "1",
        currency: "USD",
        operation: 'create.token',
        'redirect-url': window.location
    };

    var _setActive = function (active) {
        _active = active;
        if (active) {
            _$form = jQuery('#checkoutForm');
            _$form.submit(_submitHandler);
        }
    };

    var _setValue = function (key, value) {
        _params[key] = value;
        _$btn.attr('data-' + key, value);
    };

    var _submitHandler = function (e) {
        if (_ignore) {
            _ignore = false;
        } else if (_active) {
            e.preventDefault();
            //the check needs to be asynchronous so the internal jquery code changes the btn.
            //couldn't use the click handler because it's probably prevented by a previous handler.
            window.setTimeout(function () {
                if (_$form.find('#checkoutFormSubmit').prop('disabled') && _$form.find('#tos').prop('checked')) {
                    SimplifyCommerce.hostedPayments();
                    _$btn.click();
                } else {
                    _ignore = true;
                    _$form.submit();
                }
            }, 1);
            return false;
        }
    };

    var _parseSearchQuery = function () {
        var params = {};

        window.location.search.substring(1).split('&').forEach(function (param) {
            var parts = param.split('=');
            if (parts.length > 1) {
                params[parts[0]] = parts[1];
            }
        });

        return params;
    };

    var _init = function () {
        var searchParams = _parseSearchQuery();
        if (searchParams.cardToken) {
            var form = jQuery('#checkoutForm');
            form.attr('action', form.attr('action') + '?cardToken=' + searchParams.cardToken);
            form.find('#checkoutFormSubmit').click();
        } else {
            _$btn = jQuery('<button style="display: none;">Buy Now</button>');
            jQuery(document.body).append(_$btn);
            for (var key in _params) {
                if (_params.hasOwnProperty(key)) {
                    _setValue(key, _params[key]);
                }
            }
        }
    };
    _init();

    return {
        setActive: _setActive,
        setValue: _setValue
    }
})();