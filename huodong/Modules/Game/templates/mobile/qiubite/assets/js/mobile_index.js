requirejs.config({
    baseUrl: '.',
    paths: {
        jquery: '/assets/js/jquery-3.3.1.min',
        weixin: 'https://res.wx.qq.com/open/js/jweixin-1.2.0',
        apptool: '/mobile/template/app/js/app_tool',
        shake: '/mobile/template/app/js/shake',
        bubblybg: '/mobile/template/app/js/bubbly-bg',
        jqweui: '/mobile/template/app/js/jquery-weui.min'
    },
    shim: {
        apptool: {
            deps: ['jquery']
        },
        bubblybg: {
            deps: []
        },
        jqweui: {
            deps: ['jquery']
        }
    }
});
requirejs(['jquery', 'shake','weixin', 'apptool', 'bubblybg', 'jqweui'], function ($, Shake,wx) {
    var ajaxGetData = function () {
        return $.ajax({
            "url": "/Modules/module.php?m=game&c=mobile&a=ajaxGetData",
            "type": "get",
            "dataType": "json"
        });
    }
    oldstatus = 0;
    checkstatustimer = null;
    isbind = false;
    function checkStatus() {
        ajaxGetData().done(function (json) {
            
            if (json.code > 0) {
                if (json.data.id != GAMECONFIG.id) {
                    window.location.reload();
                    return;
                }
                if(JOINSTATUS==3){
                    _loading_toast._longshow("您已经得过奖了哦，请把中奖机会留给其他人吧！");
                    return ;
                }
                GAMECONFIG.status = json.data.status;
                if (json.data.status == 1) {
                    if(oldstatus==3 || oldstatus==2){
                        window.location.reload();
                    }
                    if (oldstatus != json.data.status) {
                        _loading_toast._longshow("游戏还未开始,请等待按照主持人指挥操作");
                    }
        
                }
                if (json.data.status == 2) {
                    _loading_toast._show('游戏进行中...');
                    if (oldstatus != json.data.status) {
                        //绑定摇晃事件
                        startlisten();
                    }
            
                }
                if (json.data.status == 3) {
                    if (oldstatus != json.data.status) {
                        _loading_toast._longshow("本轮游戏已经结束,后面可能还有游戏哦!");
                        endlisten();
                    }
         
                }
                oldstatus = json.data.status;
            }
        });
        checkstatustimer = setTimeout(checkStatus, 3000);
    }

    window.shakeaudio = null;
    function playshakesound() {
        if (typeof (wx) != null) {
            $(window.shakeaudio).on('canplaythrough',function(){
                if (window.shakeaudio.paused || window.shakeaudio.ended) {
                    window.shakeaudio.play();
                }
            })
        } else {
            $(document).on("WeixinJSBridgeReady", function () {
                $(window.shakeaudio).load();
                $(window.shakeaudio).on('canplaythrough', function () {
                    if (window.shakeaudio.paused || window.shakeaudio.ended) {
                        window.shakeaudio.play();
                    }
                });
            });
        }
    }
    var customerevent='shake';
    // var customerevent='click touchstart';
    var shakeevent=new Shake({
        threshold: 15, // optional shake strength threshold
        timeout: 300 // 
    });
    //开始计数
    var startrunning = false;
    var stopshakingtimer = null;
    //上一次摇晃时间
    var running = function () {
        playshakesound();
        startrunning = true;
        clearTimeout(stopshakingtimer);
        //停止摇晃3秒后把startrunning设置未false
        stopshakingtimer = setTimeout(function () {
            startrunning = false;
        }, 3000);
    }

    //一个请求没有结束不进行第2个请求
    var isrunning = false;
    var watchrunningstatustimer = null;
    //检测是否需要提交数据
    function watchrunningstatus() {
        watchrunningstatustimer = setTimeout(function () {
            watchrunningstatus();
        }, 3000);

        //检查是否停止摇晃
        if (startrunning == false) {
            return;
        }
        //检查上一个请求是否在进行种
        if (isrunning == true) {
            return;
        }
        //开始请求前把请求中得状态设置为true
        isrunning = true;
        if(JOINSTATUS==1){
            ajaxPostData().done(function (json) { }).always(function () {
                //结束一个请求后，把请求得状态设置为false，以便发起下一个请求
                isrunning = false;
            });
        }else{
            isrunning = false;
        }
        // ajaxPostData().done(function (json) { }).always(function () {
        //     //结束一个请求后，把请求得状态设置为false，以便发起下一个请求
        //     isrunning = false;
        // });
    }

    //返回一个Deferred对象
    var ajaxPostData = function () {
        var points = 2 + Math.floor(Math.random() * 5);
        return $.ajax({
            "url": "/Modules/module.php?m=game&c=mobile&a=ajaxPostData",
            "data": { 'userid': USERINFO.id, 'points': points },
            "type": "post",
            "dataType": "json"
        })
    };

    //开始监听摇晃事件
    function startlisten() {
        if (isbind == false) {
            shakeevent.start();
            $(window).on(customerevent, running);
            watchrunningstatus();
            isbind = true;
        }

    };
    //结束监听摇晃事件
    function endlisten() {
        // if (running_timer != null) {
        //     clearTimeout(running_timer);
        // }
        if (watchrunningstatustimer != null) {
            clearTimeout(watchrunningstatustimer);
        }
        if (isbind == true) {
            $(window).off(customerevent, running);
            shakeevent.stop();
            isbind = false;
        }
    }
    $('document').ready(function () {
        $('<img src="'+GAMECONFIG.themeconfig.mobilebg_path+'" style="width:100%; height:100%;z-index:-1;"/>').appendTo('body');
        $('.shake-icon').css({'background':'url('+GAMECONFIG.themeconfig.mobileimg_path+') no-repeat','background-size':'100% 100% '});
        window.shakeaudio = $('#ShakeAudio')[0];
        $(document).on("WeixinJSBridgeReady", function () {
            $(window.shakeaudio).load();
        });
        $(".dropdown-menu").click(function () {
            if (!$("#pop_nav").hasClass('weui-popup-container-visible')) {
                $("#pop_nav").popup();
                $('.weui-popup-overlay').show();
            }
        });
        $(document).on("WeixinJSBridgeReady", function () {
            wx.call('hideOptionMenu');
        });
        checkStatus();
        // setInterval(checkStatus,3000);
    });
});