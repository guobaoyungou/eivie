(global["webpackJsonp"]=global["webpackJsonp"]||[]).push([["components/dp-xixie-mendian/dp-xixie-mendian"],{"0335":function(e,n,t){"use strict";(function(e){Object.defineProperty(n,"__esModule",{value:!0}),n.default=void 0;var t=getApp(),a={data:function(){return{}},props:{mendian_data:{default:""}},methods:{selAddress:function(){var e=this,n=e.mendian_data;if(n&&n.m_address){var a=encodeURIComponent("/pages/index/index");t.goto("/pages/address/address?fromPage="+a+"&type=1")}else this.$emit("changePopupAddress",!0)},selMendian:function(){var e=this,n=e.mendian_data;n&&n.m_address||this.$emit("changePopupAddress",!0)},callMobile:function(n){var a=n.currentTarget.dataset.mobile;a?e.makePhoneCall({phoneNumber:a}):t.alert("暂无可拨打电话")}}};n.default=a}).call(this,t("a821")["default"])},"2ea4":function(e,n,t){"use strict";t.r(n);var a=t("0335"),r=t.n(a);for(var d in a)"default"!==d&&function(e){t.d(n,e,(function(){return a[e]}))}(d);n["default"]=r.a},"8af6":function(e,n,t){"use strict";t.r(n);var a=t("e835"),r=t("2ea4");for(var d in r)"default"!==d&&function(e){t.d(n,e,(function(){return r[e]}))}(d);var i,u=t("f0c5"),o=Object(u["a"])(r["default"],a["b"],a["c"],!1,null,null,null,!1,a["a"],i);n["default"]=o.exports},e835:function(e,n,t){"use strict";var a;t.d(n,"b",(function(){return r})),t.d(n,"c",(function(){return d})),t.d(n,"a",(function(){return a}));var r=function(){var e=this,n=e.$createElement;e._self._c},d=[]}}]);
;(global["webpackJsonp"] = global["webpackJsonp"] || []).push([
    'components/dp-xixie-mendian/dp-xixie-mendian-create-component',
    {
        'components/dp-xixie-mendian/dp-xixie-mendian-create-component':(function(module, exports, __webpack_require__){
            __webpack_require__('a821')['createComponent'](__webpack_require__("8af6"))
        })
    },
    [['components/dp-xixie-mendian/dp-xixie-mendian-create-component']]
]);
