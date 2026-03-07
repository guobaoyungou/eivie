# 通用信息：可灵 API 接口鉴权及限速说明

# 通用信息

## 调用域名

```Plain Text

https://api-beijing.klingai.com
```

## 接口鉴权

### Step-1：获取 AccessKey + SecretKey

### Step-2：生成API Token

加密方法遵循JWT（Json Web Token, RFC 7519）标准，JWT由Header、Payload、Signature三部分组成。

#### Python 示例

```Python

import time
import jwt

ak = ""  # 填写access key
sk = ""  # 填写secret key

def encode_jwt_token(ak, sk):
    headers = {
        "alg": "HS256",
        "typ": "JWT"
    }
    payload = {
        "iss": ak,
        "exp": int(time.time()) + 1800,  # 有效时间，当前时间+1800s(30min)
        "nbf": int(time.time()) - 5  # 开始生效时间，当前时间-5秒
    }
    token = jwt.encode(payload, sk, headers=headers)
    return token

api_token = encode_jwt_token(ak, sk)
print(api_token)  # 打印生成的API_TOKEN
```

#### Java 示例

```Java

package test;

import com.auth0.jwt.JWT;
import com.auth0.jwt.algorithms.Algorithm;

import java.util.Date;
import java.util.HashMap;
import java.util.Map;

public class JWTDemo {

    static String ak = "";  // 填写access key
    static String sk = "";  // 填写secret key

    public static void main(String[] args) {
        String token = sign(ak, sk);
        System.out.println(token);  // 打印生成的API_TOKEN
    }

    static String sign(String ak, String sk) {
        try {
            Date expiredAt = new Date(System.currentTimeMillis() + 1800 * 1000);  // 有效时间，当前时间+1800s(30min)
            Date notBefore = new Date(System.currentTimeMillis() - 5 * 1000);  // 开始生效时间，当前时间-5秒
            Algorithm algo = Algorithm.HMAC256(sk);
            Map<String, Object> header = new HashMap<String, Object>();
            header.put("alg", "HS256");
            return JWT.create()
                    .withIssuer(ak)
                    .withHeader(header)
                    .withExpiresAt(expiredAt)
                    .withNotBefore(notBefore)
                    .sign(algo);
        } catch (Exception e) {
            e.printStackTrace();
            return null;
        }
    }
}
```

### Step-3：组装Authorization

组装方式：Authorization = "Bearer XXX"，其中XXX为第二步生成的API Token（注意Bearer与XXX之间有空格），填写到Request Header中。

## 错误码

|HTTP状态码|业务码|业务码定义|业务码解释|建议解决方案|
|---|---|---|---|---|
|200|0|请求成功|-|-|
|401|1000|身份验证失败|身份验证失败|检查Authorization是否正确|
|401|1001|身份验证失败|Authorization为空|在Request Header中填写正确的Authorization|
|401|1002|身份验证失败|Authorization值非法|在Request Header中填写正确的Authorization|
|401|1003|身份验证失败|Authorization未到有效时间|检查token的开始生效时间，等待生效或重新签发|
|401|1004|身份验证失败|Authorization已失效|检查token的有效期，重新签发|
|429|1100|账户异常|账户异常|检查账户配置信息|
|429|1101|账户异常|账户欠费 (后付费场景)|进行账户充值，确保余额充足|
|429|1102|账户异常|资源包已用完/已过期（预付费场景）|购买额外的资源包，或开通后付费服务（如有）|
|403|1103|账户异常|请求的资源无权限，如接口/模型|检查账户权限|
|400|1200|请求参数非法|请求参数非法|检查请求参数是否正确|
|400|1201|请求参数非法|参数非法，如key写错或value非法|参考返回体中message字段的具体信息，修改请求参数|
|404|1202|请求参数非法|请求的method无效|查看接口文档，使用正确的request method|
|404|1203|请求参数非法|请求的资源不存在，如模型|参考返回体中message字段的具体信息，修改请求参数|
|400|1300|触发策略|触发平台策略|检查是否触发平台策略|
|400|1301|触发策略|触发平台的内容安全策略|检查输入内容，修改后重新发起请求|
|429|1302|触发策略|API请求过快，超过平台速率限制|降低请求频率、稍后重试，或联系客服增加限额|
|429|1303|触发策略|并发或QPS超出预付费资源包限制|降低请求频率、稍后重试，或联系客服增加限额|
|429|1304|触发策略|触发平台的IP白名单策略|联系客服|
|500|5000|内部错误|服务器内部错误|稍后重试，或联系客服|
|503|5001|内部错误|服务器暂时不可用，通常是在维护|稍后重试，或联系客服|
|504|5002|内部错误|服务器内部超时，通常是发生积压|稍后重试，或联系客服|
---

# 限速说明

## 可灵 API 并发定义

可灵 API 并发指账号在任意时刻可并行处理的生成任务上限，该能力由资源包提供。更高的并发数支持同时提交更多的 API 生成请求（每调用一次创建任务接口生成一个新任务）。

## 注意

- 仅影响任务创建接口，查询接口不占用并发；

- 此限制针对并行任务数，与请求频率（QPS）无关，系统不设QPS限制。

## 核心规则

|维度|规则说明|
|---|---|
|作用粒度|以账号为单位，按资源包类型（视频/图片/虚拟试穿）独立计算，所有API密钥共享配额|
|占用逻辑|任务从进入submitted状态到完成期间持续占用并发，任务结束（含失败）后释放|
|配额计算|取生效中同类型资源包的最大并发值（例：生效5并发+10并发视频包 → 视频并发能力=10）|
## 特别说明

- 视频/虚拟试穿任务：每任务固定占用 1 并发；

- 图片生成任务：并发数=API请求参数n值（例：n=9 → 占用9并发）。

## 超限报错机制

当运行任务数达到并发上限时，提交请求将返回错误：

```JSON

{
    "code": 1303,
    "message": "parallel task over resource pack limit",
    "request_id": "9984d27b-a408-4073-ae28-17ca6a13622d" // uuid
}
```

## 处理建议

因该错误由系统负载状态触发（非参数错误），推荐采用：

- 退避重试策略：使用指数退避算法延迟重试（建议初始延迟≥1s）；

