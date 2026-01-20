(global["webpackJsonp"]=global["webpackJsonp"]||[]).push([["components/dp-channelslive/dp-channelslive"],{"037f":function(n,e,t){"use strict";t.r(e);var a=t("aeaa"),r=t.n(a);for(var u in a)"default"!==u&&function(n){t.d(e,n,(function(){return a[n]}))}(u);e["default"]=r.a},"4a3e":function(n,e,t){"use strict";var a=t("e0df"),r=t.n(a);r.a},aeaa:function(n,e,t){"use strict";Object.defineProperty(e,"__esModule",{value:!0}),e.default=void 0;getApp();var a={props:{params:{},data:{}},data:function(){return{Height:"",hastabbar:!1,liveInfo:[]}},mounted:function(){var n=this;wx.getChannelsLiveInfo({finderUserName:n.params.channelsLive,success:function(e){"getChannelsLiveInfo:ok"==e.errMsg&&(n.liveInfo=e)},fail:function(n){console.log(n)}})}};e.default=a},d5a7:function(n,e,t){"use strict";var a;t.d(e,"b",(function(){return r})),t.d(e,"c",(function(){return u})),t.d(e,"a",(function(){return a}));var r=function(){var n=this,e=n.$createElement;n._self._c},u=[]},e0df:function(n,e,t){},f436:function(n,e,t){"use strict";t.r(e);var a=t("d5a7"),r=t("037f");for(var u in r)"default"!==u&&function(n){t.d(e,n,(function(){return r[n]}))}(u);t("4a3e");var f,o=t("f0c5"),c=Object(o["a"])(r["default"],a["b"],a["c"],!1,null,null,null,!1,a["a"],f);e["default"]=c.exports}}]);
;(global["webpackJsonp"] = global["webpackJsonp"] || []).push([
    'components/dp-channelslive/dp-channelslive-create-component',
    {
        'components/dp-channelslive/dp-channelslive-create-component':(function(module, exports, __webpack_require__){
            __webpack_require__('543d')['createComponent'](__webpack_require__("f436"))
        })
    },
    [['components/dp-channelslive/dp-channelslive-create-component']]
]);
