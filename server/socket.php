<?php
// include 'functions.php', which contains auxiliary functions
include 'functions.php';

/**
 * define the address and port of the server
 */
$address = '0.0.0.0';
$port = 8020;

$null = NULL;

// create a socket of type AF_INET (IPv4), SOCK_STREAM (TCP)

/*
 * AF_INET, is the address family that is used to designate the type of addresses that the socket can communicate with
 * SOCK_STREAM, is the type of socket that is used to create a connection-based socket
 * SOL_TCP, is the protocol that is used to create the socket
*/

// SOL_TCP indicates the protocol TCP instead of UDP
$sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

// asociate the socket to the address and port
socket_bind($sock, $address, $port);

// listen for incoming connections
socket_listen($sock);

/* At this point the server is active and listening for incoming connections */


echo "Listening on $address:$port\n";

// an array to store the members of the chat
$members = [];

// list of all the sockets that are connected to the server, including the main socket
$connections = [];
$connections[] = $sock;

// the server will run until it is stopped
while (true) {
    // read if there are any data disponible in the sockets
    $reads = $connections;

    // write if there is space in the buffer to write
    $writes = $exceptions = $null;

    // wait until any socket in $connections is ready to be read
    socket_select($reads, $writes, $exceptions, 0);

    /*
        * if a socket is in $reads, it means that a new client is trying to connect
    */
    if(in_array($sock, $reads)) {
        // accept the new connection and then add it to the list of $new_connections
        $new_connection = socket_accept($sock);

        // read the header of the request
        $header = socket_read($new_connection, 1024);
        // perform the handshake
        handshake($header, $new_connection, $address, $port);

        // add the new connection to the list of connections
        $connections[] = $new_connection;

        // send a message to the new client
        $reply = [
            'type' => 'join',
            'sender' => 'server',
            'text' => 'enter the name to join'
        ];
        $reply = json_encode($reply);
        $reply = pack_data($reply);

        socket_write($new_connection, $reply, strlen($reply));

        $sock_index = array_search($sock, $reads);
        unset($reads[$sock_index]);
    }
    // for each socket in $reads, try to read until 1024 bytes
    foreach ($reads as $key => $value) {
        // if socket_read return an empty string, it means that the client has disconnected
        $data = socket_read($value, 1024);

        if (!empty($data)) {
            // data contains the message sent by the client
            // write to all connected clients
            // unmask the data and store it in $message
            $message = unmask($data);
            $decode_message = json_decode($message, true);

            if ($decode_message) {
                if (isset($decode_message["text"])) {
                    if ($decode_message["type"] === "join") {
                        $members[$key] = [
                            "name" => $decode_message['sender'],
                            "connection" => $value
                        ];
                    }
                    // pack the data to send it to the clients
                    $masked_message = pack_data($message);
                    // if the client send data, these data are sent to all the other connected clients
                    foreach ($members as $mkey => $mvalue) {
                        // if the socket is the main socket, we skip it
                        if ($mkey === 0) continue;
                        socket_write($mvalue["connection"], $masked_message, strlen($masked_message));
                    }
                }
            }

        } else if ($data === '') {
            echo "disconnecting client $key \n";
            // in this case, we remove the client from the list of connections
            unset($connections[$key]);

            if (array_key_exists($key, $members)) {
                $message = [
                    "type" => "left",
                    "sender" => "server",
                    "text" => $members[$key]["name"] . " has left the chat"
                ];
                $masked_message = pack_data(json_encode($message));
                unset($members[$key]);
                foreach ($members as $mkey => $mvalue) {
                    socket_write($mvalue["connection"], $masked_message, strlen($masked_message));
                }
            }
            // and close the socket
            socket_close($value);
        }
    }
}