- 队列管理：通过任务队列控制提交速率，动态适配并发余量。

---

# 运镜控制✅---视频生成

|kling-video-o1|std|pro|
|---|---|---|
|视频生成|✅|✅|
|模型|kling-v1||kling-v1-5||kling-v1-6 图生视频||kling-v1-6 文生视频||kling-v2 大师版|
|---|---|---|---|---|---|---|---|---|---|
|模式|STD|PRO|STD|PRO|STD|PRO|STD|PRO|-|
|分辨率|720p|720p|720p|1080p|720p|1080p|720p|1080p|720p|
|帧率|30fps|30fps|30fps|30fps|30fps|30fps|24fps|24fps|24fps|
|模型版本|kling-v2-1 图生视频||kling-v2-1 Master||kling-v2-5 图生视频|kling-v2-5 文生视频|
|---|---|---|---|---|---|---|
|模式|STD|PRO|-|PRO|PRO|PRO|
|分辨率|720p|1080p|1080p|1080p|1080p|1080p|
|帧率|24fps|24fps|24fps|24fps|24fps|24fps|
|kling-v1|std 5s|std 10s|pro 5s|pro 10s|
|---|---|---|---|---|
|文生视频-视频生成|✅|✅|✅|✅|
|运镜控制|✅|-|-|-|
|图生视频-视频生成|✅|✅|✅|✅|
|首尾帧|✅|-|✅|-|
|运动笔刷|✅|-|✅|-|
|其他能力|-|-|-|-|
|视频续写（不支持设置负向提示词和参考强度）|✅|✅|✅|✅|
|视频特效-双人特效（拥抱，亲吻，比心）|✅|✅|✅|✅|
|其他|-|-|-|-|
|kling-v1-5|std 5s|std 10s|pro 5s|pro 10s|
|---|---|---|---|---|
|文生视频-全部能力|-|-|-|-|
|图生视频-视频生成|✅|✅|✅|✅|
|首尾帧|-|-|✅|✅|
|仅尾帧|-|-|✅|✅|
|运动笔刷|-|-|✅|-|
|运镜控制（仅simple）|-|-|✅|-|
|其他能力|-|-|-|-|
|视频续写|✅|✅|✅|✅|
|视频特效-双人特效（拥抱，亲吻，比心）|✅|✅|✅|✅|
|其他|-|-|-|-|
|kling-v1-6|std 5s|std 10s|pro 5s|pro 10s|
|---|---|---|---|---|
|文生视频-视频生成|✅|✅|✅|✅|
|其他能力|-|-|-|-|
|图生视频-视频生成|✅|✅|✅|✅|
|首尾帧|-|-|✅|✅|
|仅尾帧|-|-|✅|✅|
|其他能力|-|-|-|-|
|多图参考生视频|✅|✅|✅|✅|
|多模态视频编辑|✅|✅|✅|✅|
|视频续写|✅|✅|✅|✅|
|视频特效-双人特效（拥抱，亲吻，比心）|✅|✅|✅|✅|
|kling-v2-master|5s|10s|
|---|---|---|
|文生视频-视频生成|✅|✅|
|其他能力|-|-|
|图生视频-视频生成|✅|✅|
|其他能力|-|-|
|其他|-|-|
|kling-v2-1|std 5s|std 10s|pro 5s|pro10s|
|---|---|---|---|---|
|文生视频-全部能力|-|-|-|-|
|图生视频-视频生成|✅|✅|✅|✅|
|首尾帧|-|-|✅|✅|
|其他|-|-|-|-|
|其他|-|-|-|-|
|kling-v2-1-master|5s|10s|
|---|---|---|
|文生视频-视频生成|✅|✅|
|其他能力|-|-|
|图生视频-视频生成|✅|✅|
|其他能力|-|-|
|其他|-|-|
|kling-v2-5-turbo|std 5s|std 10s|pro 5s|pro 10s|
|---|---|---|---|---|
|文生视频-视频生成|✅|✅|✅|✅|
|其他|-|-|-|-|
|图生视频-视频生成|✅|✅|✅|✅|
|首尾帧|-|-|✅|✅|
|其他|-|-|-|-|
|其他|-|-|-|-|
|kling-v2-6|std 5s|std 10s|std 其他时长|pro 5s|pro 10s|pro 其他时长|
|---|---|---|---|---|---|---|
|文生视频-视频生成|-|-|-|✅|✅|-|
|其他|-|-|-|-|-|-|
|图生视频-视频生成|-|-|-|✅|✅|-|
|首尾帧|-|-|-|✅（仅无声视频）|✅（仅无声视频）|-|
|声音控制|-|-|-|✅|✅|-|
|动作控制|-|-|✅|-|-|✅|
|其他|-|-|-|-|-|-|
|与模型版本无关的能力|是否支持|描述|
|---|---|---|
|对口型|✅|可结合文案或音频，驱动视频中角色的口型|
|视频生音效|✅|支持为所有可灵模型生成的视频和用户上传的符合视频格式要求的视频添加音效|
|文生音效|-|支持通过输入文本描述（prompt）生成音效|
|其他|-|-|
## 图像生成

