(global["webpackJsonp"]=global["webpackJsonp"]||[]).push([["components/parse/components/wxParseVideo"],{"0a6f":function(t,n,e){"use strict";e.r(n);var a=e("b5bc"),o=e.n(a);for(var u in a)"default"!==u&&function(t){e.d(n,t,(function(){return a[t]}))}(u);n["default"]=o.a},"7d70":function(t,n,e){},a5ad:function(t,n,e){"use strict";e.r(n);var a=e("de45"),o=e("0a6f");for(var u in o)"default"!==u&&function(t){e.d(n,t,(function(){return o[t]}))}(u);e("c448");var c,r=e("f0c5"),i=Object(r["a"])(o["default"],a["b"],a["c"],!1,null,null,null,!1,a["a"],c);n["default"]=i.exports},b5bc:function(t,n,e){"use strict";(function(t){Object.defineProperty(n,"__esModule",{value:!0}),n.default=void 0;var e={name:"wxParseVideo",props:{node:{}},data:function(){return{playState:!0,videoStyle:"width: 100%;"}},methods:{play:function(){console.log("点击了video 播放"),this.playState=!this.playState}},mounted:function(){var n=this;t.$on("slideMenuShow",(function(t){console.log("捕获事件："+t),"show"==t&&n.playState&&(n.playState=!1)}))}};n.default=e}).call(this,e("543d")["default"])},c448:function(t,n,e){"use strict";var a=e("7d70"),o=e.n(a);o.a},de45:function(t,n,e){"use strict";var a;e.d(n,"b",(function(){return o})),e.d(n,"c",(function(){return u})),e.d(n,"a",(function(){return a}));var o=function(){var t=this,n=t.$createElement;t._self._c},u=[]}}]);
;(global["webpackJsonp"] = global["webpackJsonp"] || []).push([
    'components/parse/components/wxParseVideo-create-component',
    {
        'components/parse/components/wxParseVideo-create-component':(function(module, exports, __webpack_require__){
            __webpack_require__('543d')['createComponent'](__webpack_require__("a5ad"))
        })
    },
    [['components/parse/components/wxParseVideo-create-component']]
]);
