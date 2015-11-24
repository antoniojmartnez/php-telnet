<?php

class Telnet {

    const READ_LENGTH = 100;
    const READ_WAIT = 8; //Segundos de espera a respuesta de comando (@see execute($sentence))
    
    function __construct($host, $port) {

        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if ($socket === false) {
            throw new Exception("socket_create() falló. Razón: " . socket_strerror(socket_last_error()) . "\n");
        }

        $conn_result = socket_connect($socket, $host, $port);
        if ($conn_result === false) {
            throw new Exception("socket_connect() falló. Razón: " . socket_strerror(socket_last_error($socket)) . "\n");
        }

        $nb_result = socket_set_nonblock($socket);
        if ($nb_result === false) {
            throw new Exception("socket_set_nonblock() falló. Razón: " . socket_strerror(socket_last_error($socket)) . "\n");
        }        
        
        $this->socket = $socket;
        $this->host = $host;
        $this->port = $port;
    }

    function close() {
        if (socket_close($this->socket) === FALSE)
        {
            throw new Exception("socket_close() falló: Razón: " . socket_strerror(socket_last_error()) . "\n");
        }
        else
        {
            return true;
        }
    }

    function status() {
        if (!$this->socket) {
            return $this->host . ":" . $this->port . " - Not connected";
        } else {
            return $this->host . ":" . $this->port . " - Connected";
        }
    }

    private function write($msg) {
        $data = trim($msg, "\n") . PHP_EOL;
        return socket_write($this->socket, $data, strlen($data));
    }

    private function read() {
//        $data = "";
//        while ($out = socket_read($this->socket, 1)) {
//            if ($out == "\n") {
//                break;
//            }
//            $data .= $out;
//        }
//        return $data;
        
        // Lee esa longitud de caracteres o bien hasta un retorno \n ó \r
        return socket_read($this->socket, self::READ_LENGTH);
    }
    
    private function waiting_response($seconds) {
        $read = array($this->socket);
        $write = NULL;
        $except = NULL;
        socket_select($read, $write, $except, $seconds);
    }
    
    function execute($sentence, $result_wait_time = self::READ_WAIT) {
        //Las condiciones son no estrictas (== en vez de ===) por casos detectados en que la comunicacion se ha roto
        //pero el socket no se ha enterado. En estos casos devuelve una cadena vacia en vez de False.
        if ($this->write($sentence) == FALSE) {
            throw new Exception("No se pudo enviar la sentencia: $sentence . " .  socket_strerror(socket_last_error()));
        }
     
        $this->waiting_response($result_wait_time);
        
        $result = $this->read();
        if ($result == FALSE)
        {
            throw new Exception("No se pudo recibir el resultado: $sentence . " .  socket_strerror(socket_last_error()));            
        }
//        echo "Buffer recibido:" . $result . PHP_EOL;
        return $result;
    }

}

?>