|kling-image-o1|1:1|16:9|4:3|3:2|2:3|3:4|9:16|21:9|auto|
|---|---|---|---|---|---|---|---|---|---|
|文生图|✅|✅|✅|✅|✅|✅|✅|✅|-|
|图生图|✅|✅|✅|✅|✅|✅|✅|✅|✅|
|kling-v1|1:1|16:9|4:3|3:2|2:3|3:4|9:16|21:9|
|---|---|---|---|---|---|---|---|---|
|文生图|✅|✅|✅|✅|✅|✅|✅|-|
|图生图-通用垫图|✅|✅|✅|✅|✅|✅|✅|✅|
|其他能力|-|-|-|-|-|-|-|-|
|kling-v1-5|1:1|16:9|4:3|3:2|2:3|3:4|9:16|21:9|
|---|---|---|---|---|---|---|---|---|
|文生图|✅|✅|✅|✅|✅|✅|✅|✅|
|图生图-角色特征|✅|✅|✅|✅|✅|✅|✅|✅|
|人物长相|✅|✅|✅|✅|✅|✅|✅|✅|
|其他能力|-|-|-|-|-|-|-|-|
|kling-v2|1:1|16:9|4:3|3:2|2:3|3:4|9:16|21:9|
|---|---|---|---|---|---|---|---|---|
|文生图|✅|✅|✅|✅|✅|✅|✅|✅|
|图生图-多图参考生图|✅|✅|✅|✅|✅|✅|✅|✅|
|风格转绘|✅（生成图片分辨率与入参图相同，不支持单独设置分辨率）||||||||
|其他能力|-|-|-|-|-|-|-|-|
|kling-v2-new|1:1|16:9|4:3|3:2|2:3|3:4|9:16|21:9|
|---|---|---|---|---|---|---|---|---|
|文生图|-|-|-|-|-|-|-|-|
|图生图-风格转绘|✅（生成图片分辨率与入参图相同，不支持单独设置分辨率）||||||||
|其他能力|-|-|-|-|-|-|-|-|
|kling-v2-1|1:1|16:9|4:3|3:2|2:3|3:4|9:16|21:9|
|---|---|---|---|---|---|---|---|---|
|文生图|✅|✅|✅|✅|✅|✅|✅|✅|
|图生图-多图参考生图|✅|✅|✅|✅|✅|✅|✅|✅|
|其他能力|-|-|-|-|-|-|-|-|
|与模型版本无关的能力|是否支持|描述|
|---|---|---|
|扩图|✅|可基于已有图片扩展内容|
|其他|-||
|模型|kling-v1||kling-v1-5||kling-2||
|---|---|---|---|---|---|---|
|模式|文生图|图生图|文生图|图生图|文生图|图生图|
|清晰度|1K|1K|1K|1K|1K/2K|1K|
---

# Omni-Video

## 创建任务

|网络协议|请求地址|请求方法|请求格式|响应格式|
|---|---|---|---|---|
|https|/v1/videos/omni-video|POST|application/json|application/json|
### 请求头

|字段|值|描述|
|---|---|---|
|Content-Type|application/json|数据交换格式|
|Authorization|鉴权信息，参考接口鉴权|鉴权信息，参考接口鉴权|
### 请求体

|字段|类型|必填|默认值|描述|
|---|---|---|---|---|
|model_name|string|可选|kling-v1|模型名称，枚举值：kling-video-o1|
|prompt|string|必须|无|文本提示词，可包含正向描述和负向描述；可模板化满足不同需求；不能超过2500个字符；Omni模型可通过<<<>>>格式指定主体、图片或视频（如<<<element_1>>>、<<<image_1>>>、<<<video_1>>>）；能力范围详见可灵Omni模型使用指南|
|image_list|array|可选|空|参考图列表：<br>- 含主体、场景、风格等参考图片，可作为首帧/尾帧（type参数指定：first_frame为首帧，end_frame为尾帧；暂不支持仅尾帧，有尾帧必须有首帧；首帧/首尾帧生视频时不能使用视频编辑功能）<br>- 支持Base64编码或图片URL（确保可访问），格式.jpg/.jpeg/.png<br>- 图片大小≤10MB，宽高≥300px，宽高比1:2.5~2.5:1<br>- 有参考视频时最多4张，无参考视频时最多7张；数组超2张不支持设置尾帧<br>- image_url不能为空<br>示例：<br>{<br>"image_list":[<br>  {"image_url":"image_url", "type":"first_frame"},<br>  {"image_url":"image_url", "type":"end_frame"}<br>]<br>}|
|element_list|array|可选|空|主体参考列表：<br>- 基于主体库中主体的ID配置<br>- 示例：<br>{<br>"element_list":[<br>  {"element_id":long}<br>]<br>}<br>- 有参考视频时，参考图片+主体数量≤4；无参考视频时≤7|
|video_list|array|可选|空|参考视频：<br>- 通过URL获取，可作为特征参考（refer_type=feature）或待编辑视频（refer_type=base，默认）；待编辑视频不能定义首尾帧<br>- 可通过keep_original_sound参数选择是否保留原声（yes/no，对特征参考视频也生效）<br>- 格式仅支持MP4/MOV，时长310秒，宽高720px2160px，帧率24~60fps（生成后输出24fps）<br>- 最多上传1段，大小≤200MB；video_url不能为空<br>示例：<br>{<br>"video_list":[<br>  {"video_url":"video_url", "refer_type":"base", "keep_original_sound":"yes"}<br>]<br>}|
|mode|string|可选|pro|生成视频的模式：<br>- std：标准模式，性价比高<br>- pro：专家模式，高品质|
|aspect_ratio|string|可选|空|画面纵横比（宽:高），枚举值：16:9, 9:16, 1:1；未使用首帧参考或视频编辑功能时必填|
|duration|string|可选|5|生成视频时长（单位s），枚举值：3，4，5，6，7，8，9，10；<br>- 文生视频、首帧图生视频仅支持5和10s<br>- 视频编辑功能（refer_type=base）时，输出时长与传入视频相同，参数无效（按输入时长四舍五入取整计费）|
|callback_url|string|可选|空|任务结果回调通知地址，服务端会在任务状态变更时主动通知；具体schema见“Callback协议”|
|external_task_id|string|可选|空|自定义任务ID，不覆盖系统生成ID，支持通过该ID查询；单用户下需保证唯一性|
### 更多场景调用示例

#### 图片/主体参考

```Plain Text

curl --location 'https://api-beijing.klingai.com/v1/videos/omni-video' \
--header 'Authorization: Bearer xxx' \
--header 'Content-Type: application/json' \
--data '{
    "model_name": "kling-video-o1",
    "prompt": "<<<image_1>>>在东京的街头漫步，偶遇<<<element_1>>>和<<<element_2>>>，并跳到<<<element_2>>>的怀里。视频画面风格与<<<image_2>>>相同",
    "image_list": [
        {
         "image_url": "xxxxx"
        },
        {
         "image_url": "xxxxx"
        }
    ],
    "element_list": [
        {
         "element_id": long
        },
        {
         "element_id": long
        }
    ],
    "mode": "pro",
    "aspect_ratio": "1:1",
    "duration": "7"
}'
```

#### 指令变换（视频编辑）

