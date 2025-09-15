# 斗拱支付 - 签名验签

本文档详细说明斗拱支付接口的认证与安全机制，包括签名、验签、加密解密的完整实现。

## 认证与安全

通过签名算法，来验证接口使用者身份以及保证接口数据不被篡改。

### 加签

- 目的: 保证数据传输过程中的数据真实性和完整性。
- 密钥: 接口调用者生成一对公私钥；使用私钥进行签名，公钥进行验签。
- 签名原文: 上送报文体中的业务数据 `data` 即为签名原文。
- 签名算法: 采用 SHA256WithRSA。
- 放置位置: 计算得到签名后，放入报文体的 `sign` 字段一并上送。
- 返回验签: 汇付返回的报文也携带返回值签名，商户使用汇付公钥进行验签。
- 建议: 如无特殊场景，尽可能使用 API 管理平台的鉴权和加验签来实现功能。

### 鉴权

- 按官方文档要求完成接入与权限配置；建议配合"加验签"能力共同使用。

### 加密

- 敏感信息（如银行卡号）需加密传输。
- 加解密规则: 使用公钥进行加密，使用私钥进行解密。

## 证书生成

V1 与 V2 版本接口证书生成方法一致，生成的私钥和公钥可共用。以下示例基于 OpenSSL（Windows 需先安装 OpenSSL）：

1) 生成私钥（2048 位）
```bash
openssl genrsa -out rsa_private_key.pem 2048
```

2) 根据私钥生成公钥
```bash
openssl rsa -in rsa_private_key.pem -pubout -out rsa_public_key_2048.pub
```

3) 私钥转为 PKCS#8 格式（仅保留 RSA 内容）
```bash
openssl pkcs8 -topk8 -inform PEM -in rsa_private_key.pem -outform PEM -nocrypt > rsa_private_key_pkcs8.pem
```

注：本库签名实现使用 PKCS#8 私钥头尾（BEGIN/END PRIVATE KEY）。

## 联调公私钥参数获取

最近更新时间：2025.03.12

API 体系需要两对密钥支撑：汇付的公钥、私钥 + 商户的公钥、私钥。

注意与持有范围：
- 私钥相当于密码，务必妥善保存、不要传递。
- 商户侧通常持有：汇付公钥 + 商户公钥 + 商户私钥。

密钥作用：
- 汇付公钥：用于验证请求返回/异步请求，以及对部分参数进行加密。
- 商户私钥：用于对请求参数进行加签，以及对部分参数进行解密。

### 获取流程

一) 开通商户：开通生产或联调环境的商户，并获取管理员操作账号（联系销售经理协助）。

二) 登录控制台生成公私钥：使用管理员账号登录控制台，在【开发设置】->【密钥管理】中生成密钥。

三) 复制密钥：复制【商户私钥】与【汇付公钥】并安全存放。

说明与注意：
- 若商户使用自有公私钥对，请将商户公钥粘贴到【系统公钥】文本框供斗拱侧使用；若无自有公私钥，可由斗拱为商户生成并回传，商户据此配置到自身系统。
- 提交前请确认【接口权限】非"关闭"，否则联调会失败。
- 斗拱提供的 CLI 工具亦提供 `genrsa` 命令，可在本地生成公私钥对。

例（Java SDK，加验签密钥设置）：
```java
merConfig.setRsaPrivateKey("商户私钥");
merConfig.setRsaPublicKey("汇付公钥");
```

本库映射（PHP 配置）：
- `Yourname\\DougongPay\\Core\\DougongConfig` 接收：
  - `rsa_private_key`：商户私钥（PKCS#8，无头尾或仅内容；内部会包裹 BEGIN/END PRIVATE KEY）
  - `rsa_public_key`：汇付公钥（无头尾或仅内容；内部会包裹 BEGIN/END PUBLIC KEY）

## v2 版接口加签验签

最近更新时间：2024.9.1

### 适用范围

- 适用于前缀为 `https://api.huifu.com/v2/` 的所有接口。

### 公私钥

- 参见"联调公私钥参数获取"。

### 参数形式

- 所有接口以 POST 方法请求一个完整的 JSON 对象（不是 JSON 字符串）。
- `body.data` 为完整业务参数：`body.data` 是 JSON 对象（非字符串）。
- `body.data` 第一层为基本类型（字符串、整数等）；若出现多层嵌套，请转为 JSON 字符串传递（例如 `terminal_device_info`）。
- `body.data` 第一层所有传递的参数参与签名。

