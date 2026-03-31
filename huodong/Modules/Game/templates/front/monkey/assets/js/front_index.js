requirejs.config({
    baseUrl: '.',
    paths: {
        jquery: '/assets/js/jquery-3.3.1.min',
        hotkeys:'/wall/themes/meepo/assets/plugs/hotkeys-master/dist/hotkeys.min',
        bindhotkeys:'/wall/themes/meepo/assets/js/bindhotkeys',
    },
    shim:{
        bindhotkeys:{
            deps:['hotkeys']
        }
    }
});
requirejs(['jquery','bindhotkeys'],function($){
    window.getdatatimer=null;
    var gamestarted_at=0;
    var timepercent=0;
    var round_welcome_elm=$('.welcome_box');
    $(document).ready(function(){
        if(!GAMECONFIG){
            alert('后台没有设置游戏,请先设置一轮游戏哦');
            return ;
        }
        //初始化菜单
        initNavBar();
        //初始化游戏界面
        getData();
        checkingStatus();
    });
    function renderStartPage(){
        var qrcode=$(window.top.document).find('#box img').attr('src');
        qrcode=qrcode.replace('./','/');
        qrcode=qrcode.trim();
        round_welcome_elm.find('img.qrimg').attr("src",qrcode);
        round_welcome_elm.slideDown();
    }
    function renderJoinCounter(num){
        $('.main_right .joinNum').html(''+num+'人');
    }
    radarusers=[];
    radaruserindex=0;
    function renderRadar(){
        if(radarusers.length<=0){
            return;
        }
        $('#Audio_NewPlayer')[0].play();
        var user=radarusers.shift();
        var userhtml ='<img class="avatar default" src="' + user.avatar + '">';
        if($('.avatar.default').length>10){
            $('.avatar.default').eq(0).animate({opacity: 0},1000,function(){
              $(this).remove();
              $(userhtml).appendTo(".list").animate({
                opacity: 0.7
              },
              500);
            });
        }else{
            $('.avatar.default').eq(radaruserindex).attr('src',user.avatar);
        }
        radaruserindex++;
    }
    
    //上一个请求返回
    laststatus=0;
    function showStartPage(users){
        if(laststatus!= GAMECONFIG.status){
            laststatus=GAMECONFIG.status;
            //todo:显示界面和绑定按钮
            renderStartPage();
        }
        radarusers=radarusers.concat(users.userlist);
        //todo:显示加入的人数和雷达
        renderJoinCounter(users.num);
        renderRadar();
    }
    function hideStartPage(){
        round_welcome_elm.slideUp();
    }

    function showGamePage(users){
        if(laststatus!= GAMECONFIG.status){
            laststatus=GAMECONFIG.status;
            //隐藏开始界面
            hideStartPage();
            //重新加载按钮
            initNavBar();
            Track.resize();
            
        }
        if(GAMECONFIG.themeconfig.durationtype==1){
            gamestarted_at=gamestarted_at==0?Math.round((new Date().getTime())/1000):gamestarted_at;
            var now=Math.round((new Date().getTime())/1000);
            var timepassed=now-gamestarted_at;
            timepercent=timepercent>=1?1:timepassed/GAMECONFIG.themeconfig.duration;
        }else{
            timepercent=1;
        }
        if(users){
            for(var i=0,l=users.length;i<l;i++){
                Track.showprogress(i,users[i]);
                maxpoints=maxpoints>parseInt(users[i]['point'])?maxpoints:parseInt(users[i]['point']);
            }
        }

        if(GAMECONFIG.themeconfig.durationtype==1){
            if(timepercent==1){
                stopGame();
            }
        }else{
            if(parseInt(maxpoints) >=parseInt(GAMECONFIG.themeconfig.duration)){
                stopGame();
            }
        }
    }
    
    function showEndPage(users){
        if(laststatus!= GAMECONFIG.status){
            laststatus=GAMECONFIG.status;
            //todo:显示结果界面
            EndPage.show(users);
        }
    }
    // 检测游戏的动作
    function checkingStatus(){
        var state=window.sessionStorage['game'];
        if(state=='start'){
            window.sessionStorage['game']='idle';
            startGame();
        }
        if(state=='reset'){
            window.sessionStorage['game']='idle';
            resetGame();
        }
        setTimeout(checkingStatus,500);
    }
    /**
     * 开始游戏
     */
    function startGame(){
        ajaxPostData('start').done(function(json){
            showGamePage();
        });
    }
    /**
     * 重置游戏
     */
    function resetGame(){
        ajaxPostData('reset').done(function(json){
            clearTimeout(getdatatimer);
            getdatatimer=null;
            alert(json.message);
            if(json.code>0){
                setTimeout(function(){
                    window.location.reload();
                },3000);
            }
            
        });
    }
    /**
     * 只有是以时间为单位的游戏结束时才会使用这个游戏停止功能
     */
    function stopGame(){
        ajaxPostData('stop').done(function(json){if(json.code<0){alert(json.message)}});
    }
    function getData(){
        ajaxGetData().done(function(json){
            if(json.code>0){
                GAMECONFIG.status=json.config.status
                if(json.config.status==1){
                    showStartPage(json.users);
                    // clearTimeout(getdatatimer);
                    getdatatimer=setTimeout(getData,1000);
                }
                if(json.config.status==2){
                    // clearTimeout(getdatatimer);
                    getdatatimer=setTimeout(getData,1000);
                    //显示比赛进度
                    showGamePage(json.users);
                    // Track.showprogress()
                }
                if(json.config.status==3){
                    showEndPage(json.users);
                    // clearTimeout(getdatatimer);
                }
                return;
            }

        }).fail(function(){
            alert('您的网络不稳定,点确定后,系统会重新连接,如果再次弹出这个提示,请检查网络,或者更换网络');
            clearTimeout(getdatatimer);
            getdatatimer=setTimeout(getData,1000);
        });
    }

    /**
     * 获取数据
     */
    var ajaxGetData=function(){
        return $.ajax({
            "url":"/Modules/module.php?m=game&c=front&a=ajaxGetData",
            "type":"get",
            'dataType':"json",
        })
    }
    /**
     * 提交数据
     * 
     * @param string action start stop reset 
     */
    var ajaxPostData=function(action){
        return $.ajax({
            "url":"/Modules/module.php?m=game&c=front&a=ajaxPostData",
            "type":"post",
            "dataType":"json",
            "data":{"action":action}
        });
    }
    //处理右下角菜单
    function initNavBar(){
        var opts={};
        //未开的游戏 有个开始按钮
        if(GAMECONFIG.status==1){
            opts.btn_start=function(){
                window.sessionStorage['game']='start';
                // alert(1);
                // startGame();
            };
        }
        //已经开始的游戏,有个重玩按钮
        //已经结束的按钮,有重玩和排行按钮
        if(GAMECONFIG.status==2 || GAMECONFIG.status==3){
            opts.btn_reset=function(){
                // resetGame();
                window.sessionStorage['game']='reset';
            };
        }

        // if(GAMECONFIG.previd>0){
        //     opts.btn_prev=function(){
        //         window.location.href="/Modules/module.php?m=game&c=front&a=index&id="+GAMECONFIG.previd;
        //     };
        // }
        // if(GAMECONFIG.nextid>0){
        //     opts.btn_next=function(){
        //         window.location.href="/Modules/module.php?m=game&c=front&a=index&id="+GAMECONFIG.nextid;
        //     }
        // }
        window.top.minibar.init(opts);
        // window.sessionStorage['bgpath']=GAMECONFIG.themeconfig.bg_path;
        window.sessionStorage['bgmusic']=GAMECONFIG.themeconfig.bgmusic_path+'|'+GAMECONFIG.themeconfig.bgmusic_switch;

        if(CONFIGS.length>0){
            var btns=[];
            var icons=['iconyyy','iconxqc','iconhzsq','iconsq','iconjzsf','iconsaipao','iconslz','iconsc','iconsm','iconst','iconqbtzj','icon61'];
            var themename=['iconyyy','默认汽车','猴子爬树','数钱游戏','金猪送福','赛跑','赛龙舟','赛车','赛马','游艇','丘比特之箭','欢乐六一'];
            for(var i=0,l=CONFIGS.length;i<l;i++){
                var iscurrent=GAMECONFIG.id==CONFIGS[i]['id']?1:2;
                btns[i]={"name":themename[CONFIGS[i]['themeid']],"action":"/Modules/module.php?m=game&c=front&a=index&id="+CONFIGS[i]['id'],"icon":icons[CONFIGS[i]['themeid']],'current':iscurrent};
            }
            window.top.roundbar().init(btns);
        }
    }
    var maxpoints=0;
    var Track={
        "tracklist_elm":null,
        "track_elm":null,
        "width":0,
        "height":0,
        "size":0,
        
        "init":function(){
            
            if(this.tracklist_elm==null){
                this.tracklist_elm=$(".houzik");
                this.track_elm=this.tracklist_elm.children('div');
            }
            this.height=this.tracklist_elm.height();
        },
        "resize":function(){
            this.init();
            $('.pashuMain .houzi_user').removeClass('houzi_user');
        },
        "ph_arr":[],
        "oldprogress":[],
        "showprogress":function (index,player){
            //console.log(player);
            if(this.oldprogress[index]==undefined){
                this.oldprogress[index]=0;
            }
            var progress=this.calcProgress(player['point']);
            
            if(this.tracklist_elm==null){
                this.resize();
            }
            var max_top=this.height-255;
            
            $(this.track_elm[index]).find('.topk').find('span').text(player['nickname']);
            $(this.track_elm[index]).find('.mshakeuser_img').attr('src',player['avatar']);
            var now_top = progress*max_top/100;
            $(this.track_elm[index]).css('bottom',now_top+'px');
            $(this.track_elm[index]).find('.bottomhz').find('img').attr('src',"/Modules/Game/templates/front/monkey/assets/images/hz.gif");
            if(now_top==max_top){
                var guozi = $(this.track_elm[index]).find('.guozik');
                $(guozi).css('opacity', 1).css('top', max_top+'px');
            }
        },
        "maxpoint":0,
        "calcProgress":function(point){
            //point 摇一摇指数
            //durationtype 1时间2分数
            //duration 条件
            if(GAMECONFIG.themeconfig.durationtype==1){
                this.maxpoint=this.maxpoint>parseInt(point)? this.maxpoint:parseInt(point);
                var percent=Math.round(point/this.maxpoint*timepercent*100);
                console.warn('point:'+point+' maxpoint:'+this.maxpoint+' percent:'+ timepercent);
                return percent;
            }else{
                var percent=Math.round(point/GAMECONFIG.themeconfig.duration*100);
                return percent>=100?100:percent;
            }

        }
    };
    var EndPage={
        "result_layer":null,
        "result_label":null,
        "result_cap":null,
        "player_tp":$("<div class='player'><div class='head'></div><div class='nickname'></div></div>"),
        "showGameoverPage":function(){
            $(".paomabeijing2,.paomabeijing,.tracklist,.player .lunzia,.player .lunzib").removeClass('okplay');
            this.result_layer.show().animateControl('bounceIn');
            $('#Audio_Gameover')[0].play();
        },
        "show":function(users){
            var self=this;           
            this.result_layer=$('.rank_box');
            winner_num=users.num;
            if(users.num>10){
                setTimeout(function(){self.showScore();},1000);
                return;
            }
            this.showGameoverPage();
            showrank(users.users);
            function showrank(users){
                var usernum=users.length;
                if (usernum >= 1 && users[0]) {
                    var rank=$('.rank1');
                    rank.find('img').attr('src',users[0]["avatar"]);
                    rank.find('p').text(users[0]["nickname"]);
                }
                if (usernum >= 2 && users[1]) {
                    var rank=$('.rank2');
                    rank.find('img').attr('src',users[1]["avatar"]);
                    rank.find('p').text(users[1]["nickname"]);
                }
                if (usernum >= 3 && users[2]) {
                    var rank=$('.rank3');
                    rank.find('img').attr('src',users[2]["avatar"]);
                    rank.find('p').text(users[2]["nickname"]);
                }
                if(usernum>3){
                    var rank_others=$('.rank_others').find('li');
                    for(var i=3,l=usernum;i<l;i++){
                        $(rank_others[i-3]).find('.avarta').attr('src',users[i]["avatar"]);
                        $(rank_others[i-3]).find('p').text(users[i]["nickname"]);
                    }
                }
            }
        },
        'showScore':function(){
            var url = '/Modules/module.php?m=game&c=front&a=gameResult';
            showPage(url);
        }
    };
    showPage = function(c) {
        var d = $('<div class="frame-dialog"><div class="phbphb" id="phbphb"><img src="/Modules/Game/templates/front/racing/assets/images/phb.png" class="phbtop" ><div class="phbk"><div class="phbbiaok"><iframe frameborder="0" src="' + c + '"></iframe></div></div></div></div>');
        d.css({"z-index":999});
        d.appendTo("body").show();
    }
    ;(function($) {
        $.fn.extend({
            animateControl: function (a,fn) {
                this.addClass('animated ' + a).one('webkitAnimationEnd mozAnimationEnd MSAnimationEnd oanimationend animationend', function() {
                    if(fn){
                        fn();
                    }
                    $(this).removeClass('animated ' + a);
                });
                return this;
            }
        });
    })(jQuery);
});