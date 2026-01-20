//省市区，会员的区域代理选择的样式
//1、引入页面head头引入address3.js和 selectarea.css ，<script src="__STATIC__/admin/js/address3.js"></script> <link rel="stylesheet" type="text/css" href="__STATIC__/admin/css/selectarea.css?v=202410" media="all"> 
//2、对应加入div标签查找带 class="area" 和 class="alert"的两个div标签 ，必须加
//3、页面在引入该文件前定义  var area_agent_info = {$area_agent_info};  ，{$area_agent_info}是后台映射的值例如：["北京市","天津市","山东省-临沂市-罗庄区"]
//4、html底部引入该文件，提交保存时增加 field.areadata = dataInfo; ，详见 view/maidan_invite/index.html 
var dataList = $.extend(true,provinceList,dataList);
//dataList.splice(0,1);
var areaAlert = true,
    provinceIndex = 0,
    cityIndex = 0,
    dataInfo = area_agent_info??[],
    indexInfo = [];

function setIndex(){
    for(let i=0;i<dataInfo.length;i++){
        if(tagCount(dataInfo[i]).toString()=='0'){
            for(let j=0;j<dataList.length;j++){
                if(dataInfo[i]==dataList[j].name){
                    let item = {
                        'provinceIndex': j.toString()
                    }
                    indexInfo.push(item)
                }
            }
        }else if(tagCount(dataInfo[i]).toString()=='1'){
            let front = getAfter(dataInfo[i],0);
            let after = getAfter(dataInfo[i],1);
            let item = {
                'provinceIndex': '',
                'cityIndex': ''
            }
            for(let j=0;j<dataList.length;j++){
                if(front==dataList[j].name){
                    item.provinceIndex = j.toString()
                }
            }
            for(let k=0;k<dataList[item.provinceIndex].cityList.length;k++){
                if(after==dataList[item.provinceIndex].cityList[k].name){
                    item.cityIndex = k.toString()
                }
            }
            indexInfo.push(item)
        }else if(tagCount(dataInfo[i]).toString()=='2'){
            let front = getFront(dataInfo[i],0);
            let center = getCenter(dataInfo[i]);
            let after = getAfter(dataInfo[i],1);
            let item = {
                'provinceIndex': '',
                'cityIndex': '',
                'areaIndex': ''
            }
            for(let j=0;j<dataList.length;j++){
                if(front==dataList[j].name){
                    item.provinceIndex = j.toString()
                }
            }
            for(let k=0;k<dataList[item.provinceIndex].cityList.length;k++){
                if(center==dataList[item.provinceIndex].cityList[k].name){
                    item.cityIndex = k.toString()
                }
            }
            for(let l=0;l<dataList[item.provinceIndex].cityList[item.cityIndex].areaList.length;l++){
                if(after==dataList[item.provinceIndex].cityList[item.cityIndex].areaList[l]){
                    item.areaIndex = l.toString()
                }
            }
            indexInfo.push(item);
        }
    }
}
setIndex();
function getFront(obj,state){
    var index=obj.indexOf("\-");
    if(state==0){
        obj=obj.substring(0,index);
    }else {
        obj=obj.substring(index+1,obj.length);
    }
    return obj;
}
function getCenter(obj){
    var str = obj;
    str = str.match(/-(\S*)-/)[1];
    return str;
}
function getAfter(obj,state) {
    var index=obj.lastIndexOf("\-");
    if(state==0){
        obj=obj.substring(0,index);
    }else {
        obj=obj.substring(index+1,obj.length);
    }
    return obj;
}
function tagCount(obj){
    let str = obj;
    let array = str.match(/-/g);
    let count = array ? array.length: 0;
    return count;
}
function createProvince(){
    $(".select_province").empty();
    for(let i=0;i<dataList.length;i++){
        $(".select_province").append("<div class='alert_item' index='"+ i +"'><div class='alert_icon province_select'><i class='layui-icon layui-icon-ok' style='font-size: 12px; color: #fff;font-weight: bold;'></i></div><div class='alert_text province_more'>"+ dataList[i].name +"</div><i class='layui-icon layui-icon-triangle-r province_more'></i></div>")
    }
    for(let i=0;i<dataList.length;i++){
        for(let j=0;j<indexInfo.length;j++){
            if(i == indexInfo[j].provinceIndex){
                $(".province_select").eq(indexInfo[j].provinceIndex).addClass('alert_active');
            }
        }
    }
}
function createCity(){
    $(".select_city").empty();
    for(let i=0;i<dataList[provinceIndex].cityList.length;i++){
        $(".select_city").append("<div class='alert_item' index='"+ i +"'><div class='alert_icon city_select'><i class='layui-icon layui-icon-ok' style='font-size: 12px; color: #fff;font-weight: bold;'></i></div><div class='alert_text city_more'>"+ dataList[provinceIndex].cityList[i].name +"</div><i class='layui-icon layui-icon-triangle-r city_more'></i></div>")
    }
    for(let i=0;i<dataList[provinceIndex].cityList.length;i++){
        for(let j=0;j<indexInfo.length;j++){
            if(provinceIndex == indexInfo[j].provinceIndex){
                if(i == indexInfo[j].cityIndex){
                    $(".city_select").eq(indexInfo[j].cityIndex).addClass('alert_active');
                }
            }
        }
    }
}
function createArea(){
    $(".select_area").empty();
    for(let i=0;i<dataList[provinceIndex].cityList[cityIndex].areaList.length;i++){
        $(".select_area").append("<div class='alert_item' index='"+ i +"'><div class='alert_icon area_select'><i class='layui-icon layui-icon-ok' style='font-size: 12px; color: #fff;font-weight: bold;'></i></div><div class='alert_text'>"+ dataList[provinceIndex].cityList[cityIndex].areaList[i] +"</div></div>")
    }
    for(let i=0;i<dataList[provinceIndex].cityList[cityIndex].areaList.length;i++){
        for(let j=0;j<indexInfo.length;j++){
            if(provinceIndex == indexInfo[j].provinceIndex){
                if(cityIndex == indexInfo[j].cityIndex){
                    if(i == indexInfo[j].areaIndex){
                        $(".area_select").eq(indexInfo[j].areaIndex).addClass('alert_active');
                    }
                }
            }
        }
    }
}
function createTag(){
    $(".area_content").empty();
    if(dataInfo.length){
        for(let i=0;i<dataInfo.length;i++){
            $(".area_content").append("<div class='area_item'>"+ dataInfo[i] +"<i style='font-weight:bold;margin-left:5px;font-size:14px' index='"+ i +"' class='layui-icon layui-icon-close area_cut'></i></div>")
        }
    }else{
        $(".area_content").html("请选择区域");
    }
}
createTag();
function setOpt(){
    $(document).on('click',".province_select",function(){
        let item_name = dataList[$(this).parent().attr('index')].name;
        let item_index = $(this).parent().attr('index');
        let index = '';
        for(let i=0;i<indexInfo.length;i++){
            if(indexInfo[i].provinceIndex==item_index){
                if(indexInfo[i].cityIndex){
                    return;
                }
                index = i;
            }
        }
        if(index.toString()==''){
            dataInfo.push(item_name);
            indexInfo.push(
                {
                    'provinceIndex': item_index.toString()
                }
            );
            $(this).addClass('alert_active');
        }else{
            dataInfo.splice(index,1);
            indexInfo.splice(index,1);
            $(this).removeClass('alert_active');
        }
        createTag();
    })
    $(document).on('click',".city_select",function(){
        let item_name = dataList[provinceIndex].name + '-' + dataList[provinceIndex].cityList[$(this).parent().attr('index')].name;
        let item_index = $(this).parent().attr('index');
        let index = '';
        let indexO = 'none';
        for(let i=0;i<indexInfo.length;i++){
            if(indexInfo[i].provinceIndex==provinceIndex){
                if(indexInfo[i].cityIndex){
                    if(indexInfo[i].cityIndex == item_index){
                        index = i;
                        indexO = 'have';
                    }
                }else{
                    index = i;
                    indexO = 'cover';
                }
            }
        }
        if(indexO=='none'){
            dataInfo.push(item_name);
            indexInfo.push(
                {
                    'provinceIndex': provinceIndex.toString(),
                    'cityIndex': item_index.toString()
                }
            );
            $(".province_select").eq(provinceIndex).addClass('alert_active');
            $(this).addClass('alert_active');
        }else if(indexO=='have'){
            if(indexInfo[index].areaIndex){
                return;
            }
            dataInfo.splice(index,1);
            indexInfo.splice(index,1);
            let currentAry = [];
            for(let i=0;i<indexInfo.length;i++){
                if(indexInfo[i].provinceIndex==provinceIndex){
                    currentAry.push(i);
                }
            }
            if(!currentAry.length){
                $(".province_select").eq(provinceIndex).removeClass('alert_active');
            }
            $(this).removeClass('alert_active');
        }else if(indexO=='cover'){
            dataInfo[index] = item_name;
            indexInfo[index] = {
                'provinceIndex': provinceIndex.toString(),
                'cityIndex': item_index.toString()
            };
            $(".province_select").eq(provinceIndex).addClass('alert_active');
            $(this).addClass('alert_active');
        }
        createTag();
    })
    $(document).on('click',".area_select",function(){
        let item_name = dataList[provinceIndex].name + '-' + dataList[provinceIndex].cityList[cityIndex].name + '-' + dataList[provinceIndex].cityList[cityIndex].areaList[$(this).parent().attr('index')];
        let item_index = $(this).parent().attr('index');
        let index = '';
        let indexO = 'none';
        for(let i=0;i<indexInfo.length;i++){
            if(indexInfo[i].provinceIndex==provinceIndex){
                if(indexInfo[i].cityIndex){
                    if(indexInfo[i].cityIndex == cityIndex){
                        if(indexInfo[i].areaIndex){
                            if(indexInfo[i].areaIndex == item_index){
                                index = i;
                                indexO = 'have';
                            }
                        }else{
                            index = i;
                            indexO = 'cover';
                        }
                    }
                }else{
                    index = i;
                    indexO = 'cover';
                }
            }
        }
        if(indexO=='none'){
            dataInfo.push(item_name);
            indexInfo.push(
                {
                    'provinceIndex': provinceIndex.toString(),
                    'cityIndex': cityIndex.toString(),
                    'areaIndex': item_index.toString()
                }
            );
            $(".province_select").eq(provinceIndex).addClass('alert_active');
            $(".city_select").eq(cityIndex).addClass('alert_active');
            $(this).addClass('alert_active');
        }else if(indexO=='have'){
            dataInfo.splice(index,1);
            indexInfo.splice(index,1);
            let pAry = [];
            let cAry = [];
            for(let i=0;i<indexInfo.length;i++){
                if(indexInfo[i].provinceIndex==provinceIndex){
                    pAry.push(i);
                    if(indexInfo[i].cityIndex==cityIndex){
                        cAry.push(i);
                    }
                }
            }
            if(!pAry.length){
                $(".province_select").eq(provinceIndex).removeClass('alert_active');
            }
            if(!cAry.length){
                $(".city_select").eq(cityIndex).removeClass('alert_active');
            }
            $(this).removeClass('alert_active');
        }else if(indexO=='cover'){
            dataInfo[index] = item_name;
            indexInfo[index] = {
                'provinceIndex': provinceIndex.toString(),
                'cityIndex': cityIndex.toString(),
                'areaIndex': item_index.toString()
            };
            $(".province_select").eq(provinceIndex).addClass('alert_active');
            $(".city_select").eq(cityIndex).addClass('alert_active');
            $(this).addClass('alert_active');
        }
        createTag();
    })
    $(document).on('click',".province_more",function(){
        provinceIndex = $(this).parent().attr('index');
        cityIndex = 0;
        createCity();
        createArea();
    })
    $(document).on('click',".city_more",function(){
        cityIndex = $(this).parent().attr('index');
        createArea();
    })
}
setOpt();
$(document).on('click',".area_cut",function(event){
    let index = $(this).attr('index');
    dataInfo.splice(index,1);
    indexInfo.splice(index,1);
    createTag();
    event.stopPropagation();
})
$(document).on('click',".area_module",function(){
    createProvince();
    createCity();
    createArea();
    $(".alert").show();
})
$(document).on('click',".alert_hide",function(){
    createProvince();
    createCity();
    createArea();
    $(".alert").hide();
})