```Plain Text

curl --location 'https://api-beijing.klingai.com/v1/videos/omni-video' \
--header 'Authorization: Bearer xxx' \
--header 'Content-Type: application/json' \
--data '{
    "model_name": "kling-video-o1",
    "prompt": "给<<<video_1>>>中的穿蓝衣服的女孩，戴上<<<image_1>>>中的王冠",
    "image_list": [
      {
       "image_url": "xxx"
      }
    ],
    "video_list": [
      {
        "video_url":"xxxxxxxx",
        "refer_type":"base",
        "keep_original_sound":"yes"
      }
    ],
    "mode": "pro"
}'
```

#### 视频参考（生成下一个/上一个镜头）

```Plain Text

curl --location 'https://api-beijing.klingai.com/v1/videos/omni-video' \
--header 'Authorization: Bearer xxx' \
--header 'Content-Type: application/json' \
--data '{
    "model_name": "kling-video-o1",
    "prompt": "基于<<<video_1>>>，生成下一个镜头",
    "video_list": [
      {
        "video_url":"xxxxxxxx",
        "refer_type":"feature",
        "keep_original_sound":"yes"
      }
    ],
    "mode": "pro"
}'
```

#### 首尾帧图生视频

```Plain Text

curl --location 'https://api-beijing.klingai.com/v1/videos/omni-video' \
--header 'Authorization: Bearer xxx' \
--header 'Content-Type: application/json' \
--data '{
    "model_name": "kling-video-o1",
    "prompt": "视频中的人跳舞",
    "image_list": [
      {
       "image_url": "xxx",
        "type": "first_frame"
      },
      {
       "image_url": "xxx",
        "type": "end_frame"
      }
    ],
    "mode": "pro"
}'
```

#### 文生视频

```Plain Text

curl --location 'https://api-beijing.klingai.com/v1/videos/omni-video' \
--header 'Authorization: Bearer xxx' \
--header 'Content-Type: application/json' \
--data '{
    "model_name": "kling-video-o1",
    "prompt": "视频中的人跳舞",
    "mode": "pro",
    "aspect_ratio": "1:1",
    "duration": "7"
}'
```

### FAQ

1. 生成视频时长（duration）支持情况：

    - 文生、图生（不含首尾帧）：可选5s/10s；

    - 有视频输入且使用视频编辑功能（type=base）：不可指定，与视频对齐；

    - 其他情况（不传视频+传图片+主体，或传视频+type=feature）：可选3-10s。

2. 视频延长实现方式：

    - 通过“视频参考”，传入视频，用prompt驱动模型“生成下一个镜头”或“生成上一个镜头”（示例见“视频参考”场景）。

3. 生成视频宽高比（aspect_ratio）支持情况：

    - 不支持：指令变换（视频编辑）、图生视频（含首尾帧）；

    - 支持：文生视频、图片/主体参考、视频参考（生成下一个/上一个镜头等）。

### 响应体

```JSON

{
"code": 0, // 错误码；具体定义见错误码
"message": "string", // 错误信息
"request_id": "string", // 请求ID，系统生成，用于跟踪请求、排查问题
"data": {
"task_id": "string", // 任务ID，系统生成
"task_info": { // 任务创建时的参数信息
"external_task_id": "string" // 客户自定义任务ID
},
"task_status": "string", // 任务状态，枚举值：submitted（已提交）、processing（处理中）、succeed（成功）、failed（失败）
"created_at": 1722769557708, // 任务创建时间，Unix时间戳、单位ms
"updated_at": 1722769557708 // 任务更新时间，Unix时间戳、单位ms
}
}
```

## 查询任务（单个）

|网络协议|请求地址|请求方法|请求格式|响应格式|
|---|---|---|---|---|
|https|/v1/videos/omni-video/{id}|GET|application/json|application/json|
### 请求头

|字段|值|描述|
|---|---|---|
|Content-Type|application/json|数据交换格式|
|Authorization|鉴权信息，参考接口鉴权|鉴权信息，参考接口鉴权|
### 请求路径参数

|字段|类型|必填|默认值|描述|
|---|---|---|---|---|
|task_id|string|可选|无|文生视频的任务ID，请求路径参数，与external_task_id二选一|
|external_task_id|string|可选|无|文生视频的自定义任务ID，创建任务时填写，与task_id二选一|
### 请求体

无

### 响应体

```JSON

{
"code": 0, // 错误码；具体定义见错误码
"message": "string", // 错误信息
"request_id": "string", // 请求ID，系统生成，用于跟踪请求、排查问题
"data": {
"task_id": "string", // 任务ID，系统生成
"task_status": "string", // 任务状态，枚举值：submitted（已提交）、processing（处理中）、succeed（成功）、failed（失败）
"task_status_msg": "string", // 任务状态信息，失败时展示原因
"task_info": { // 任务创建时的参数信息
"external_task_id": "string" // 客户自定义任务ID
},
"task_result": {
"videos": [
{
"id": "string", // 生成的视频ID；全局唯一
"url": "string", // 生成视频的URL（防盗链格式，30天后清理，请及时转存）
"duration": "string" // 视频总时长，单位s
}
]
},
"created_at": 1722769557708, // 任务创建时间，Unix时间戳、单位ms
"updated_at": 1722769557708 // 任务更新时间，Unix时间戳、单位ms
}
}
```

## 查询任务（列表）

|网络协议|请求地址|请求方法|请求格式|响应格式|
|---|---|---|---|---|
|https|/v1/videos/omni-video|GET|application/json|application/json|
### 请求头

|字段|值|描述|
|---|---|---|
|Content-Type|application/json|数据交换格式|
|Authorization|鉴权信息，参考接口鉴权|鉴权信息，参考接口鉴权|
### 查询参数

|字段|类型|必填|默认值|描述|
|---|---|---|---|---|
|pageNum|int|可选|1|页码，取值范围：[1,1000]|
|pageSize|int|可选|30|每页数据量，取值范围：[1,500]|
### 请求体

无

### 响应体

