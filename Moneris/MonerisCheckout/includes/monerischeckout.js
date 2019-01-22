var monerisCheckout = (function () {
    var mode = "";
    var request_url = "";
    var checkout_div = "moneris-checkout";

    var callbacks = {
        page_loaded: "",
        address_change: "",
        cancel_transaction: "",
        payment_receipt: "",
        payment_complete: "",
        error_event: ""
    };

    function monerisCheckout() {
        var me = this;
        window.addEventListener('message', function (e) {
            console.log("setting receive message");
            me.receivePostMessage(e);
        });
    };

    monerisCheckout.prototype.logConfig = function () {
        console.log("callbacks: " + JSON.stringify(callbacks));
        console.log("request_url: " + request_url);
        console.log("checkout_div: " + checkout_div);
    };

    monerisCheckout.prototype.setCheckoutDiv = function (name) {
        checkout_div = name;
    };

    monerisCheckout.prototype.setMode = function (setmode) {
        mode = setmode;
        if (mode == 'dev') {
            request_url = "https://gatewaydev.moneris.com/chkt/display/index.php";
        }
        else if (mode == 'qa') {
            request_url = "https://gatewayt.moneris.com/chkt/display/index.php";
        }
        else {
            request_url = "https://gateway.moneris.com/chkt/display/index.php";
        }
        console.log("mode is :" + request_url);
    };

    monerisCheckout.prototype.setCallback = function (name, func) {
        if (name in callbacks) {
            callbacks[name] = func;
        }
        else {
            console.log("setCallback - Invalid callback defined: " + name);
        }
    };

    monerisCheckout.prototype.startCheckout = function (ticket) {
        checkoutUrl = request_url + "?tck=" + ticket;
        if (navigator.userAgent.match(/(iPod|iPhone|iPad)/)) {
            $("#" + checkout_div).css({
                "position": "absolute",
                "left": "0",
                "top": "0",
                "border": "none",
                "background": "#FAFAFA",
                "z-index": "100000",
                "min-width": "100%",
                "width": "100%",
                "min-height": "100%",
                "height": "100%"
            });

            $('<iframe>')
                .attr('src', checkoutUrl)
                .css({'border': 'none', 'width': '100%', 'height': '100%'})
                .attr('id', checkout_div + "-Frame")
                .attr('allowpaymentrequest', 'true')
                .appendTo("#" + checkout_div);

            $('html > head').append($('<style type = "text/css">.checkoutHtmlStyleFromiFrame{max-width:100%; width:100%; overflow:hidden !important; }</style>'));
            $("html").addClass("checkoutHtmlStyleFromiFrame");

        }
        else {
            $("#" + checkout_div).css({
                "position": "fixed",
                "left": "0",
                "top": "0",
                "border": "none",
                "background": "#FAFAFA",
                "z-index": "100000",
                "min-width": "100%",
                "width": "100%",
                "min-height": "100%",
                "height": "100%"
            });

            $('<iframe>')
                .attr('src', checkoutUrl)
                .css({'border': 'none', 'width': '100%', 'height': '100%'})
                .attr('id', checkout_div + "-Frame")
                .attr('allowpaymentrequest', 'true')
                .appendTo("#" + checkout_div);

            $('html > head').append($('<style type = "text/css">.checkoutHtmlStyleFromiFrame{position:fixed; width:100%; overflow:hidden !important; }</style>'));
            $("html").addClass("checkoutHtmlStyleFromiFrame");
        }

        return;
    };

    monerisCheckout.prototype.startCheckoutHandler = function (response) {
        if (response.success == 'true') {
            console.log(response.url);
            //insert iframe into div #moneris-checkout
        }
        else {
            callbacks.error_event(response.error);
        }
    };

    monerisCheckout.prototype.closeCheckout = function () {
        $("#" + checkout_div).html("");
        $("html").removeClass("checkoutHtmlStyle");
        $("#" + checkout_div).css({"position": "static", "width": "0px", "height": "0px", "min-height": "0px"});

    };

    monerisCheckout.prototype.sendPostMessage = function (request) {
        var frameRef = document.getElementById(checkout_div + "-Frame").contentWindow;
        frameRef.postMessage(request, request_url + 'chkt/display/request.php');
        return false;
    };

    monerisCheckout.prototype.receivePostMessage = function (resp) {
        console.log("POST MESSAGE: " + JSON.stringify(resp));

        try {
            var response_json = resp.data;
            var respObj = JSON.parse(response_json);

            if (respObj.rev_action == 'height_change') {
                console.log("this is new height:" + respObj.outerHeight);
                $("#" + checkout_div + "-Frame").css({"height": respObj.outerHeight + "px"});
                //	$("#"+checkout_div).css({"height":  respObj.outerHeight + "px"});

            }
            else {
                var callback = callbacks[respObj["handler"]];

                if (typeof callback === "function") {
                    callback(response_json);
                }
            }
        }
        catch (e) {
            console.log("got a non standard post message");
        }
    };

    monerisCheckout.prototype.setNewShippingRates = function (json) {
        this.sendPostMessage(json);
    };

    return monerisCheckout;
})();


