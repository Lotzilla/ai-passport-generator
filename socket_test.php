<?php
$sock = @stream_socket_server('tcp://127.0.0.1:8085', $errno, $errstr);
if ($sock) {
    fclose($sock);
    echo "Socket OK - PHP can bind to ports\n";
} else {
    echo "Socket FAILED: [{$errno}] {$errstr}\n";
}