```JSON

{
"code": 0, // 错误码；具体定义见错误码
"message": "string", // 错误信息
"request_id": "string", // 请求ID，系统生成，用于跟踪请求、排查问题
"data": [
{
"task_id": "string", // 任务ID，系统生成
"task_status": "string", // 任务状态，枚举值：submitted（已提交）、processing（处理中）、succeed（成功）、failed（失败）
"task_status_msg": "string", // 任务状态信息，失败时展示原因
"task_info": { // 任务创建时的参数信息
"external_task_id": "string" // 客户自定义任务ID
},
"task_result": {
"videos": [
{
"id": "string", // 生成的视频ID；全局唯一
"url": "string", // 生成视频的URL（防盗链格式，30天后清理，请及时转存）
"duration": "string" // 视频总时长，单位s
}
]
},
"created_at": 1722769557708, // 任务创建时间，Unix时间戳、单位ms
"updated_at": 1722769557708 // 任务更新时间，Unix时间戳、单位ms
}
]
}
```

---

# 图生视频

## 创建任务

|网络协议|请求地址|请求方法|请求格式|响应格式|
|---|---|---|---|---|
|https|/v1/videos/image2video|POST|application/json|application/json|
### 请求头

|字段|值|描述|
|---|---|---|
|Content-Type|application/json|数据交换格式|
|Authorization|鉴权信息，参考接口鉴权|鉴权信息，参考接口鉴权|
### 请求体

注意：为保持命名统一，原model字段变更为model_name字段，未来请使用该字段指定模型版本；继续使用原model字段仍有效，等价于model_name为空时的默认行为（调用V1模型）。

#### 示例

```Bash

curl --location --request POST 'https://api-beijing.klingai.com/v1/videos/image2video' \
--header 'Authorization: Bearer xxx' \
--header 'Content-Type: application/json' \
--data-raw '{
    "model_name": "kling-v1",
    "mode": "pro",
    "duration": "5",
    "image": "https://h2.inkwai.com/bs2/upload-ylab-stunt/se/ai_portal_queue_mmu_image_upscale_aiweb/3214b798-e1b4-4b00-b7af-72b5b0417420_raw_image_0.jpg",
    "prompt": "宇航员站起身走了",
    "cfg_scale": 0.5,
    "static_mask": "https://h2.inkwai.com/bs2/upload-ylab-stunt/ai_portal/1732888177/cOLNrShrSO/static_mask.png",
    "dynamic_masks": [
      {
        "mask": "https://h2.inkwai.com/bs2/upload-ylab-stunt/ai_portal/1732888130/WU8spl23dA/dynamic_mask_1.png",
        "trajectories": [
          {"x":279,"y":219},{"x":417,"y":65}
        ]
      }
    ]
}'
```

|字段|类型|必填|默认值|描述|
|---|---|---|---|---|
|model_name|string|可选|kling-v1|模型名称，枚举值：kling-v1, kling-v1-5, kling-v1-6, kling-v2-master, kling-v2-1, kling-v2-1-master, kling-v2-5-turbo, kling-v2-6|
|image|string|必须|空|参考图像：<br>- 支持Base64编码（无前缀）或图片URL（确保可访问）<br>- 格式.jpg/.jpeg/.png，大小≤10MB，宽高≥300px，宽高比1:2.5~2.5:1<br>- 与image_tail至少二选一，不能同时为空<br>- 与image_tail、dynamic_masks/static_mask、camera_control三选一，不能同时使用<br>- 不同模型/模式支持范围见3-0能力地图|
|image_tail|string|可选|空|参考图像-尾帧控制：<br>- 支持Base64编码（无前缀）或图片URL（确保可访问）<br>- 格式.jpg/.jpeg/.png，大小≤10MB，宽高≥300px<br>- 与image至少二选一，不能同时为空<br>- 与image、dynamic_masks/static_mask、camera_control三选一，不能同时使用<br>- 不同模型/模式支持范围见3-0能力地图|
|prompt|string|可选|无|正向文本提示词：<br>- 不能超过2500个字符<br>- 用<<<voice_1>>>指定音色（序号同voice_list），最多引用2个音色；指定时sound参数必须为on<br>- 语法简洁，如“男人<<<voice_1>>>说：‘你好’”<br>- 引用音色时按“有指定音色”计费<br>- 不同模型/模式支持范围见3-0能力地图|
|negative_prompt|string|可选|空|负向文本提示词，不能超过2500个字符|
|voice_list|array|可选|无|生成视频引用的音色列表：<br>- 最多2个音色，voice_id通过音色定制接口返回或使用系统预置音色（非对口型API的voice_id）<br>- 仅V2.6及后续版本支持<br>- 示例：<br>{<br>"voice_list":[<br>  {"voice_id":"voice_id_1"},<br>  {"voice_id":"voice_id_2"}<br>]<br>}|
|sound|string|可选|off|生成视频时是否同时生成声音，枚举值：on，off；仅V2.6及后续版本支持|
|cfg_scale|float|可选|0.5|生成视频的自由度（值越大，与提示词相关性越强），取值范围[0,1]；kling-v2.x模型不支持|
|mode|string|可选|std|生成视频的模式：<br>- std：标准模式，性价比高<br>- pro：专家模式，高品质<br>- 不同模型/模式支持范围见3-0能力地图|
|static_mask|string|可选|无|静态笔刷涂抹区域：<br>- 支持Base64编码（无前缀）或图片URL（确保可访问），格式同image<br>- 长宽比必须与image相同，与dynamic_masks.mask分辨率一致<br>- 不同模型/模式支持范围见3-0能力地图|
|dynamic_masks|array|可选|无|动态笔刷配置列表：<br>- 最多6组，每组含“mask（涂抹区域，要求同static_mask）”和“trajectories（运动轨迹坐标序列）”<br>- trajectories：5s视频轨迹长度≤77（坐标个数2-77），以图片左下角为原点，顺序为轨迹方向<br>- 不同模型/模式支持范围见3-0能力地图|
|camera_control|object|可选|空|控制摄像机运动的协议（未指定则智能匹配）：<br>- type：预定义运镜类型（枚举值：simple、down_back、forward_up、right_turn_forward、left_turn_forward）<br>  - simple：需在config中六选一运镜<br>  - 其他类型：无需填写config<br>- config：含horizontal、vertical、pan、tilt、roll、zoom六个字段，六选一（仅一个参数非0），取值范围[-10,10]<br>- 不同模型/模式支持范围见3-0能力地图|
|duration|string|可选|5|生成视频时长（单位s），枚举值：5，10|
|callback_url|string|可选|无|任务结果回调通知地址，服务端会在任务状态变更时主动通知；具体schema见“Callback协议”|
|external_task_id|string|可选|无|自定义任务ID，不覆盖系统生成ID，支持通过该ID查询；单用户下需保证唯一性|
### 响应体

