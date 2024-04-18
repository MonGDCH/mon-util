<?php

use mon\util\HttpRequest;
use mon\util\HttpResponse;

require __DIR__ . '/../vendor/autoload.php';

$req_buff = 'GET /ss/?d=123 HTTP/1.1
Host: 127.0.0.1:8014
Connection: Upgrade
Pragma: no-cache
Cache-Control: no-cache
User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36 Edg/123.0.0.0
Upgrade: websocket
Origin: https://wstool.js.org
Sec-WebSocket-Version: 13
Accept-Encoding: gzip, deflate, br, zstd
Accept-Language: zh-CN,zh;q=0.9,en;q=0.8,en-GB;q=0.7,en-US;q=0.6
Sec-WebSocket-Key: 0xuYx63SV0ULdsxWESW/bA==
Sec-WebSocket-Extensions: permessage-deflate; client_max_window_bits

';

$rsp_buff = "HTTP/1.1 200 OK\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Length: 12\r\nServer: Apache\r\n\r\nHello World!";


$response = new HttpResponse($rsp_buff);

dd($response->body());

// $request = new HttpRequest($req_buff);

// dd($request->get());
// dd($request->post());
// dd($request->method());
// dd($request->header());
// dd($request->cookie());
// dd($request->path());
// dd($request->protocolVersion());