示例：
```json
{
  "sys_id": "test",
  "product_id": "HSK",
  "data": {
    "devs_id": "TYXJL0623715525894234",
    "auth_code": "134558771750600000",
    "terminal_device_info": "{\"devs_id\":\"TYXJL0623715525894234\"}"
  },
  "sign": "签名"
}
```

### 如何加签

- 仅对 `body.data` 中的内容进行加签。
- 将 `body.data` 去掉换行、空格等格式字符后，对第一层参数按参数名 ASCII 升序排序，生成 JSON 字符串作为签名原文。
- 注：若某字段值本身是 JSON 字符串（用于承载内层复杂对象），该值不参与二次排序，按原样参与签名。

排序后的示例（与上例一致）：
```json
{
  "auth_code": "134558771750600000",
  "devs_id": "TYXJL0623715525894234",
  "terminal_device_info": "{\"devs_id\":\"TYXJL0623715525894234\"}"
}
```

语言示例（JSON 排序，仅排序第一层）：
- Java：`JSON.toJSONString(JSONObject.parseObject(inputDataJSON, TreeMap.class))`
- Python：
```python
def sort_dict(params):
    keys = sorted(params.keys())
    result = {}
    for key in keys:
        value = params.get(key)
        if isinstance(value, dict):
            result[key] = value
        elif isinstance(value, list) and len(value) != 0:
            result[key] = value
        elif value is not None:
            result[key] = value
    return result
```
- PHP：`ksort($post_data); json_encode($post_data, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);`
- C#：
```csharp
public static string sort4JsonString(string sourceJson) {
    var dic = JsonConvert.DeserializeObject<SortedDictionary<string, object>>(sourceJson);
    var result = dic.OrderBy(m => m.Key).ToDictionary(x => x.Key, x => x.Value);
    return JsonConvert.SerializeObject(result);
}
```

签名算法：SHA256WithRSA（PKCS#8 私钥）。示例：
- Java：
```java
public static String sign(String data, String privateKeyBase64) {
    try {
        byte[] bytes = Base64.getDecoder().decode(privateKeyBase64);
        PKCS8EncodedKeySpec keySpec = new PKCS8EncodedKeySpec(bytes);
        KeyFactory keyFactory = KeyFactory.getInstance("RSA");
        PrivateKey privateKey = keyFactory.generatePrivate(keySpec);
        Signature signature = Signature.getInstance("SHA256WithRSA");
        signature.initSign(privateKey);
        signature.update(data.getBytes("UTF-8"));
        return Base64.getEncoder().encodeToString(signature.sign());
    } catch (Exception e) {
        return null;
    }
}
```
- Python：
```python
def rsa_sign(private_key, message, charset='utf-8'):
    try:
        private_key = add_start_end(private_key, "-----BEGIN PRIVATE KEY-----\n", "\n-----END PRIVATE KEY-----")
        msg = message.encode(charset)
        private_key = RSA.importKey(private_key)
        hash_obj = SHA256.new(msg)
        signature = pkcs1_15.new(private_key).sign(hash_obj)
        return True, base64.b64encode(signature).decode(charset)
    except Exception as ex:
        return False, str(ex)
```
- PHP：
```php
function sha_with_rsa_sign($data, $rsaPrivateKey, $alg = OPENSSL_ALGO_SHA256) {
    $key = "-----BEGIN PRIVATE KEY-----\n" . wordwrap($rsaPrivateKey, 64, "\n", true) . "\n-----END PRIVATE KEY-----";
    $signature = '';
    openssl_sign($data, $signature, $key, $alg);
    return base64_encode($signature);
}
```
- C#：
```csharp
public static string Sign(string signaturePrivateKey, string signatureData, string hashAlgorithm = "SHA256", string encoding = "UTF-8") {
    RSACryptoServiceProvider rsa = new RSACryptoServiceProvider();
    rsa.FromPrivateKeyJavaString(signaturePrivateKey);
    byte[] signatureBytes = rsa.SignData(Encoding.GetEncoding(encoding).GetBytes(signatureData), hashAlgorithm);
    return Convert.ToBase64String(signatureBytes);
}
```

TIP：可通过斗拱 CLI 工具的 `verify_sign` 命令验证签名结果。

### 如何验签

- 仅对 `data` 中的内容进行验签。
- 同步返参：对 `data` 的第一层参数按字典序排序后生成字符串再验签。
- 异步返参：无需进行排序，直接对 `data` 原文进行验签。