```JSON

{
"code": 0, // 错误码；具体定义见错误码
"message": "string", // 错误信息
"request_id": "string", // 请求ID，系统生成，用于跟踪请求、排查问题
"data": {
"task_id": "string", // 任务ID，系统生成
"task_info": {
// 任务创建时的参数信息
"external_task_id": "string" // 客户自定义任务ID
},
"task_status": "string", // 任务状态，枚举值：submitted（已提交）、processing（处理中）、succeed（成功）、failed（失败）
"created_at": 1722769557708, // 任务创建时间，Unix时间戳、单位ms
"updated_at": 1722769557708 // 任务更新时间，Unix时间戳、单位ms
}
}
```

## 查询任务（单个）

|网络协议|请求地址|请求方法|请求格式|响应格式|
|---|---|---|---|---|
|https|/v1/videos/image2video/{id}|GET|application/json|application/json|
### 请求头

|字段|值|描述|
|---|---|---|
|Content-Type|application/json|数据交换格式|
|Authorization|鉴权信息，参考接口鉴权|鉴权信息，参考接口鉴权|
### 请求路径参数

|字段|类型|必填|默认值|描述|
|---|---|---|---|---|
|task_id|string|可选|无|图生视频的任务ID，请求路径参数，与external_task_id二选一|
|external_task_id|string|可选|无|图生视频的自定义任务ID，创建任务时填写，与task_id二选一|
### 请求体

无

### 响应体

```JSON

{
"code": 0, // 错误码；具体定义见错误码
"message": "string", // 错误信息
"request_id": "string", // 请求ID，系统生成，用于跟踪请求、排查问题
"data": {
"task_id": "string", // 任务ID，系统生成
"task_status": "string", // 任务状态，枚举值：submitted（已提交）、processing（处理中）、succeed（成功）、failed（失败）
"task_status_msg": "string", // 任务状态信息，失败时展示原因
"task_info": { // 任务创建时的参数信息
"external_task_id": "string" // 客户自定义任务ID
},
"task_result": {
"videos": [
{
"id": "string", // 生成的视频ID；全局唯一
"url": "string", // 生成视频的URL（30天后清理，请及时转存）
"duration": "string" // 视频总时长，单位s
}
]
},
"created_at": 1722769557708, // 任务创建时间，Unix时间戳、单位ms
"updated_at": 1722769557708 // 任务更新时间，Unix时间戳、单位ms
}
}
```

## 查询任务（列表）

|网络协议|请求地址|请求方法|请求格式|响应格式|
|---|---|---|---|---|
|https|/v1/videos/image2video|GET|application/json|application/json|
### 请求头

|字段|值|描述|
|---|---|---|
|Content-Type|application/json|数据交换格式|
|Authorization|鉴权信息，参考接口鉴权|鉴权信息，参考接口鉴权|
### 查询参数

|字段|类型|必填|默认值|描述|
|---|---|---|---|---|
|pageNum|int|可选|1|页码，取值范围：[1,1000]|
|pageSize|int|可选|30|每页数据量，取值范围：[1,500]|
### 请求体

无

### 响应体

```JSON

{
"code": 0, // 错误码；具体定义见错误码
"message": "string", // 错误信息
"request_id": "string", // 请求ID，系统生成，用于跟踪请求、排查问题
"data": [
{
"task_id": "string", // 任务ID，系统生成
"task_status": "string", // 任务状态，枚举值：submitted（已提交）、processing（处理中）、succeed（成功）、failed（失败）
"task_status_msg": "string", // 任务状态信息，失败时展示原因
"task_info": { // 任务创建时的参数信息
"external_task_id": "string" // 客户自定义任务ID
},
"task_result": {
"videos": [
{
"id": "string", // 生成的视频ID；全局唯一
"url": "string", // 生成视频的URL（30天后清理，请及时转存）
"duration": "string" // 视频总时长，单位s
}
]
},
"created_at": 1722769557708, // 任务创建时间，Unix时间戳、单位ms
"updated_at": 1722769557708 // 任务更新时间，Unix时间戳、单位ms
}
]
}
```

---

# 视频特效

## 创建任务

|网络协议|请求地址|请求方法|请求格式|响应格式|
|---|---|---|---|---|
|https|/v1/videos/effects|POST|application/json|application/json|
### 请求头

|字段|值|描述|
|---|---|---|
|Content-Type|application/json|数据交换格式|
|Authorization|鉴权信息，参考接口鉴权|鉴权信息，参考接口鉴权|
### 请求体

