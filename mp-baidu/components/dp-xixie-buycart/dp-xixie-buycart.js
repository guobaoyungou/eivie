(global["webpackJsonp"]=global["webpackJsonp"]||[]).push([["components/dp-xixie-buycart/dp-xixie-buycart"],{"207c":function(t,n,r){"use strict";r.r(n);var u=r("8093"),e=r.n(u);for(var a in u)"default"!==a&&function(t){r.d(n,t,(function(){return u[t]}))}(a);n["default"]=e.a},"617d":function(t,n,r){"use strict";var u=r("f4d7"),e=r.n(u);e.a},"6c914":function(t,n,r){"use strict";r.r(n);var u=r("bf84"),e=r("207c");for(var a in e)"default"!==a&&function(t){r.d(n,t,(function(){return e[t]}))}(a);r("617d");var c,o=r("f0c5"),f=Object(o["a"])(e["default"],u["b"],u["c"],!1,null,null,null,!1,u["a"],c);n["default"]=f.exports},8093:function(t,n,r){"use strict";Object.defineProperty(n,"__esModule",{value:!0}),n.default=void 0;var u=getApp(),e={data:function(){return{pre_url:""}},props:{color:{default:""},colorrgb:{default:""},cartnum:{default:"0"},cartprice:{default:"0"}},mounted:function(){var t=this;t.pre_url=u.globalData.pre_url},methods:{gobuy:function(){var t=this,n=t.cartnum,r=t.cartprice;n<=0||r<=0?u.alert("请选择要清洗的商品"):u.goto("/xixie/buy?gotype=all")}}};n.default=e},bf84:function(t,n,r){"use strict";var u;r.d(n,"b",(function(){return e})),r.d(n,"c",(function(){return a})),r.d(n,"a",(function(){return u}));var e=function(){var t=this,n=t.$createElement;t._self._c},a=[]},f4d7:function(t,n,r){}}]);
;(global["webpackJsonp"] = global["webpackJsonp"] || []).push([
    'components/dp-xixie-buycart/dp-xixie-buycart-create-component',
    {
        'components/dp-xixie-buycart/dp-xixie-buycart-create-component':(function(module, exports, __webpack_require__){
            __webpack_require__('5486')['createComponent'](__webpack_require__("6c914"))
        })
    },
    [['components/dp-xixie-buycart/dp-xixie-buycart-create-component']]
]);
