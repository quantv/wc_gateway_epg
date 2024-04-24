!function () {
    "use strict";
    console.log('loading payment methods');
    var t = window.wp.element, e = window.wp.htmlEntities, a = window.wp.i18n, n = window.wc.wcBlocksRegistry, i = window.wc.wcSettings;
    const l = () => {
        const t = (0, i.getSetting)("epg_data", null); if (!t) throw new Error("EPG initialization data is not available");

        console.log('data', t);

        return t
    };
    var o;
    const r = () => (0, e.decodeEntities)(l()?.description || "");
    const title = () => (0, e.decodeEntities)(l()?.title || "");
    (0, n.registerPaymentMethod)
        ({
            name: "epg",
            label: (0, t.createElement)('div', {},
                (0, t.createElement)(title, null),
            ),
            ariaLabel: (0, a.__)("EPG payment method", "woocommerce-gateway-epg"), canMakePayment: () => !0,
            content: (0, t.createElement)('div', {},
                (0, t.createElement)(r, null),
            ),
            edit: (0, t.createElement)('div', {},
                (0, t.createElement)(r, null),
            ),
            supports: { features: null !== (o = l()?.supports) && void 0 !== o ? o : [] }
        })
}();
