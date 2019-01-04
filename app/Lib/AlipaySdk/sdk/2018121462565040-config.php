<?php
/**
 * 正式 VIVO 小程序 配置文件
 */
return array(
    //应用ID,您的APPID。
    'app_id' => "2018121462565040",
    //商户私钥，您的原始格式私钥,一行字符串
    'merchant_private_key' => "MIIEowIBAAKCAQEAutjmU6vbkMokzmCrVMnsHc4eogUMKlWKpoLBEwO/5M9xeDOyVDuqw0lpw8daqOv35oo4JIMPDpKE818vt6wBY25c1HGZA7N2kj/EZou7n0qdc7G45zT5ivbxwaM8TULY2taz1hPd8gp/28xwwBizJAWHEqQjowT24XJZHc9ibTyXO3bM3Ffinghwb2kfXZsSGRos1KVn+5b9oQVVZDOFswp5eDMIzd2sms6mZFGGezxgjnxRwXnIPxLbuyi/OROQAQwLnQpcy58ud38RW9+LjzEFHYiAEMh5Tn1x0Yn1pGP1cKgCd90w5RHscB2Rv/FZYUYT5eKvR1fHvOOkeLFnyQIDAQABAoIBAHZWVxIZH1eFX2hB+2EY/e0mlWoh7kGFqempmGTVlBxzcbcibshAffdvMIgpY3bm9DvTwJkHVGrzSzbkS1F1o+94f1yhkbqxV+BqeJZF24+Ybz5OCgCNIZrsqdLs8o0wUC3Rm1ZzgLcCBVaNFb/kJNkkkxawVE20IDRK+rwRk5IAn05gjUSaNFo6q69oh8d3G2NZl+6qLZBAupTVeuX6/WGYNmgrjf/2xEd8MqJ3C8wUsPDrLg30EpfwKVFgLR+OZRTvGfuv/lILyoRWMgOwrodr0e1dpzfmVLitvWjTmboNTJfOBrsfAcGjr5PT6fmPyAranzQndB/LZ9JevS0BFQECgYEA6hxpbolIZmdP+UlnGthwuinFDab5KT6J2C7NTmMCILWHDESnX7t2Ik7OXENyjRUtCdYrSHmjnDnWnAF0RuwPmg5Y4WGHrc6cCSOUQ24ulGNuMhp6AQk1ZQwHLJ2ETQ0Zv4Es6pdpW74OzHq8Cbo6Rmv7zoxXbCVAHh65ukiESGECgYEAzFEzvuPhOm1Vt0kDmG0eVgUlxWgqfsGAqTIZiUZLumI9Ua3TRyhRyqkndZ/oYNRBB/+rTlBqPbvcSpIq2TIu3XK7j0QKW+9J4pZIYKnTEji3koWiWwBDMsIts46kodCcmaJ3yk/KzzJW5G53lPPym0Pfv9P61UFXL3aKFVaquGkCgYAjPHOB95e2EgavdqTWHY2Z9395NpB57LfCmgi8q5o+YhQZfJvUhNqa/1GAAYbURUOqH1oUZnqBoRL0GCPwKMeQYGhwQ8WwG1DQ71H5dDP+kZicYe/LCB/JPa42wN6Q2k/tyvt/s5pf/JMto6t5q1XqE4aq5+Sgmlq0Ldu2dgOzAQKBgQCYf/Nzg3tUtM7JowG//5Io+maa1YkCW0PBBdfxkjprv2+tS2TrM7j43xDIxrYXr9VqNvaR2Yuy0Ek4j6jTvJUmTMCZyltBC3XGXg1fuOIGM7cw8fWgnq2JcU3TO6C99ossUQvAQZZK2HPqxFkVII/wO9UBxSLvkXmVv5CJJMsAIQKBgFE7FUVjmbqyE1frXjh+VXK9Ij5AWRNLF6/eoYC6AktNobmQRJ3HRpQ1yEvCQFStD6G8lNf7w3WLF680zdvkUA4yGAfYbry+N9lsmKcdMUUOvllfDolZmrbBIorJbAH5MUnwD2IwozBj/+rXdX+6pTI2cjQqiU8nWlQYLQ8/dwQQ",
    //商户应用公钥,一行字符串
    'merchant_public_key' => "MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAutjmU6vbkMokzmCrVMnsHc4eogUMKlWKpoLBEwO/5M9xeDOyVDuqw0lpw8daqOv35oo4JIMPDpKE818vt6wBY25c1HGZA7N2kj/EZou7n0qdc7G45zT5ivbxwaM8TULY2taz1hPd8gp/28xwwBizJAWHEqQjowT24XJZHc9ibTyXO3bM3Ffinghwb2kfXZsSGRos1KVn+5b9oQVVZDOFswp5eDMIzd2sms6mZFGGezxgjnxRwXnIPxLbuyi/OROQAQwLnQpcy58ud38RW9+LjzEFHYiAEMh5Tn1x0Yn1pGP1cKgCd90w5RHscB2Rv/FZYUYT5eKvR1fHvOOkeLFnyQIDAQAB",
    //支付宝公钥,查看地址：https://openhome.alipay.com/platform/keyManage.htm 对应APPID下的支付宝公钥。
    'alipay_public_key' => "MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAm2YIBllOQOoFtHYgkT2efHIRJFbho9++we4XXJnBS9F85whRTdZeMhc2UaFgt9MkvE+AOysNTgHh3heOccUrV5lebxIQyDsrp5mb3d/rGUorI0jcMW20P3oRljpjh6xnTGLMGdgjDJYJjN50hZXsHbMtJVfcq6le2MwjG8Q3fQH0yMaUsFyg2g9bjsA5qRpQQ1drAXSqVeLdOIkbEC3O2OV3ZQv0eSZe0SRyHPiq5ZUpRLkxNumK/DkzQWzr9zZMGqgVWZpMWttmMSXa16r7NZA6FJmKV2K4/MvRywUhVbYeuzyCMUkpGxSyf0RhDfKx6Leme2L08/cXn/v6BLjXewIDAQAB",
    //编码格式
    'charset' => "UTF-8",
    //支付宝网关
    'gatewayUrl' => "https://openapi.alipay.com/gateway.do",
    //签名方式
    'sign_type' => "RSA2",
    // debug输出开启
    'debug_info' => false,
);
