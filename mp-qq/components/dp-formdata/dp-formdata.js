(global["webpackJsonp"]=global["webpackJsonp"]||[]).push([["components/dp-formdata/dp-formdata"],{"268f":function(t,e,n){"use strict";(function(t){Object.defineProperty(e,"__esModule",{value:!0}),e.default=void 0;getApp();var n={data:function(){return{pre_url:getApp().globalData.pre_url}},props:{menuindex:{default:-1},params:{},data:{}},methods:{phone:function(e){var n=e.currentTarget.dataset.phone;t.makePhoneCall({phoneNumber:n,fail:function(){}})},openLocation:function(e){var n=parseFloat(e.currentTarget.dataset.latitude),a=parseFloat(e.currentTarget.dataset.longitude),r=e.currentTarget.dataset.address;n&&a&&t.openLocation({latitude:n,longitude:a,name:r,scale:13})}}};e.default=n}).call(this,n("a821")["default"])},"490f6":function(t,e,n){"use strict";var a=n("e2f0"),r=n.n(a);r.a},bd3e:function(t,e,n){"use strict";var a;n.d(e,"b",(function(){return r})),n.d(e,"c",(function(){return u})),n.d(e,"a",(function(){return a}));var r=function(){var t=this,e=t.$createElement;t._self._c},u=[]},c117:function(t,e,n){"use strict";n.r(e);var a=n("268f"),r=n.n(a);for(var u in a)"default"!==u&&function(t){n.d(e,t,(function(){return a[t]}))}(u);e["default"]=r.a},e2c2:function(t,e,n){"use strict";n.r(e);var a=n("bd3e"),r=n("c117");for(var u in r)"default"!==u&&function(t){n.d(e,t,(function(){return r[t]}))}(u);n("490f6");var o,c=n("f0c5"),f=Object(c["a"])(r["default"],a["b"],a["c"],!1,null,null,null,!1,a["a"],o);e["default"]=f.exports},e2f0:function(t,e,n){}}]);
;(global["webpackJsonp"] = global["webpackJsonp"] || []).push([
    'components/dp-formdata/dp-formdata-create-component',
    {
        'components/dp-formdata/dp-formdata-create-component':(function(module, exports, __webpack_require__){
            __webpack_require__('a821')['createComponent'](__webpack_require__("e2c2"))
        })
    },
    [['components/dp-formdata/dp-formdata-create-component']]
]);
