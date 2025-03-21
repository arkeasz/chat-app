<?php

/**
 * unmask is a function that takes a string of text and returns the unmasked text
 * @param string $text
 * @return string
 */
function unmask($text) {
    $length = ord($text[1]) & 127;

    if ($length == 126) {
        $masks = substr($text, 4, 4);
        $data = substr($text, 8);
    } elseif ($length == 127) {
        $masks = substr($text, 10, 4);
        $data = substr($text, 14);
    } else {
        $masks = substr($text, 2, 4);
        $data = substr($text, 6);
    }

    $text = '';
    for ($i = 0; $i < strlen($data); ++$i) {
        $text .= $data[$i] ^ $masks[$i % 4];
    }

    return $text;
}

function pack_data($text) {
    /**
     * $b1 is the first byte (8 bits), which represents the FIN, RSV1, RSV2, RSV3 (1 bit each), and opcode (4 bits)
     * hexadeciaml values
     * 0x80 = 128 (decimal) = 10000000 (binary)
     * 0x1 = 1 (decimal) = 00000001 (binary)
     * 0x0f = 15 (decimal) = 00001111 (binary)
     * therefore
     * $b1 = 0x80 | (0x1 & 0x0f) = 10000001 (binary) = 129 (decimal)
     * the last 1 bit (0x1) indiccates its a text frame
     */
    $b1 = 0x80 | (0x1 & 0x0f);
    $length = strlen($text);

    if ($length <= 125) {
        $header = pack('CC', $b1, $length);
    } elseif ($length > 125 && $length < 65536) {
        $header = pack('CCn', $b1, 126, $length);
    } elseif ($length >= 65536) {
        $header = pack('CCNN', $b1, 127, $length);
    }
    
    return $header.$text;
}

function handshake($request_header, $sock, $address, $port) {
    $headers = [];
    $lines = preg_split("/\r\n/", $request_header);

    foreach ($lines as $line) {
        $line = chop($line);
        if (preg_match('/\A(\S+): (.*)\z/', $line, $matches)) {
            $headers[$matches[1]] = $matches[2];
        }
    }

    $sec_key = $headers['Sec-WebSocket-Key'];
    $sec_accept = base64_encode(sha1($sec_key.'258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));
    $response_header = "HTTP/1.1 101 Switching Protocols\r\n";
    $response_header .= "Upgrade: websocket\r\n";
    $response_header .= "Connection: Upgrade\r\n";
    $response_header .= "Sec-WebSocket-Accept: $sec_accept\r\n\r\n";

    socket_write($sock, $response_header, strlen($response_header));
}