排序后的示例：
```json
{
  "auth_code": "134558771750600000",
  "devs_id": "TYXJL0623715525894234",
  "terminal_device_info": "{\"devs_id\":\"TYXJL0623715525894234\"}"
}
```

验签示例：
- Java：
```java
public static boolean verify(String data, String publicKeyBase64, String sign) {
    try {
        byte[] bytes = Base64.getDecoder().decode(publicKeyBase64);
        X509EncodedKeySpec keySpec = new X509EncodedKeySpec(bytes);
        KeyFactory keyFactory = KeyFactory.getInstance("RSA");
        PublicKey publicKey = keyFactory.generatePublic(keySpec);
        Signature signature = Signature.getInstance("SHA256WithRSA");
        signature.initVerify(publicKey);
        signature.update(data.getBytes("UTF-8"));
        return signature.verify(Base64.getDecoder().decode(sign));
    } catch (Exception e) {
        return false;
    }
}
```
- Python：
```python
def rsa_design(signature, message, my_rsa_public):
    try:
        my_rsa_public = fill_public_key_marker(my_rsa_public)
        message = message.encode("utf-8")
        public_key = RSA.importKey(my_rsa_public)
        hash_obj = SHA256.new(message)
        pkcs1_15.new(public_key).verify(hash_obj, base64.b64decode(signature))
        return True, ''
    except (ValueError, TypeError) as ex:
        return False, str(ex)
```
- PHP：
```php
function verifySign_sort($signature, $data, $rsaPublicKey, $alg = OPENSSL_ALGO_SHA256) {
    $key = "-----BEGIN PUBLIC KEY-----\n" . wordwrap($rsaPublicKey, 64, "\n", true) . "\n-----END PUBLIC KEY-----";
    ksort($data);
    return openssl_verify(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), base64_decode($signature), $key, $alg);
}
```
- C#：
```csharp
public static bool VerfySign(string publicKey, string signature, string content, string hashAlgorithm = "SHA256", string encoding = "UTF-8") {
    RSACryptoServiceProvider rsa = new RSACryptoServiceProvider();
    rsa.FromPublicKeyJavaString(publicKey);
    byte[] Data = Encoding.GetEncoding(encoding).GetBytes(content);
    byte[] rgbSignature = Convert.FromBase64String(signature);
    return rsa.VerifyData(Data, hashAlgorithm, rgbSignature);
}
```

## 接口加密解密说明

最近更新时间：2024.11.29

本文档针对部分接口指定参数的加解密（如银行卡号、手机号、身份证号、银行卡 CVV2、银行卡有效期 `valid_date`）进行说明。

### 商户端要求

- 加密：请求参数中的敏感信息需要加密，上送前使用汇付公钥进行加密。
- 解密：对于返回结果中携带的敏感信息，商户需要使用商户私钥进行解密。

### 加密算法

- 斗拱接口采用 RSA 加密。参考（Java）：
```java
byte[] decoded = Base64.getDecoder().decode(publicKey);
RSAPublicKey pubKey = (RSAPublicKey) KeyFactory.getInstance("RSA").generatePublic(new X509EncodedKeySpec(decoded));
Cipher cipher = Cipher.getInstance("RSA");
cipher.init(Cipher.ENCRYPT_MODE, pubKey);
encrypt = Base64.getEncoder().encodeToString(cipher.doFinal(content.getBytes(StandardCharsets.UTF_8)));
```

### 解密算法

- 斗拱接口采用 RSA 解密。参考（Java）：
```java
byte[] inputByte = Base64.getDecoder().decode(encryptContent.getBytes("UTF-8"));
byte[] decoded = Base64.getDecoder().decode(privateKey);
RSAPrivateKey priKey = (RSAPrivateKey) KeyFactory.getInstance("RSA").generatePrivate(new PKCS8EncodedKeySpec(decoded));
Cipher cipher = Cipher.getInstance("RSA");
cipher.init(Cipher.DECRYPT_MODE, priKey);
decrypt = new String(cipher.doFinal(inputByte));
```

### 加解密工具

- 可使用斗拱 SDK 提供的工具类（Java）：`com.huifu.bspay.sdk.opps.core.utils.RsaUtils`
  - 加密：`encrypt`
  - 解密：`decrypt`

本库映射（建议）：
- 在 PHP 侧建议提供 RSA 加/解密辅助（如 `Yourname\\DougongPay\\Tools\\RsaCrypto`）以封装 `openssl_public_encrypt` / `openssl_private_decrypt`；当前可直接使用 OpenSSL 函数完成等效能力。