|字段|类型|必填|默认值|描述|
|---|---|---|---|---|
|effect_scene|string|必须|无|场景名称，枚举值：cheers_2026, kiss_pro, fight_pro, hug_pro, countdown_teleport, santa_random_surprise, magic_match_tree, bullet_time_360, happy_birthday, birthday_star, thumbs_up_pro, tiger_hug_pro, pet_lion_pro, surprise_bouquet, bouquet_drop, 3d_cartoon_1_pro, firework_2026, glamour_photo_shoot, box_of_joy, first_toast_of_the_year, my_santa_pic, santa_gift, steampunk_christmas, snowglobe, christmas_photo_shoot, ornament_crash, santa_express, instant_christmas, particle_santa_surround, coronation_of_frost, building_sweater, spark_in_the_snow, scarlet_and_snow, cozy_toon_wrap, bullet_time_lite, magic_cloak, balloon_parade, jumping_ginger_joy, bullet_time, c4d_cartoon_pro, pure_white_wings, black_wings, golden_wing, pink_pink_wings, venomous_spider, throne_of_king, luminous_elf, woodland_elf, japanese_anime_1, american_comics, guardian_spirit, swish_swish, snowboarding, witch_transform, vampire_transform, pumpkin_head_transform, demon_transform, mummy_transform, zombie_transform, cute_pumpkin_transform, cute_ghost_transform, knock_knock_halloween, halloween_escape, baseball, inner_voice, a_list_look, memory_alive, trampoline, trampoline_night, pucker_up, guess_what, feed_mooncake, rampage_ape, flyer, dishwasher, pet_chinese_opera, magic_fireball, gallery_ring, pet_moto_rider, muscle_pet, squeeze_scream, pet_delivery, running_man, disappear, mythic_style, steampunk, c4d_cartoon, 3d_cartoon_1, 3d_cartoon_2, eagle_snatch, hug_from_past, firework, media_interview, pet_lion, pet_chef, santa_gifts, santa_hug, girlfriend, boyfriend, heart_gesture_1, pet_wizard, smoke_smoke, thumbs_up, instant_kid, dollar_rain, cry_cry, building_collapse, gun_shot, mushroom, double_gun, pet_warrior, lightning_power, jesus_hug, shark_alert, long_hair, lie_flat, polar_bear_hug, brown_bear_hug, jazz_jazz, office_escape_plow, fly_fly, watermelon_bomb, pet_dance, boss_coming, wool_curly, pet_bee, marry_me, swing_swing, day_to_night, piggy_morph, wig_out, car_explosion, ski_ski, tiger_hug, siblings, construction_worker, let’s_ride, snatched, magic_broom, felt_felt, jumpdrop, celebration, splashsplash, surfsurf, fairy_wing, angel_wing, dark_wing, skateskate, plushcut, jelly_press, jelly_slice, jelly_squish, jelly_jiggle, pixelpixel, yearbook, instant_film, anime_figure, rocketrocket, bloombloom, dizzydizzy, fuzzyfuzzy, squish, expansion, hug, kiss, heart_gesture, fight；更多参数见特效模版中心|
|input|object|必须|无|支持不同任务输入的结构体，根据scene不同，字段不同（详见场景请求体）|
|callback_url|string|可选|无|任务结果回调通知地址，服务端会在任务状态变更时主动通知；具体schema见“Callback协议”|
|external_task_id|string|可选|无|自定义任务ID，不覆盖系统生成ID，支持通过该ID查询；单用户下需保证唯一性|
### 场景请求体

#### 单图特效（159款）

- 包含场景：countdown_teleport, santa_random_surprise, magic_match_tree, bullet_time_360, happy_birthday, birthday_star, thumbs_up_pro, tiger_hug_pro, pet_lion_pro, surprise_bouquet, bouquet_drop, 3d_cartoon_1_pro, firework_2026, glamour_photo_shoot, box_of_joy, first_toast_of_the_year, my_santa_pic, santa_gift, steampunk_christmas, snowglobe, christmas_photo_shoot, ornament_crash, santa_express, instant_christmas, particle_santa_surround, coronation_of_frost, building_sweater, spark_in_the_snow, scarlet_and_snow, cozy_toon_wrap, bullet_time_lite, magic_cloak, balloon_parade, jumping_ginger_joy, bullet_time, c4d_cartoon_pro, pure_white_wings, black_wings, golden_wing, pink_pink_wings, venomous_spider, throne_of_king, luminous_elf, woodland_elf, japanese_anime_1, american_comics, guardian_spirit, swish_swish, snowboarding, witch_transform, vampire_transform, pumpkin_head_transform, demon_transform, mummy_transform, zombie_transform, cute_pumpkin_transform, cute_ghost_transform, knock_knock_halloween, halloween_escape, baseball, inner_voice, a_list_look, memory_alive, trampoline, trampoline_night, pucker_up, guess_what, feed_mooncake, rampage_ape, flyer, dishwasher, pet_chinese_opera, magic_fireball, gallery_ring, pet_moto_rider, muscle_pet, squeeze_scream, pet_delivery, running_man, disappear, mythic_style, steampunk, c4d_cartoon, 3d_cartoon_1, 3d_cartoon_2, eagle_snatch, hug_from_past, firework, media_interview, pet_lion, pet_chef, santa_gifts, santa_hug, girlfriend, boyfriend, heart_gesture_1, pet_wizard, smoke_smoke, thumbs_up, instant_kid, dollar_rain, cry_cry, building_collapse, gun_shot, mushroom, double_gun, pet_warrior, lightning_power, jesus_hug, shark_alert, long_hair, lie_flat, polar_bear_hug, brown_bear_hug, jazz_jazz, office_escape_plow, fly_fly, watermelon_bomb, pet_dance, boss_coming, wool_curly, pet_bee, marry_me, swing_swing, day_to_night, piggy_morph, wig_out, car_explosion, ski_ski, tiger_hug, siblings, construction_worker, let’s_ride, snatched, magic_broom, felt_felt, jumpdrop, celebration, splashsplash, surfsurf, fairy_wing, angel_wing, dark_wing, skateskate, plushcut, jelly_press, jelly_slice, jelly_squish, jelly_jiggle, pixelpixel, yearbook, instant_film, anime_figure, rocketrocket, bloombloom, dizzydizzy, fuzzyfuzzy, squish, expansion

|字段|类型|必填|默认值|描述|
|---|---|---|---|---|
|image|string|必须|无|参考图像：<br>- 支持Base64编码（无前缀）或图片URL（确保可访问）<br>- 格式.jpg/.jpeg/.png，大小≤10MB，宽高≥300px，宽高比1:2.5~2.5:1|
|duration|string|必须|无|生成视频时长（单位s），枚举值：5|
##### 单人特效请求示例

```JSON

{
  "effect_scene": "pet_lion",
  "input":{
    "image":"https://p2-kling.klingai.com/bs2/upload-ylab-stunt/c54e463c95816d959602f1f2541c62b2.png?x-kcdn-pid=112452",
    "duration": "5"
  }
}
```

#### 双人互动特效（8款）

- 包含场景：cheers_2026, kiss_pro, fight_pro, hug_pro, hug, kiss, heart_gesture, fight

