<?php
// +----------------------------------------------------------------------
// | 控制台配置
// +----------------------------------------------------------------------
return [
    // 指令定义
    'commands' => [
        'plantask'=>'app\command\PlanTask',//奖金发放计划任务
        'clean_stuck_generations'=>'app\command\CleanStuckGenerations',//清理卡住的生成记录
        'aivideo:cron'=>'app\command\AivideoCron',//AI旅拍定时任务
        'backfill_portrait_tags'=>'app\command\BackfillPortraitTags',//批量回填人像自动标签
        'backfill_result_urls'=>'app\command\BackfillResultUrls',//回填合成结果图片URL为WebP持久化地址
    ],
];
