# eivie-cn 数据库文档

## 数据库信息
- **数据库名称**: eivie-cn
- **生成日期**: 2024-06-07 11:05:34
- **PHP 版本**: 7.4.33

## 表结构目录

1. [ddwx_hd_weixinweixin_admin](#ddwx_hd_weixinweixin_admin)
2. [ddwx_hd_weixinweixin_aliyunoss](#ddwx_hd_weixinweixin_aliyunoss)
3. [ddwx_hd_weixinweixin_attachments](#ddwx_hd_weixinweixin_attachments)
4. [ddwx_hd_weixinweixin_background](#ddwx_hd_weixinweixin_background)
5. [ddwx_hd_weixinweixin_bimu_config](#ddwx_hd_weixinweixin_bimu_config)
6. [ddwx_hd_weixinweixin_choujiang_config](#ddwx_hd_weixinweixin_choujiang_config)
7. [ddwx_hd_weixinweixin_choujiang_themes](#ddwx_hd_weixinweixin_choujiang_themes)
8. [ddwx_hd_weixinweixin_choujiang_users](#ddwx_hd_weixinweixin_choujiang_users)
9. [ddwx_hd_weixinweixin_cookie](#ddwx_hd_weixinweixin_cookie)
10. [ddwx_hd_weixinweixin_danmu_config](#ddwx_hd_weixinweixin_danmu_config)
11. [ddwx_hd_weixinweixin_danye](#ddwx_hd_weixinweixin_danye)
12. [ddwx_hd_weixinweixin_flag](#ddwx_hd_weixinweixin_flag)
13. [ddwx_hd_weixinweixin_flag_config](#ddwx_hd_weixinweixin_flag_config)
14. [ddwx_hd_weixinweixin_flag_extention_column_type](#ddwx_hd_weixinweixin_flag_extention_column_type)
15. [ddwx_hd_weixinweixin_flag_extention_data](#ddwx_hd_weixinweixin_flag_extention_data)
16. [ddwx_hd_weixinweixin_flag_reserved_infomation](#ddwx_hd_weixinweixin_flag_reserved_infomation)
17. [ddwx_hd_weixinweixin_game_config](#ddwx_hd_weixinweixin_game_config)
18. [ddwx_hd_weixinweixin_game_records](#ddwx_hd_weixinweixin_game_records)
19. [ddwx_hd_weixinweixin_game_themes](#ddwx_hd_weixinweixin_game_themes)
20. [ddwx_hd_weixinweixin_hdxc](#ddwx_hd_weixinweixin_hdxc)
21. [ddwx_hd_weixinweixin_importlottery](#ddwx_hd_weixinweixin_importlottery)
22. [ddwx_hd_weixinweixin_importlotterycolumns](#ddwx_hd_weixinweixin_importlotterycolumns)
23. [ddwx_hd_weixinweixin_kaimu_config](#ddwx_hd_weixinweixin_kaimu_config)
24. [ddwx_hd_weixinweixin_lottery_config](#ddwx_hd_weixinweixin_lottery_config)
25. [ddwx_hd_weixinweixin_lottery_themes](#ddwx_hd_weixinweixin_lottery_themes)
26. [ddwx_hd_weixinweixin_menu](#ddwx_hd_weixinweixin_menu)
27. [ddwx_hd_weixinweixin_music](#ddwx_hd_weixinweixin_music)
28. [ddwx_hd_weixinweixin_pashu_config](#ddwx_hd_weixinweixin_pashu_config)
29. [ddwx_hd_weixinweixin_pashu_record](#ddwx_hd_weixinweixin_pashu_record)
30. [ddwx_hd_weixinweixin_plugs](#ddwx_hd_weixinweixin_plugs)
31. [ddwx_hd_weixinweixin_prizes](#ddwx_hd_weixinweixin_prizes)
32. [ddwx_hd_weixinweixin_redpacket_config](#ddwx_hd_weixinweixin_redpacket_config)
33. [ddwx_hd_weixinweixin_redpacket_orders](#ddwx_hd_weixinweixin_redpacket_orders)
34. [ddwx_hd_weixinweixin_redpacket_order_return](#ddwx_hd_weixinweixin_redpacket_order_return)
35. [ddwx_hd_weixinweixin_redpacket_round](#ddwx_hd_weixinweixin_redpacket_round)
36. [ddwx_hd_weixinweixin_redpacket_users](#ddwx_hd_weixinweixin_redpacket_users)
37. [ddwx_hd_weixinweixin_sessions](#ddwx_hd_weixinweixin_sessions)
38. [ddwx_hd_weixinweixin_shake_config](#ddwx_hd_weixinweixin_shake_config)
39. [ddwx_hd_weixinweixin_shake_record](#ddwx_hd_weixinweixin_shake_record)
40. [ddwx_hd_weixinweixin_shake_themes](#ddwx_hd_weixinweixin_shake_themes)
41. [ddwx_hd_weixinweixin_shuqian_config](#ddwx_hd_weixinweixin_shuqian_config)
42. [ddwx_hd_weixinweixin_shuqian_record](#ddwx_hd_weixinweixin_shuqian_record)
43. [ddwx_hd_weixinweixin_system_config](#ddwx_hd_weixinweixin_system_config)

## 表结构详情

### ddwx_hd_weixinweixin_admin
**描述**: 管理员表

| 字段名 | 数据类型 | 约束 | 描述 |
| :--- | :--- | :--- | :--- |
| `user` | text | NOT NULL | 用户名 |
| `pwd` | text | NOT NULL | 密码 |

**数据**:
| user | pwd |
| :--- | :--- |
| admin | 71jc.cnW98E3 |

### ddwx_hd_weixinweixin_aliyunoss
**描述**: 阿里云oss配置表

| 字段名 | 数据类型 | 约束 | 描述 |
| :--- | :--- | :--- | :--- |
| `id` | int(11) | NOT NULL | 主键 |
| `OSS_ACCESS_ID` | varchar(255) | DEFAULT NULL | ACCESS_ID |
| `OSS_ACCESS_KEY` | varchar(255) | DEFAULT NULL | ACCESS_KEY |
| `ALI_LOG` | tinyint(1) | DEFAULT '1' | 1不记录日志2记录日志 |
| `ALI_LOG_PATH` | varchar(255) | DEFAULT NULL | 日志存放路径 |
| `ALI_DISPLAY_LOG` | tinyint(1) | DEFAULT '1' | 是否显示日志输出1不显示2显示 |
| `BUCKET_NAME` | varchar(255) | DEFAULT NULL | bucket名称 |

### ddwx_hd_weixinweixin_attachments
**描述**: 附件表

| 字段名 | 数据类型 | 约束 | 描述 |
| :--- | :--- | :--- | :--- |
| `id` | int(11) | NOT NULL | 主键 |
| `filepath` | varchar(255) | DEFAULT NULL | 文件路径 |
| `extension` | varchar(10) | DEFAULT NULL | 扩展名 |
| `type` | tinyint(1) | DEFAULT NULL | 1本地文件2阿里云3新浪云 |
| `filemd5` | varchar(32) | DEFAULT NULL | 文件名和文件大小组合的md5值 |

**数据**:
| id | filepath | extension | type | filemd5 |
| :--- | :--- | :--- | :--- | :--- |
| 1 | /wall/themes/meepo/assets/images/shake/worldcup/football1.gif | gif | 1 | 5aa0060a952e110c494530036fd5c5b7 |
| 2 | /wall/themes/meepo/assets/images/shake/worldcup/football2.gif | gif | 1 | a8c965007337f76590b8dfdf2827a7a6 |
| 3 | /wall/themes/meepo/assets/images/shake/worldcup/football3.gif | gif | 1 | 0fd0c04960211d1a55328cdda9e57551 |
| 4 | /wall/themes/meepo/assets/images/shake/worldcup/football4.gif | gif | 1 | c860c8a5b63b76d6b73b4e2dbe6302d6 |
| 5 | /wall/themes/meepo/assets/images/shake/worldcup/football5.gif | gif | 1 | c377de39abfdb7c0f73640f63a4892bf |
| 6 | /wall/themes/meepo/assets/images/shake/worldcup/football6.gif | gif | 1 | 32752496889ac4ee5b86137d4a8cfbd2 |
| 7 | /wall/themes/meepo/assets/images/shake/worldcup/football7.gif | gif | 1 | c7e222195ede65f1717a2183f69ec09f |
| 8 | /wall/themes/meepo/assets/images/shake/worldcup/football8.gif | gif | 1 | 1c3e027d25b720a4368a39c6957b2ab8 |
| 9 | /wall/themes/meepo/assets/images/shake/worldcup/football9.gif | gif | 1 | e7f176ee2343b1f99873d833fea8fb8a |
| 10 | /wall/themes/meepo/assets/images/shake/worldcup/football10.gif | gif | 1 | 8b08e371aa58e2b65f879ad9fc590b02 |
| 11 | /wall/themes/meepo/assets/images/shake/worldcup/bg.mp4 | mp4 | 1 | a87fbbee00734ea2f134c9d87f936207 |
| 12 | /mobile/template/app/images/shake/shake2.png | png | 1 | 0342d812ad0da3bbcea87abaf93617d9 |
| 13 | /Modules/Choujiang/templates/mobile/guaguaka/assets/images/icon.png | png | 1 | 0e491cae8fd78b87542c36eba4bbf35c |
| 14 | /wall/themes/meepo/assets/images/shake/pig/pig.gif | gif | 1 | 874636879e8a6062e7321987fcd77d38 |
| 15 | /wall/themes/meepo/assets/images/shake/pig/shake0.png | png | 1 | 7e7235957843aa42b10d312ceb05f265 |
| 16 | /data/pic/pic_1717729388.jpg | jpg | 1 | f9176be65c3f97fe25594b4a00989d90 |

### ddwx_hd_weixinweixin_background
**描述**: 背景表

| 字段名 | 数据类型 | 约束 | 描述 |
| :--- | :--- | :--- | :--- |
| `id` | int(11) | NOT NULL | 主键 |
| `attachmentid` | int(11) | DEFAULT NULL | 背景图id |
| `name` | varchar(32) | DEFAULT NULL | 名称 |
| `plugname` | varchar(32) | DEFAULT NULL | 关联的组件名 |
| `bgtype` | tinyint(1) | DEFAULT NULL | 1图片2视频 |

**数据**:
| id | attachmentid | name | plugname | bgtype |
| :--- | :--- | :--- | :--- | :--- |
| 1 | 16 | 签到墙背景 | qdq | 1 |
| 2 | NULL | 微信上墙背景 | wall | 1 |
| 3 | NULL | 对对碰背景 | ddp | 1 |
| 4 | NULL | 投票背景 | vote | 1 |
| 5 | NULL | 幸运手机号背景 | xysjh | 1 |
| 6 | NULL | 幸运号码背景 | xyh | 1 |
| 7 | NULL | 相册背景 | xiangce | 1 |
| 8 | NULL | 开幕墙背景 | kaimu | 1 |
| 9 | NULL | 闭幕墙背景 | bimu | 1 |
| 10 | NULL | 红包雨背景 | redpacket | 1 |
| 11 | NULL | 摇大奖背景 | ydj | 1 |
| 12 | NULL | 导入抽奖背景图 | importlottery | 1 |
| 13 | NULL | 3D签到背景图 | threedimensionalsign | 1 |

### ddwx_hd_weixinweixin_bimu_config
**描述**: 闭幕墙配置

| 字段名 | 数据类型 | 约束 | 描述 |
| :--- | :--- | :--- | :--- |
| `Id` | int(11) | NOT NULL | 主键 |
| `imagepath` | int(11) | DEFAULT NULL | 闭幕墙鸣谢图id |
| `fullscreen` | tinyint(1) | DEFAULT '1' | 1表示居中2表示全屏 |

**数据**:
| Id | imagepath | fullscreen |
| :--- | :--- | :--- |
| 1 | 0 | 1 |

### ddwx_hd_weixinweixin_choujiang_config
**描述**: 抽奖配置表

| 字段名 | 数据类型 | 约束 | 描述 |
| :--- | :--- | :--- | :--- |
| `id` | int(11) | NOT NULL | 主键 |
| `title` | varchar(255) | DEFAULT NULL | 标题 |
| `themeid` | int(11) | DEFAULT NULL | 主题id |
| `showleftnum` | tinyint(1) | NOT NULL DEFAULT '2' | 1表示显示剩余数量2表示不显示剩余数量 |
| `defaultnum` | tinyint(3) | DEFAULT NULL | 默认的抽取次数 |
| `description` | text | COMMENT | 游戏说明 |
| `created_at` | int(11) | DEFAULT NULL | 创建时间 |
| `started_at` | int(11) | DEFAULT NULL | 开始时间 |
| `ended_at` | int(11) | DEFAULT NULL | 结束时间 |

### ddwx_hd_weixinweixin_choujiang_themes
**描述**: 抽奖主题

| 字段名 | 数据类型 | 约束 | 描述 |
| :--- | :--- | :--- | :--- |
| `id` | int(11) | NOT NULL | 主键 |
| `themepath` | varchar(255) | DEFAULT NULL | 主题路径 |
| `themename` | varchar(255) | DEFAULT NULL | 主题名称 |

**数据**:
| id | themepath | themename |
| :--- | :--- | :--- |
| 1 | guaguaka | 刮刮卡 |

### ddwx_hd_weixinweixin_choujiang_users
**描述**: 设置每个人可以抽几次奖，如果没有记录则使用抽奖配置中的默认次数

| 字段名 | 数据类型 | 约束 | 描述 |
| :--- | :--- | :--- | :--- |
| `id` | int(11) | NOT NULL | 主键 |
| `choujiangid` | int(11) | DEFAULT NULL | 抽奖配置id |
| `userid` | int(11) | DEFAULT NULL | 用户id |
| `cjtimes` | tinyint(3) | DEFAULT NULL | 抽取的次数 |
| `lefttimes` | tinyint(3) | DEFAULT NULL | 剩余次数 |

### ddwx_hd_weixinweixin_cookie
**描述**: Cookie表

| 字段名 | 数据类型 | 约束 | 描述 |
| :--- | :--- | :--- | :--- |
| `cookie` | text | NOT NULL | Cookie |
| `cookies` | text | NOT NULL | Cookies |
| `token` | int(11) | NOT NULL | Token |
| `id` | int(11) | NOT NULL | 主键 |

### ddwx_hd_weixinweixin_danmu_config
**描述**: 弹幕配置

| 字段名 | 数据类型 | 约束 | 描述 |
| :--- | :--- | :--- | :--- |
| `id` | int(11) | NOT NULL | id |
| `danmuswitch` | tinyint(1) | DEFAULT '1' | 1表示关2表示开 |
| `textcolor` | varchar(7) | DEFAULT NULL | 16进制颜色值 |
| `looptime` | int(3) | DEFAULT NULL | 消息显示的时间间隔，单位是秒 |
| `isloop` | tinyint(1) | DEFAULT NULL | 1表示不循环2表示循环 |
| `historynum` | int(3) | DEFAULT NULL | 循环时使用的历史记录条数 |
| `positionmode` | tinyint(1) | DEFAULT NULL | 1表示上三分之一2表示中间三分之一3表示下三分之一4表示全屏随机 |
| `showname` | tinyint(1) | DEFAULT NULL | 1不显示昵称2显示昵称 |

**数据**:
| id | danmuswitch | textcolor | looptime | isloop | historynum | positionmode | showname |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| 1 | 1 | #b7e692 | 3 | 2 | 30 | 4 | 2 |

### ddwx_hd_weixinweixin_danye
**描述**: 单页表

| 字段名 | 数据类型 | 约束 | 描述 |
| :--- | :--- | :--- | :--- |
| `id` | int(11) | NOT NULL | 主键 |
| `title` | varchar(20) | DEFAULT NULL | 标题 |
| `img` | varchar(255) | DEFAULT NULL | 图片 |
| `createtime` | datetime | DEFAULT NULL | 创建时间 |
| `sort` | int(11) | DEFAULT NULL | 排序 |

### ddwx_hd_weixinweixin_flag
**描述**: 用户微信信息表

| 字段名 | 数据类型 | 约束 | 描述 |
| :--- | :--- | :--- | :--- |
| `id` | int(11) | NOT NULL | 主键 |
| `openid` | varchar(255) | NOT NULL | openid |
| `flag` | int(11) | DEFAULT NULL | 1表示未签到2表示签到成功 |
| `nickname` | varchar(255) | DEFAULT NULL | 微信昵称 |
| `avatar` | text | COMMENT | 微信头像 |
| `sex` | varchar(255) | DEFAULT NULL | 性别 |
| `status` | tinyint(1) | NOT NULL DEFAULT '1' | 1正常2禁用（禁用状态不能使用任何功能） |
| `datetime` | int(10) | DEFAULT NULL | 时间戳 |
| `fromtype` | varchar(25) | DEFAULT NULL | 签到来源ddwx_hd_weixinweixin |
| `rentopenid` | varchar(28) | DEFAULT NULL | 借用来openid |
| `signname` | varchar(32) | NOT NULL DEFAULT '' | 签到记录的姓名 |
| `phone` | varchar(11) | DEFAULT NULL | 电话 |
| `signorder` | int(11) | DEFAULT NULL | 签到顺序 |

### ddwx_hd_weixinweixin_flag_config
**描述**: 签到用户配置

| 字段名 | 数据类型 | 约束 | 描述 |
| :--- | :--- | :--- | :--- |
| `id` | int(11) | NOT NULL | 主键 |
| `reserved_infomation_match` | tinyint(1) | DEFAULT '1' | 1表示不完全匹配2表示完全匹配 |
| `reserved_infomation_verify` | tinyint(1) | DEFAULT '1' | 1表示通过2表示不通过审核 |
| `reserved_infomation_csv_attachmentid` | int(11) | DEFAULT '0' | 上传的csv位置 |

**数据**:
| id | reserved_infomation_match | reserved_infomation_verify | reserved_infomation_csv_attachmentid |
| :--- | :--- | :--- | :--- |
| 1 | 1 | 1 | 0 |

### ddwx_hd_weixinweixin_flag_extention_column_type
**描述**: 签到扩展字段类型

| 字段名 | 数据类型 | 约束 | 描述 |
| :--- | :--- | :--- | :--- |
| `id` | int(11) | NOT NULL | 主键 |
| `ordernum` | int(11) | DEFAULT NULL | 排序号 |
| `coltype` | varchar(20) | DEFAULT NULL | 字段类型 |
| `title` | varchar(50) | DEFAULT NULL | 字段名称 |
| `placeholder` | varchar(255) | DEFAULT NULL | 占位内容 |
| `options` | text | COMMENT | 选项内容 |
| `defaultvalue` | text | COMMENT | 默认内容 |
| `ismust` | tinyint(1) | DEFAULT NULL | 1不是必填2必填 |
| `created_at` | int(11) | DEFAULT NULL | 创建时间 |

### ddwx_hd_weixinweixin_flag_extention_data
**描述**: 签到扩展数据

| 字段名 | 数据类型 | 约束 | 描述 |
| :--- | :--- | :--- | :--- |
| `id` | int(11) | NOT NULL | 主键 |
| `userid` | int(11) | DEFAULT NULL | 用户id |
| `datastr` | text | COMMENT | 内容 |
| `created_at` | int(11) | DEFAULT NULL | 创建时间 |

### ddwx_hd_weixinweixin_flag_reserved_infomation
**描述**: 签到用户预留信息表，在用户签到时如果匹配到其中的数据，就会把这个数据显示到用户的签到界面上

| 字段名 | 数据类型 | 约束 | 描述 |
| :--- | :--- | :--- | :--- |
| `id` | int(11) | NOT NULL | 主键 |
| `realname` | varchar(30) | DEFAULT NULL | 姓名 |
| `phone` | varchar(20) | DEFAULT NULL | 手机号 |
| `info` | varchar(255) | DEFAULT NULL | 预留信息 |

### ddwx_hd_weixinweixin_game_config
**描述**: 摇一摇游戏配置表

| 字段名 | 数据类型 | 约束 | 描述 |
| :--- | :--- | :--- | :--- |
| `id` | int(11) | NOT NULL | 主键 |
| `toprank` | int(5) | DEFAULT '3' | 前几名获奖 |
| `winagain` | tinyint(1) | DEFAULT '1' | 1表示不能重复2表示可以重复获奖，默认是1 |
| `status` | tinyint(1) | DEFAULT '1' | 1表示未开始，2进行中，3表示结束 |
| `showtype` | varchar(32) | DEFAULT NULL | 默认是nickname |
| `themeid` | int(11) | DEFAULT NULL | 主题id |
| `themeconfig` | text | COMMENT | 主题配置 |

**数据**:
| id | toprank | winagain | status | showtype | themeid | themeconfig |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| 1 | 10 | 1 | 1 | nickname | 6 |  |
| 2 | 10 | 1 | 1 | nickname | 1 |  |
| 3 | 10 | 1 | 1 | nickname | 2 |  |
| 4 | 10 | 1 | 1 | nickname | 3 |  |
| 5 | 10 | 1 | 1 | nickname | 4 |  |
| 6 | 10 | 1 | 1 | nickname | 5 |  |
| 7 | 10 | 1 | 1 | nickname | 7 |  |
| 8 | 10 | 1 | 1 | nickname | 8 |  |
| 9 | 10 | 1 | 1 | nickname | 9 |  |
| 10 | 10 | 1 | 1 | nickname | 10 |  |
| 11 | 10 | 1 | 1 | nickname | 11 |  |

### ddwx_hd_weixinweixin_game_records
**描述**: 游戏记录

| 字段名 | 数据类型 | 约束 | 描述 |
| :--- | :--- | :--- | :--- |
| `id` | int(11) | NOT NULL | 主键 |
| `userid` | int(11) | DEFAULT NULL | 用户id |
| `points` | int(11) | DEFAULT NULL | 分数 |
| `created_at` | int(11) | DEFAULT NULL | 创建时间 |
| `updated_at` | int(11) | DEFAULT NULL | 更新时间 |
| `gameid` | int(11) | DEFAULT NULL | 游戏id |

### ddwx_hd_weixinweixin_game_themes
**描述**: 游戏主题

| 字段名 | 数据类型 | 约束 | 描述 |
| :--- | :--- | :--- | :--- |
| `id` | int(11) | NOT NULL | 主键 |
| `themename` | varchar(32) | DEFAULT NULL | 主题名称 |
| `themepath` | varchar(255) | DEFAULT NULL | 主题路径 |

**数据**:
| id | themename | themepath |
| :--- | :--- | :--- |
| 1 | 默认汽车主题 | Racing |
| 2 | 猴子爬树 | Monkey |
| 3 | 数钱游戏 | Money |
| 4 | 金猪送福 | Pig |
| 5 | 赛跑 | Runner |
| 6 | 赛龙舟 | DragonBoat |
| 7 | 赛车 | Car |
| 8 | 赛马 | Horse |
| 9 | 游艇 | Yacht |
| 10 | 丘比特之箭 | Qiubite |
| 11 | 欢乐六一 | Happy61 |

### ddwx_hd_weixinweixin_hdxc
**描述**: 活动行程表

| 字段名 | 数据类型 | 约束 | 描述 |
| :--- | :--- | :--- | :--- |
| `id` | int(11) | NOT NULL | 主键 |
| `img` | varchar(255) | DEFAULT NULL | 图片 |

### ddwx_hd_weixinweixin_importlottery
**描述**: 导入抽奖表

| 字段名 | 数据类型 | 约束 | 描述 |
| :--- | :--- | :--- | :--- |
| `id` | int(11) | NOT NULL | 主键 |
| `col1` | varchar(255) | DEFAULT NULL | 列1 |
| `col2` | varchar(255) | DEFAULT NULL | 列2 |
| `col3` | varchar(255) | DEFAULT NULL | 列3 |
| `imgid` | int(11) | DEFAULT NULL | 图片id |

### ddwx_hd_weixinweixin_importlotterycolumns
**描述**: 导入抽奖列配置

| 字段名 | 数据类型 | 约束 | 描述 |
| :--- | :--- | :--- | :--- |
| `id` | int(11) | NOT NULL | 主键 |
| `col_name1` | varchar(255) | DEFAULT NULL | 列名1 |
| `col_name2` | varchar(255) | DEFAULT NULL | 列名2 |
| `col_name3` | varchar(255) | DEFAULT NULL | 列名3 |

**数据**:
| id | col_name1 | col_name2 | col_name3 |
| :--- | :--- | :--- | :--- |
| 1 | NULL | NULL | NULL |

### ddwx_hd_weixinweixin_kaimu_config
**描述**: 开幕墙配置

| 字段名 | 数据类型 | 约束 | 描述 |
| :--- | :--- | :--- | :--- |
| `Id` | int(11) | NOT NULL | 主键 |
| `imagepath` | int(11) | DEFAULT NULL | 开幕墙图片地址id |
| `fullscreen` | tinyint(1) | DEFAULT '1' | 1表示居中2表示全屏 |

**数据**:
| Id | imagepath | fullscreen |
| :--- | :--- | :--- |
| 1 | 0 | 1 |

### ddwx_hd_weixinweixin_lottery_config
**描述**: 抽奖配置表

| 字段名 | 数据类型 | 约束 | 描述 |
| :--- | :--- | :--- | :--- |
| `id` | int(11) | NOT NULL | 主键 |
| `title` | varchar(255) | DEFAULT NULL | 主题名称 |
| `themeid` | int(11) | DEFAULT NULL | 主题id |
| `created_at` | int(11) | DEFAULT NULL | 创建时间 |
| `winagain` | tinyint(1) | DEFAULT NULL | 之前得过奖的还能再次参与 1表示不可以，2表示可以 |
| `showtype` | varchar(255) | DEFAULT '' | 默认是nickname |
| `themeconfig` | text | COMMENT | 主题的配置 |

**数据**:
| id | title | themeid | created_at | winagain | showtype | themeconfig |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| 1 | 第1轮活动 | 1 | 1 | 1 | nickname |  |
| 2 | 第2轮活动 | 2 | 1 | 1 | nickname |  |
| 3 | 第3轮活动 | 3 | 1 | 1 | nickname |  |

### ddwx_hd_weixinweixin_lottery_themes
**描述**: 抽奖主题

| 字段名 | 数据类型 | 约束 | 描述 |
| :--- | :--- | :--- | :--- |
| `id` | int(11) | NOT NULL | 主键 |
| `themename` | varchar(255) | DEFAULT NULL | 主题名称 |
| `themepath` | varchar(255) | DEFAULT NULL | 主题文件存放的目录 |
| `created_at` | varchar(255) | DEFAULT NULL | 创建时间 |

**数据**:
| id | themename | themepath | created_at |
| :--- | :--- | :--- | :--- |
| 1 | 3D抽奖 | ThreeDimensional | 1 |
| 2 | 砸金蛋 | Zjd | 2 |
| 3 | 抽奖箱 | Cjx | 3 |

### ddwx_hd_weixinweixin_menu
**描述**: 菜单表

| 字段名 | 数据类型 | 约束 | 描述 |
| :--- | :--- | :--- | :--- |
| `id` | int(11) | NOT NULL | 主键 |
| `title` | varchar(255) | DEFAULT NULL | 标题 |
| `icon` | int(11) | DEFAULT NULL | 按钮图标 |
| `link` | text | COMMENT | 链接 |
| `ordernum` | int(11) | DEFAULT NULL | 排序 |
| `type` | tinyint(1) | DEFAULT '1' | 1手机端2pc端 |

### ddwx_hd_weixinweixin_music
**描述**: 音乐表

| 字段名 | 数据类型 | 约束 | 描述 |
| :--- | :--- | :--- | :--- |
| `id` | int(11) | NOT NULL | 主键 |
| `bgmusic` | int(11) | DEFAULT NULL | 背景音乐id |
| `bgmusicstatus` | tinyint(1) | DEFAULT NULL | 1开2关 |
| `name` | varchar(32) | DEFAULT NULL | 名称 |
| `plugname` | varchar(32) | DEFAULT NULL | 关联的组件名 |

**数据**:
| id | bgmusic | bgmusicstatus | name | plugname |
| :--- | :--- | :--- | :--- | :--- |
| 1 | NULL | 2 | 签到墙背景乐 | qdq |
| 2 | NULL | 2 | 对对碰背景乐 | ddp |
| 3 | NULL | 2 | 投票背景乐 | vote |
| 4 | NULL | 2 | 幸运手机号背景乐 | xysjh |
| 5 | NULL | 2 | 幸运号码背景乐 | xyh |
| 6 | NULL | 2 | 3D签到背景乐 | threedimensionalsign |
| 7 | NULL | 2 | 微信上墙背景乐 | wall |
| 8 | NULL | 2 | 相册背景乐 | xiangce |
| 9 | NULL | 2 | 开幕墙背景乐 | kaimu |
| 10 | NULL | 2 | 闭幕墙背景乐 | bimu |
| 11 | NULL | 2 | 红包雨背景乐 | redpacket |
| 12 | NULL | 2 | 摇大奖树背景乐 | ydj |
| 13 | NULL | 2 | 导入抽奖 | importlottery |

### ddwx_hd_weixinweixin_pashu_config
**描述**: 猴子爬树配置表

| 字段名 | 数据类型 | 约束 | 描述 |
| :--- | :--- | :--- | :--- |
| `id` | int(11) | NOT NULL | 主键 |
| `times` | int(11) | NOT NULL DEFAULT '0' | 次数 |
| `toprank` | int(5) | DEFAULT '3' | 前几名获奖 |
| `winningagain` | tinyint(1) | DEFAULT '1' | 1表示不能重复2表示可以重复获奖，默认是1 |
| `status` | tinyint(1) | DEFAULT '1' | 1表示未开始，2进行中，3表示结束 |
| `maxplayers` | int(11) UNSIGNED | DEFAULT '200' | 最大参与人数，默认200 |
| `showstyle` | tinyint(1) | DEFAULT '1' | 1昵称2姓名3手机号 |
| `currentshow` | tinyint(1) | DEFAULT '1' | 1不是当前活动2当前活动 |

**数据**:
| id | times | toprank | winningagain | status | maxplayers | showstyle | currentshow |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| 1 | 100 | 3 | 1 | 1 | 200 | 1 | 1 |
| 2 | 100 | 3 | 1 | 1 | 200 | 1 | 2 |
| 3 | 100 | 3 | 1 | 1 | 200 | 1 | 2 |
| 4 | 100 | 3 | 1 | 1 | 200 | 1 | 2 |
| 5 | 100 | 3 | 1 | 1 | 200 | 1 | 2 |
| 6 | 100 | 3 | 1 | 1 | 200 | 1 | 2 |
| 7 | 100 | 3 | 1 | 1 | 200 | 1 | 2 |
| 8 | 100 | 3 | 1 | 1 | 200 | 1 | 2 |
| 9 | 100 | 3 | 1 | 1 | 200 | 1 | 2 |
| 10 | 100 | 3 | 1 | 1 | 200 | 1 | 2 |

### ddwx_hd_weixinweixin_pashu_record
**描述**: 猴子爬树游戏记录

| 字段名 | 数据类型 | 约束 | 描述 |
| :--- | :--- | :--- | :--- |
| `id` | int(11) | NOT NULL | 主键 |
| `point` | int(11) | DEFAULT NULL | 数量 |
| `openid` | varchar(255) | DEFAULT NULL | openid |
| `configid` | int(11) | DEFAULT NULL | 配置id |
| `iswinner` | tinyint(1) | DEFAULT NULL | 1不是2是中奖用户 |

### ddwx_hd_weixinweixin_plugs
**描述**: 插件表

| 字段名 | 数据类型 | 约束 | 描述 |
| :--- | :--- | :--- | :--- |
| `id` | int(11) | NOT NULL | 主键 |
| `name` | varchar(255) | NOT NULL | 模块名 |
| `title` | varchar(255) | DEFAULT NULL | 模块中文名 |
| `switch` | tinyint(1) UNSIGNED ZEROFILL | NOT NULL DEFAULT '0' | 1表示开2表示关 |
| `url` | varchar(255) | DEFAULT NULL | url |
| `img` | varchar(255) | DEFAULT NULL | 图标 |
| `ordernum` | tinyint(3) UNSIGNED ZEROFILL | DEFAULT '000' | 排序号 |
| `choujiang` | tinyint(1) UNSIGNED ZEROFILL | DEFAULT '0' | 0表示不是抽奖项目1表示不能重复中奖，2表示可以重复中奖 |
| `hotkey` | varchar(10) | DEFAULT NULL | 快捷键 |
| `ismodule` | tinyint(1) | DEFAULT '2' | 1表示是组件2表示不是，默认为2 |

**数据**:
| id | name | title | switch | url | img | ordernum | choujiang | hotkey | ismodule |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| 1 | qdq | 签到墙 | 1 | /wall/index.php | themes/meepo/assets/images/icon/ico005.png | 001 | 0 | ctrl+1 | 2 |
| 2 | ddp | 对对碰 | 1 | /wall/ddp.php | themes/meepo/assets/images/icon/ico006.png | 010 | 0 | ctrl+8 | 2 |
| 3 | vote | 投票 | 1 | /wall/vote.php | themes/meepo/assets/images/icon/ico004.png | 005 | 0 | ctrl+5 | 2 |
| 4 | xysjh | 幸运手机号 | 1 | /wall/xysjh.php | themes/meepo/assets/images/icon/ico019.png | 008 | 0 | ctrl+7 | 2 |
| 5 | xyh | 幸运号码 | 1 | /wall/xyh.php | themes/meepo/assets/images/icon/ico016.png | 007 | 0 | ctrl+6 | 2 |
| 6 | threedimensionalsign | 3D签到 | 1 | /wall/3dsign.php | themes/meepo/assets/images/icon/ico013.png | 002 | 0 | ctrl+2 | 2 |
| 7 | wall | 微信上墙 | 1 | /wall/wall.php | themes/meepo/assets/images/icon/ico009.png | 003 | 0 | ctrl+3 | 2 |
| 8 | xiangce | 相册 | 1 | /wall/xiangce.php | themes/meepo/assets/images/icon/ico003.png | 012 | 0 | ctrl+9 | 2 |
| 9 | kaimu | 开幕墙 | 1 | /wall/kaimu.php | themes/meepo/assets/images/icon/ico007.png | 014 | 0 | ctrl+k | 2 |
| 10 | bimu | 闭幕墙 | 1 | /wall/bimu.php | themes/meepo/assets/images/icon/ico014.png | 015 | 0 | ctrl+b | 2 |
| 11 | redpacket | 红包雨 | 2 | /wall/redpacket.php | themes/meepo/assets/images/icon/redpack3.png | 016 | 0 | ctrl+r | 2 |
| 12 | ydj | 摇大奖 | 1 | NULL | NULL | 020 | 1 | ctrl+y | 1 |
| 13 | choujiang | 手机端抽奖 | 1 | NULL | NULL | 021 | 1 |  | 1 |
| 14 | importlottery | 导入抽奖 | 1 | NULL | NULL | 022 | 1 |  | 1 |
| 15 | lottery | 抽奖 | 1 | NULL | NULL | 023 | 1 | NULL | 1 |
| 16 | game | 游戏 | 1 | NULL | NULL | 024 | 1 | NULL | 1 |
| 17 | danye | 单页 | 1 | /wall/danye.php | NULL | 025 | 0 | NULL | 2 |
| 18 | hyxc | 活动行程 | 1 | /wall/dyxc.php | NULL | 026 | 0 | NULL | 2 |

### ddwx_hd_weixinweixin_prizes
**描述**: 奖品表

| 字段名 | 数据类型 | 约束 | 描述 |
| :--- | :--- | :--- | :--- |
| `id` | int(11) | NOT NULL | id |
| `prizename` | varchar(255) | DEFAULT NULL | 奖品名称 |
| `type` | tinyint(1) | DEFAULT NULL | 奖品类型1普通奖品2微信卡券3微信红包4微信零钱5虚拟卡密 |
| `num` | int(11) UNSIGNED | NOT NULL DEFAULT '1' | 奖品数量 |
| `freezenum` | int(11) UNSIGNED | NOT NULL DEFAULT '0' | 冻结的数量 |
| `leftnum` | int(11) UNSIGNED | NOT NULL DEFAULT '0' | 剩余数量（不包含冻结的数量） |
| `prizedata` | text | COMMENT | 序列化的奖品数据 |
| `plugname` | varchar(64) | DEFAULT NULL | 组件名称 |
| `activityid` | int(11) UNSIGNED | DEFAULT '0' | 活动编号，没有就是0，有就是id |
| `isdel` | tinyint(1) | NOT NULL DEFAULT '1' | 1表示正常2表示已删除 |
| `rate` | int(11) UNSIGNED | NOT NULL DEFAULT '1000000' | 中奖概率，百万分之一 |

**数据**:
| id | prizename | type | num | freezenum | leftnum | prizedata | plugname | activityid | isdel | rate |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| 1 | 奖品1 | 1 | 10 | 0 | 10 | a:1:{s:7:"imageid";i:0;} | ydj | 1 | 1 | 1000000 |
| 2 | 奖品2 | 1 | 10 | 0 | 10 | a:1:{s:7:"imageid";i:0;} | ydj | 1 | 1 | 1000000 |
| 3 | 奖品3 | 1 | 10 | 0 | 10 | a:1:{s:7:"imageid";i:0;} | ydj | 1 | 1 | 1000000 |
| 4 | 奖品1 | 1 | 10 | 0 | 10 | a:1:{s:7:"imageid";i:0;} | ydj | 2 | 1 | 1000000 |
| 5 | 奖品2 | 1 | 10 | 0 | 10 | a:1:{s:7:"imageid";i:0;} | ydj | 2 | 1 | 1000000 |
| 6 | 奖品3 | 1 | 10 | 0 | 10 | a:1:{s:7:"imageid";i:0;} | ydj | 2 | 1 | 1000000 |
| 7 | 奖品1 | 1 | 10 | 0 | 10 | a:1:{s:7:"imageid";i:0;} | ydj | 3 | 1 | 1000000 |
| 8 | 奖品2 | 1 | 10 | 0 | 10 | a:1:{s:7:"imageid";i:0;} | ydj | 3 | 1 | 1000000 |
| 9 | 奖品3 | 1 | 10 | 0 | 10 | a:1:{s:7:"imageid";i:0;} | ydj | 3 | 1 | 1000000 |
| 10 | 奖品1 | 1 | 100 | 0 | 100 | a:1:{s:7:"imageid";i:0;} | importlottery | 1 | 1 | 1000000 |
| 11 | 奖品2 | 1 | 100 | 0 | 100 | a:1:{s:7:"imageid";i:0;} | importlottery | 1 | 1 | 1000000 |
| 12 | 奖品3 | 1 | 100 | 0 | 100 | a:1:{s:7:"imageid";i:0;} | importlottery | 1 | 1 | 1000000 |
| 13 | 一等奖 | 1 | 100 | 0 | 100 | a:1:{s:7:"imageid";i:0;} | lottery | 1 | 1 | 1000000 |
| 14 | 二等奖 | 1 | 100 | 0 | 100 | a:1:{s:7:"imageid";i:0;} | lottery | 1 | 1 | 1000000 |
| 15 | 三等奖 | 1 | 100 | 0 | 100 | a:1:{s:7:"imageid";i:0;} | lottery | 1 | 1 | 1000000 |
| 16 | 一等奖 | 1 | 100 | 0 | 100 | a:1:{s:7:"imageid";i:0;} | lottery | 2 | 1 | 1000000 |
| 17 | 二等奖 | 1 | 100 | 0 | 100 | a:1:{s:7:"imageid";i:0;} | lottery | 2 | 1 | 1000000 |
| 18 | 三等奖 | 1 | 100 | 0 | 100 | a:1:{s:7:"imageid";i:0;} | lottery | 2 | 1 | 1000000 |
| 19 | 一等奖 | 1 | 100 | 0 | 100 | a:1:{s:7:"imageid";i:0;} | lottery | 3 | 1 | 1000000 |
| 20 | 二等奖 | 1 | 100 | 0 | 100 | a:1:{s:7:"imageid";i:0;} | lottery | 3 | 1 | 1000000 |
| 21 | 三等奖 | 1 | 100 | 0 | 100 | a:1:{s:7:"imageid";i:0;} | lottery | 3 | 1 | 1000000 |

### ddwx_hd_weixinweixin_redpacket_config
**描述**: 红包配置信息

| 字段名 | 数据类型 | 约束 | 描述 |
| :--- | :--- | :--- | :--- |
| `id` | int(11) | NOT NULL | 主键 |
| `rule` | text | COMMENT | 抢红包规则 |
| `tips` | text | COMMENT | 提示语 |
| `sendname` | varchar(32) | DEFAULT NULL | 红包发送者名称 |
| `wishing` | varchar(128) | DEFAULT NULL | 祝福语 |

**数据**:
| id | rule | tips | sendname | wishing |
| :--- | :--- | :--- | :--- | :--- |
| 1 | 1.用户打开微信扫描大屏幕上的二维码进入等待抢红包页面<br>2.主持人说开始后，大屏幕和手机页面同时落下红包雨<br>3.用户随机选择落下的红包，并拆开红包。<br>4.如果倒计时还在继续，那么无论用户是否抢到了，都可以继续抢 直到倒计时完成。 | 大屏幕倒计时开始，<br>红包将从大屏幕降落到手机，此时<br>手指戳红包即可参与<br>抢红包游戏 |  |  |

### ddwx_hd_weixinweixin_redpacket_orders
**描述**: 红包订单表

| 字段名 | 数据类型 | 约束 | 描述 |
| :--- | :--- | :--- | :--- |
| `id` | int(11) | NOT NULL | id |
| `mch_billno` | varchar(28) | DEFAULT NULL | 商户订单号 |
| `mch_id` | varchar(32) | DEFAULT NULL | 商户号 |
| `wxappid` | varchar(32) | DEFAULT NULL | 公众号appid |
| `send_name` | varchar(32) | DEFAULT NULL | 红包发送者名称 |
| `re_openid` | varchar(32) | DEFAULT NULL | 接受红包的openid |
| `total_num` | int(11) | DEFAULT '1' | 数量 |
| `wishing` | varchar(128) | DEFAULT NULL | 祝福语 |
| `client_ip` | varchar(15) | DEFAULT NULL | 调用接口机器的ip |
| `act_name` | varchar(32) | DEFAULT NULL | 活动名称 |
| `remark` | varchar(255) | DEFAULT NULL | 备注信息 |
| `scene_id` | varchar(32) | DEFAULT NULL | 场景id |
| `risk_info` | varchar(128) | DEFAULT NULL | 活动信息 |
| `consume_mch_id` | varchar(32) | DEFAULT NULL | 资金授权商户号 |
| `nonce_str` | varchar(32) | DEFAULT NULL | 随机字符串 |
| `sign` | varchar(32) | DEFAULT NULL | 数据签名 |

### ddwx_hd_weixinweixin_redpacket_order_return
**描述**: 发红包返回信息表

| 字段名 | 数据类型 | 约束 | 描述 |
| :--- | :--- | :--- | :--- |
| `id` | int(11) | NOT NULL | 主键 |
| `return_code` | varchar(16) | DEFAULT NULL | 返回状态吗 |
| `return_msg` | varchar(128) | DEFAULT NULL | 返回信息表 |
| `sign` | varchar(32) | DEFAULT NULL | 签名信息 |
| `result_code` | varchar(16) | DEFAULT NULL | 业务结果 |
| `err_code` | varchar(32) | DEFAULT NULL | 错误代码 |
| `err_code_des` | varchar(128) | DEFAULT NULL | 错误代码描述 |
| `mch_billno` | varchar(28) | DEFAULT NULL | 商户订单号 |
| `mch_id` | varchar(32) | DEFAULT NULL | 商户号 |
| `wxappid` | varchar(32) | DEFAULT NULL | 公众号appid |
| `re_openid` | varchar(32) | DEFAULT NULL | 收红包用户的openid |
| `total_amount` | int(11) | DEFAULT NULL | 付款金额 |
| `send_listid` | varchar(32) | DEFAULT NULL | 微信单号 |

### ddwx_hd_weixinweixin_redpacket_round
**描述**: 红包轮次配置

| 字段名 | 数据类型 | 约束 | 描述 |
| :--- | :--- | :--- | :--- |
| `id` | int(11) | NOT NULL | 主键 |
| `status` | tinyint(1) UNSIGNED | DEFAULT '1' | 1未开始2进行中3结束 |
| `type` | tinyint(1) UNSIGNED | DEFAULT '1' | 1普通红包2随机红包 |
| `amount` | int(8) UNSIGNED | DEFAULT '0' | 红包金额 单位是分 |
| `num` | int(4) UNSIGNED | DEFAULT '1' | 红包个数大于1 |
| `numperperson` | tinyint(3) UNSIGNED | DEFAULT '1' | 每个用户此轮可抢的红包数量，默认为1个 |
| `chance` | int(4) UNSIGNED | DEFAULT '0' | 红包获得概率，单位是千分之1 |
| `lefttime` | int(11) UNSIGNED | DEFAULT '30' | 活动持续时间，单位是秒 |
| `minamount` | int(8) UNSIGNED | DEFAULT '0' | 随机红包最小金额大于100，单位是分 |
| `maxamount` | int(8) UNSIGNED | DEFAULT '0' | 随机红包的最大金额 |
| `started_at` | int(11) | DEFAULT NULL | 轮次开始时间 |

### ddwx_hd_weixinweixin_redpacket_users
**描述**: 红包雨中奖用户数据

| 字段名 | 数据类型 | 约束 | 描述 |
| :--- | :--- | :--- | :--- |
| `id` | int(11) | NOT NULL | 主键 |
| `userid` | int(11) | DEFAULT NULL | 用户id |
| `roundid` | int(11) | DEFAULT NULL | 轮次id |
| `amount` | int(11) | DEFAULT NULL | 红包金额，单位是分 |
| `created_at` | int(11) | DEFAULT NULL | 红包领取时间 |
| `updated_at` | int(11) | DEFAULT NULL | 发放完成时间 |
| `status` | tinyint(1) | DEFAULT NULL | 1表示未发2表示发放中3已发4发放失败 |

### ddwx_hd_weixinweixin_sessions
**描述**: 会话表

| 字段名 | 数据类型 | 约束 | 描述 |
| :--- | :--- | :--- | :--- |
| `session_id` | varchar(40) | NOT NULL DEFAULT '0' | 会话id |
| `ip_address` | varchar(15) | NOT NULL DEFAULT '0' | IP地址 |
| `user_agent` | varchar(200) | NOT NULL | 用户代理 |
| `last_activity` | int(10) UNSIGNED | NOT NULL DEFAULT '0' | 最后活动时间 |
| `user_data` | text | COMMENT | 用户数据 |

**数据**:
| session_id | ip_address | user_agent | last_activity | user_data |
| :--- | :--- | :--- | :--- | :--- |
| 431a2a760010bc9d769de4c9295aa95a | 192.168.253.1 | Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.5735.289 Safari/537.36 | 1717729092 | admin|b:1; |

### ddwx_hd_weixinweixin_shake_config
**描述**: 摇一摇游戏配置表

| 字段名 | 数据类型 | 约束 | 描述 |
| :--- | :--- | :--- | :--- |
| `id` | int(11) | NOT NULL | 主键 |
| `duration` | int(11) | NOT NULL DEFAULT '1' | 持续条件（次/秒） |
| `durationtype` | tinyint(1) | DEFAULT '1' | 1表示按时间，2表示按次数 |
| `toprank` | int(5) | DEFAULT '3' | 前几名获奖 |
| `winningagain` | tinyint(1) | DEFAULT '1' | 1表示不能重复2表示可以重复获奖，默认是1 |
| `status` | tinyint(1) | DEFAULT '1' | 1表示未开始，2进行中，3表示结束 |
| `maxplayers` | int(11) UNSIGNED | DEFAULT '200' | 最大参与人数，默认200 |
| `showstyle` | tinyint(1) | DEFAULT '1' | 1昵称2姓名3手机号 |
| `currentshow` | tinyint(1) | DEFAULT '1' | 1不是当前活动2当前活动 |
| `themeid` | int(11) | DEFAULT NULL | 主题id |

**数据**:
| id | duration | durationtype | toprank | winningagain | status | maxplayers | showstyle | currentshow | themeid |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| 1 | 100 | 2 | 3 | 1 | 1 | 200 | 1 | 2 | 1 |
| 2 | 100 | 2 | 3 | 1 | 1 | 200 | 1 | 1 | 2 |
| 3 | 100 | 2 | 3 | 1 | 1 | 200 | 1 | 1 | 3 |
| 4 | 100 | 2 | 3 | 1 | 1 | 200 | 1 | 1 | 1 |
| 5 | 100 | 2 | 3 | 1 | 1 | 200 | 1 | 1 | 2 |
| 6 | 100 | 2 | 3 | 1 | 1 | 200 | 1 | 1 | 3 |
| 7 | 100 | 2 | 3 | 1 | 1 | 200 | 1 | 1 | 1 |
| 8 | 100 | 2 | 3 | 1 | 1 | 200 | 1 | 1 | 2 |
| 9 | 100 | 2 | 3 | 1 | 1 | 200 | 1 | 1 | 3 |
| 10 | 100 | 2 | 3 | 1 | 1 | 200 | 1 | 1 | 1 |

### ddwx_hd_weixinweixin_shake_record
**描述**: 摇一摇游戏记录

| 字段名 | 数据类型 | 约束 | 描述 |
| :--- | :--- | :--- | :--- |
| `id` | int(11) | NOT NULL | 主键 |
| `point` | int(11) | DEFAULT NULL | 数量 |
| `userid` | int(11) | DEFAULT NULL | 用户id |
| `configid` | int(11) | DEFAULT NULL | 配置id |
| `iswinner` | tinyint(1) | DEFAULT NULL | 1不是2是中奖用户 |

### ddwx_hd_weixinweixin_shake_themes
**描述**: 摇一摇主题

| 字段名 | 数据类型 | 约束 | 描述 |
| :--- | :--- | :--- | :--- |
| `id` | int(11) | NOT NULL | 主键 |
| `themename` | varchar(32) | DEFAULT NULL | 主题名称 |
| `themedata` | text | COMMENT | 主题的数据 |

**数据**:
| id | themename | themedata |
| :--- | :--- | :--- |
| 1 | 默认横向汽车主题 | a:2:{s:12:"ishorizontal";s:1:"1";s:4:"tips";a:8:{i:0;s:12:"再大力！";i:1;s:22:"再大力,再大力！";i:2;s:32:"再大力,再大力,再大力！";i:3;s:15:"摇，大力摇";i:4;s:24:"快点摇啊，别停！";i:5;s:24:"摇啊，摇啊，摇啊";i:6;s:45:"小心手机，别飞出去伤到花花草草";i:7;s:18:"看灰机～～～";}} |
| 2 | 默认纵向气球主题 | a:2:{s:12:"ishorizontal";s:1:"2";s:4:"tips";a:8:{i:0;s:12:"再大力！";i:1;s:22:"再大力,再大力！";i:2;s:32:"再大力,再大力,再大力！";i:3;s:15:"摇，大力摇";i:4;s:24:"快点摇啊，别停！";i:5;s:24:"摇啊，摇啊，摇啊";i:6;s:45:"小心手机，别飞出去伤到花花草草";i:7;s:18:"看灰机～～～";}} |
| 3 | 横向足球主题 | a:18:{s:12:"ishorizontal";i:1;s:8:"avatar_1";i:1;s:8:"avatar_2";i:2;s:8:"avatar_3";i:3;s:8:"avatar_4";i:4;s:8:"avatar_5";i:5;s:8:"avatar_6";i:6;s:8:"avatar_7";i:7;s:8:"avatar_8";i:8;s:8:"avatar_9";i:9;s:9:"avatar_10";i:10;s:9:"startline";i:0;s:7:"endline";i:0;s:8:"trackodd";i:0;s:9:"trackeven";i:0;s:2:"bg";i:11;s:9:"mobileimg";i:12;s:4:"tips";a:8:{i:0;s:12:"再大力！";i:1;s:22:"再大力,再大力！";i:2;s:32:"再大力,再大力,再大力！";i:3;s:15:"摇，大力摇";i:4;s:24:"快点摇啊，别停！";i:5;s:24:"摇啊，摇啊，摇啊";i:6;s:45:"小心手机，别飞出去伤到花花草草";i:7;s:18:"看灰机～～～";}} |
| 4 | 猪年主题 | a:18:{s:8:"avatar_1";s:2:"14";s:8:"avatar_2";s:2:"14";s:8:"avatar_3";s:2:"14";s:8:"avatar_4";s:2:"14";s:8:"avatar_5";s:2:"14";s:8:"avatar_6";s:2:"14";s:8:"avatar_7";s:2:"14";s:8:"avatar_8";s:2:"14";s:8:"avatar_9";s:2:"14";s:9:"avatar_10";s:2:"14";s:9:"startline";i:0;s:7:"endline";i:0;s:8:"trackodd";i:0;s:9:"trackeven";i:0;s:2:"bg";i:0;s:9:"mobileimg";s:2:"15";s:12:"ishorizontal";s:1:"1";s:4:"tips";a:8:{i:0;s:12:"再大力！";i:1;s:22:"再大力,再大力！";i:2;s:32:"再大力,再大力,再大力！";i:3;s:15:"摇，大力摇";i:4;s:24:"快点摇啊，别停！";i:5;s:24:"摇啊，摇啊，摇啊";i:6;s:45:"小心手机，别飞出去伤到花花草草";i:7;s:18:"看灰机～～～";}} |

### ddwx_hd_weixinweixin_shuqian_config
**描述**: 数钱游戏配置表

| 字段名 | 数据类型 | 约束 | 描述 |
| :--- | :--- | :--- | :--- |
| `id` | int(11) | NOT NULL | 主键 |
| `duration` | int(4) | DEFAULT '30' | 游戏持续时间 |
| `toprank` | int(5) | DEFAULT '3' | 前几名获奖 |
| `winningagain` | tinyint(1) | DEFAULT '1' | 1表示不能重复2表示可以重复获奖，默认是1 |
| `status` | tinyint(1) | DEFAULT '1' | 1表示未开始，2进行中，3表示结束 |
| `maxplayers` | int(11) UNSIGNED | DEFAULT '200' | 最大参与人数，默认200 |
| `showstyle` | tinyint(1) | DEFAULT '1' | 1昵称2姓名3手机号 |
| `currentshow` | tinyint(1) | DEFAULT '1' | 1不是当前活动2当前活动 |

**数据**:
| id | duration | toprank | winningagain | status | maxplayers | showstyle | currentshow |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| 1 | 30 | 3 | 1 | 1 | 200 | 1 | 1 |
| 2 | 30 | 3 | 1 | 1 | 200 | 1 | 2 |
| 3 | 30 | 3 | 1 | 1 | 200 | 1 | 2 |
| 4 | 30 | 3 | 1 | 1 | 200 | 1 | 2 |
| 5 | 30 | 3 | 1 | 1 | 200 | 1 | 2 |
| 6 | 30 | 3 | 1 | 1 | 200 | 1 | 2 |
| 7 | 30 | 3 | 1 | 1 | 200 | 1 | 2 |
| 8 | 30 | 3 | 1 | 1 | 200 | 1 | 2 |
| 9 | 30 | 3 | 1 | 1 | 200 | 1 | 2 |
| 10 | 30 | 3 | 1 | 1 | 200 | 1 | 2 |

### ddwx_hd_weixinweixin_shuqian_record
**描述**: 数钱游戏记录

| 字段名 | 数据类型 | 约束 | 描述 |
| :--- | :--- | :--- | :--- |
| `id` | int(11) | NOT NULL | 主键 |
| `point` | int(11) | DEFAULT NULL | 数量 |
| `openid` | varchar(255) | DEFAULT NULL | openid |
| `configid` | int(11) | DEFAULT NULL | 配置id |
| `iswinner` | tinyint(1) | DEFAULT NULL | 1不是2是中奖用户 |

### ddwx_hd_weixinweixin_system_config
**描述**: 系统配置表

| 字段名 | 数据类型 | 约束 | 描述 |
| :--- | :--- | :--- | :--- |
| `id` | int(11) | NOT NULL | id |
| `configkey` | varchar(255) | DEFAULT NULL | 配置名称 |
| `configvalue` | varchar(255) | DEFAULT NULL | 配置值 |
| `configname` | varchar(255) | DEFAULT NULL | 配置中文名称 |
| `configcomment` | text | COMMENT | 配置备注说明 |

**数据**:
| id | configkey | configvalue | configname | configcomment |
| :--- | :--- | :--- | :--- | :--- |
| 1 | SAVEFILEMODE | file | 文件保存模式 | file表示文件保存，aliyunoss表示阿里云oss保存图片 |
| 2 | mobileqiandaobg | 0 | 手机端签到页面背景 | 手机签到页面的背景图，默认0是现在的星空背景 |
| 3 | wallnameshowstyle | 1 | 上墙消息显示 | 1昵称2姓名3手机号 |
| 4 | signnameshowstyle | 1 | 签到显示 | 1昵称2姓名3手机号 |
| 5 | danmushowstyle | 1 | 弹幕显示 | 1昵称2姓名3手机号 |