|字段|类型|必填|默认值|描述|
|---|---|---|---|---|
|model_name|string|可选|kling-v1|模型名称：<br>- cheers_2026, kiss_pro, fight_pro, hug_pro 无需填写<br>- 枚举值：kling-v1, kling-v1-5, kling-v1-6<br>- fight 仅支持kling-v1-6；hug, kiss, heart_gesture支持kling-v1, kling-v1-5, kling-v1-6|
|mode|string|可选|std|生成视频的模式：<br>- cheers_2026, kiss_pro, fight_pro, hug_pro 无需填写<br>- std：标准模式，性价比高<br>- pro：专家模式，高品质|
|images|array|必须|无|参考图像组：<br>- 数组长度必须为2（第一张在左，第二张在右，自动拼接合照）<br>- 支持Base64编码（无前缀）或图片URL（确保可访问）<br>- 格式.jpg/.jpeg/.png，大小≤10MB，宽高≥300px，宽高比1:2.5~2.5:1|
|duration|string|必须|无|生成视频时长（单位s）：<br>- cheers_2026, kiss_pro, fight_pro, hug_pro 仅支持5<br>- 枚举值：5，10|
##### 双人特效请求示例

```JSON

{
"effect_scene": "hug",
"input": {
"model_name": "kling-v1-6",
"mode": "std",
"images": [
"https://p2-kling.klingai.com/bs2/upload-ylab-stunt/c54e463c95816d959602f1f2541c62b2.png?x-kcdn-pid=112452",
"https://p2-kling.klingai.com/bs2/upload-ylab-stunt/5eef15e03a70e1fa80732808a2f50f3f.png?x-kcdn-pid=112452"
],
"duration": "5"
}
}
```

### 响应体

```JSON

{
"code": 0, // 错误码；具体定义见错误码
"message": "string", // 错误信息
"request_id": "string", // 请求ID，系统生成，用于跟踪请求、排查问题
"data": {
"task_id": "string", // 任务ID，系统生成
"task_info": { // 任务创建时的参数信息
"external_task_id": "string" // 客户自定义任务ID
},
"task_status": "string", // 任务状态，枚举值：submitted（已提交）、processing（处理中）、succeed（成功）、failed（失败）
"created_at": 1722769557708, // 任务创建时间，Unix时间戳、单位ms
"updated_at": 1722769557708 // 任务更新时间，Unix时间戳、单位ms
}
}
```

## 查询任务（单个）

|网络协议|请求地址|请求方法|请求格式|响应格式|
|---|---|---|---|---|
|https|/v1/videos/effects/{id}|GET|application/json|application/json|
### 请求头

|字段|值|描述|
|---|---|---|
|Content-Type|application/json|数据交换格式|
|Authorization|鉴权信息，参考接口鉴权|鉴权信息，参考接口鉴权|
### 请求路径参数

|字段|类型|必填|默认值|描述|
|---|---|---|---|---|
|task_id|string|必须|无|视频特效的任务ID，请求路径参数，与external_task_id二选一|
|external_task_id|string|必须|无|视频特效的自定义任务ID，创建任务时填写，与task_id二选一|
### 请求体

无

### 响应体

```JSON

{
"code": 0, // 错误码；具体定义见错误码
"message": "string", // 错误信息
"request_id": "string", // 请求ID，系统生成，用于跟踪请求、排查问题
"data": {
"task_id": "string", // 任务ID，系统生成
"task_status": "string", // 任务状态，枚举值：submitted（已提交）、processing（处理中）、succeed（成功）、failed（失败）
"task_status_msg": "string", // 任务状态信息，失败时展示原因
"task_info": { // 任务创建时的参数信息
"external_task_id": "string" // 客户自定义任务ID
},
"task_result": {
"videos": [
{
"id": "string", // 生成的视频ID；全局唯一
"url": "string", // 生成视频的URL（30天后清理，请及时转存）
"duration": "string" // 视频总时长，单位s
}
]
},
"created_at": 1722769557708, // 任务创建时间，Unix时间戳、单位ms
"updated_at": 1722769557708 // 任务更新时间，Unix时间戳、单位ms
}
}
```

## 查询任务（列表）

|网络协议|请求地址|请求方法|请求格式|响应格式|
|---|---|---|---|---|
|https|/v1/videos/effects|GET|application/json|application/json|
### 请求头

|字段|值|描述|
|---|---|---|
|Content-Type|application/json|数据交换格式|
|Authorization|鉴权信息，参考接口鉴权|鉴权信息，参考接口鉴权|
### 查询参数

|字段|类型|必填|默认值|描述|
|---|---|---|---|---|
|pageNum|int|可选|1|页码，取值范围：[1,1000]|
|pageSize|int|可选|30|每页数据量，取值范围：[1,500]|
### 请求体

无

### 响应体

```JSON

{
"code": 0, // 错误码；具体定义见错误码
"message": "string", // 错误信息
"request_id": "string", // 请求ID，系统生成，用于跟踪请求、排查问题
"data": [
{
"task_id": "string", // 任务ID，系统生成
"task_status": "string", // 任务状态，枚举值：submitted（已提交）、processing（处理中）、succeed（成功）、failed（失败）
"task_status_msg": "string", // 任务状态信息，失败时展示原因
"task_info": { // 任务创建时的参数信息
"external_task_id": "string" // 客户自定义任务ID
},
"task_result": {
"videos": [
{
"id": "string", // 生成的视频ID；全局唯一
"url": "string", // 生成视频的URL（30天后清理，请及时转存）
"duration": "string" // 视频总时长，单位s
}
]
},
"created_at": 1722769557708, // 任务创建时间，Unix时间戳、单位ms
"updated_at": 1722769557708 // 任务更新时间，Unix时间戳、单位ms
}
]
}
```

---

# 视频生音效

## 创建任务

|网络协议|请求地址|请求方法|请求格式|响应格式|
|---|---|---|---|---|
|https|/v1/audio/video-to-audio|POST|application/json|application/json|
### 请求头

|字段|值|描述|
|---|---|---|
|Content-Type|application/json|数据交换格式|
|Authorization|鉴权信息，参考接口鉴权|鉴权信息，参考接口鉴权|
### 请求体

|字段|类型|必填|默认值|描述|
|---|---|---|---|---|
|video_id|string|可选|无|可灵AI生成的视频ID：<br>- 与video_url二选一，不能同时为空或有值<br>- 仅支持30天内生成、长度3.0-20.0秒的视频|
|video_url|string|可选|无||
> （注：文档部分内容可能由 AI 生成）