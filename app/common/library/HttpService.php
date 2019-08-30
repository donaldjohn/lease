<?php
namespace app\common\library;

class HttpService
{
    const GET       = "GET";
    const PUT       = "PUT";
    const POST      = "POST";
    const DELETE    = "DELETE";
    const PATCH     = "PATCH";
    const HEAD      = "HEAD";
    const OPTIONS   = "OPTIONS";
    const PURGE     = "PURGE";

    const STATUS_UNKNOWN = [0, "Unknown"];

    // INFORMATIONAL CODES
    const STATUS_100 = [100, "Continue"];                        // RFC 7231, 6.2.1
    const STATUS_101 = [101, "Switching Protocols"];             // RFC 7231, 6.2.2
    const STATUS_102 = [102, "Processing"];                      // RFC 2518, 10.1

    // SUCCESS CODES
    const STATUS_200 = [200, "OK"];                              // RFC 7231, 6.3.1
    const STATUS_201 = [201, "Created"];                         // RFC 7231, 6.3.2
    const STATUS_202 = [202, "Accepted"];                        // RFC 7231, 6.3.3
    const STATUS_203 = [203, "Non-Authoritative Information"];   // RFC 7231, 6.3.4
    const STATUS_204 = [204, "No Content"];                      // RFC 7231, 6.3.5
    const STATUS_205 = [205, "Reset Content"];                   // RFC 7231, 6.3.6
    const STATUS_206 = [206, "Partial Content"];                 // RFC 7233, 4.1
    const STATUS_207 = [207, "Multi-status"];                    // RFC 4918, 11.1
    const STATUS_208 = [208, "Already Reported"];                // RFC 5842, 7.1
    const STATUS_226 = [226, "IM Used"];                         // RFC 3229, 10.4.1

    // REDIRECTION CODES
    const STATUS_300 = [300, "Multiple Choices"];                // RFC 7231, 6.4.1
    const STATUS_301 = [301, "Moved Permanently"];               // RFC 7231, 6.4.2
    const STATUS_302 = [302, "Found"];                           // RFC 7231, 6.4.3
    const STATUS_303 = [303, "See Other"];                       // RFC 7231, 6.4.4
    const STATUS_304 = [304, "Not Modified"];                    // RFC 7232, 4.1
    const STATUS_305 = [305, "Use Proxy"];                       // RFC 7231, 6.4.5
    const STATUS_306 = [306, "Switch Proxy"];                    // RFC 7231, 6.4.6 (Deprecated)
    const STATUS_307 = [307, "Temporary Redirect"];              // RFC 7231, 6.4.7
    const STATUS_308 = [308, "Permanent Redirect"];              // RFC 7538, 3

    // CLIENT ERROR
    const STATUS_400 = [400, "Bad Request"];                     // RFC 7231, 6.5.1
    const STATUS_401 = [401, "Unauthorized"];                    // RFC 7235, 3.1
    const STATUS_402 = [402, "Payment Required"];                // RFC 7231, 6.5.2
    const STATUS_403 = [403, "Forbidden"];                       // RFC 7231, 6.5.3
    const STATUS_404 = [404, "Not Found"];                       // RFC 7231, 6.5.4
    const STATUS_405 = [405, "Method Not Allowed"];              // RFC 7231, 6.5.5
    const STATUS_406 = [406, "Not Acceptable"];                  // RFC 7231, 6.5.6
    const STATUS_407 = [407, "Proxy Authentication Required"];   // RFC 7235, 3.2
    const STATUS_408 = [408, "Request Time-out"];                // RFC 7231, 6.5.7
    const STATUS_409 = [409, "Conflict"];                        // RFC 7231, 6.5.8
    const STATUS_410 = [410, "Gone"];                            // RFC 7231, 6.5.9
    const STATUS_411 = [411, "Length Required"];                 // RFC 7231, 6.5.10
    const STATUS_412 = [412, "Precondition Failed"];             // RFC 7232, 4.2
    const STATUS_413 = [413, "Request Entity Too Large"];        // RFC 7231, 6.5.11
    const STATUS_414 = [414, "Request-URI Too Large"];           // RFC 7231, 6.5.12
    const STATUS_415 = [415, "Unsupported Media Type"];          // RFC 7231, 6.5.13
    const STATUS_416 = [416, "Requested range not satisfiable"]; // RFC 7233, 4.4
    const STATUS_417 = [417, "Expectation Failed"];              // RFC 7231, 6.5.14
    const STATUS_418 = [418, "I'm a teapot"];                    // RFC 7168, 2.3.3
    const STATUS_421 = [421, "Misdirected Request"];
    const STATUS_422 = [422, "Unprocessable Entity"];            // RFC 4918, 11.2
    const STATUS_423 = [423, "Locked"];                          // RFC 4918, 11.3
    const STATUS_424 = [424, "Failed Dependency"];               // RFC 4918, 11.4
    const STATUS_425 = [425, "Unordered Collection"];
    const STATUS_426 = [426, "Upgrade Required"];                // RFC 7231, 6.5.15
    const STATUS_428 = [428, "Precondition Required"];           // RFC 6585, 3
    const STATUS_429 = [429, "Too Many Requests"];               // RFC 6585, 4
    const STATUS_431 = [431, "Request Header Fields Too Large"]; // RFC 6585, 5
    const STATUS_451 = [451, "Unavailable For Legal Reasons"];   // RFC 7725, 3
    const STATUS_499 = [499, "Client Closed Request"];

    // SERVER ERROR
    const STATUS_500 = [500, "Internal Server Error"];           // RFC 7231, 6.6.1
    const STATUS_501 = [501, "Not Implemented"];                 // RFC 7231, 6.6.2
    const STATUS_502 = [502, "Bad Gateway"];                     // RFC 7231, 6.6.3
    const STATUS_503 = [503, "Service Unavailable"];             // RFC 7231, 6.6.4
    const STATUS_504 = [504, "Gateway Time-out"];                // RFC 7231, 6.6.5
    const STATUS_505 = [505, "HTTP Version not supported"];      // RFC 7231, 6.6.6
    const STATUS_506 = [506, "Variant Also Negotiates"];         // RFC 2295, 8.1
    const STATUS_507 = [507, "Insufficient Storage"];            // RFC 4918, 11.5
    const STATUS_508 = [508, "Loop Detected"];                   // RFC 5842, 7.2
    const STATUS_510 = [510, "Not Extended"];                    // RFC 2774, 7
    const STATUS_511 = [511, "Network Authentication Required"];  // RFC 6585, 6



    //自定义 code
    // Auth  10000 - 10999
    const AuthHeaderInvalid         = [10001, "非法头部请求"];
    const AuthTokenInvalid          = [10002, "非法请求"];
    const AuthTokenExpired          = [10003, "登入超时,请重新登入!"];


    const CURL_TIME_OUT             = [500, "curl time out"];
    const MICRO_ERROR             = [500, "微服务报错"];
    const DATA_ERROR             = [500, "数据格式返回错误"];


    public function getHttpCode($code)
    {
        $status_code = 'STATUS_'.$code;
        $result = (isset($this->$status_code)) ? $this->$status_code : [$code,'Unknown Status Code'];
        return $result;
    }


    public static function format(array $error, $args = null)
    {
        if ($args === null)
            return $error;

        if (is_array($args)) {
            for ($i = 0; $i < count($args); $i++) {
                $error[1] = str_replace('{'. ($i+1) . '}', $args[$i], $error[1]);
            }
            return $error;
        }

        $error[1] = str_replace('{1}', $args, $error[1]);
        return $error;
    }
}