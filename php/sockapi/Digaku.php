<?php
/**
 * Copyright (C) 2011 Ansvia.
 * Digaku sockapi client driver for PHP.
 * File:           Digaku.php
 * First writter:  robin <robin [at] digaku [dot] kom>
 */

require_once(dirname(__FILE__) . '/Coder.php');

class Digaku
{
	/**
	 * @constructor
	 * @param $host -- {String} sockapi host name.
	 * @param $port -- {Int} sockapi port number.
	 * @param $msgpack_driver -- {String} msgpack driver, can be `php-msgpack`
	 *    						  for use native C driver (faster),
	 *    						  or `msgpack-php` for use pure PHP driver (slower).
	 */
	function __construct( $host, $port, $msgpack_driver, $debug_mode=false)
	{
		$this->host = $host;
		$this->port = $port;
		
		$this->msgpacklib = $msgpack_driver;
		try{
			$this->connect();
		}catch(Exception $e){
			echo "Digaku SockAPI connection failed.";
			if($this->debug_mode == true){
				echo '<pre>';
				echo 'Using host: ' . $this->host . ', port: ' . $this->port . '\n';
				print_r($e);
				echo '</pre>';
			}
			die();
		}
	}

	function connect() {
		$sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		if ($sock === null) {
			// Connection errors are stored in the 'null' last error.
			throw new DigakuSockAPIClientError(socket_strerror(socket_last_error()));
		}
		if (!@socket_connect($sock, $this->host, $this->port)) {
			// Clean up and return NULL
			$err = socket_last_error();
			@socket_close($sock);
			throw new DigakuSockAPIClientError(socket_strerror($err));
		}
		socket_set_block($sock);
		$this->sock = $sock;
	}

	function _write($data) {
		while (strlen($data)) {
			$len = @socket_write($this->sock, $data, strlen($data));
			if ($len === false) {
				throw new DigakuSockAPIClientError(socket_strerror(socket_last_error($this->sock)));
			}
			$data = substr($data, $len);
		}
	}

	function _read($nbytes) {
		$data = '';
		while ($nbytes) {
			if (false === ($nr = @socket_recv($this->sock, $chunk, $nbytes-strlen($data), MSG_WAITALL))) {
				throw new DigakuSockAPIClientError(socket_strerror(socket_last_error($this->sock)));
			}
			/*$chunk = @socket_read($this->sock, $nbytes-strlen($data), PHP_BINARY_READ);
			if ($chunk === false) {
				throw new DigakuSockAPIClientError(socket_strerror(socket_last_error($this->sock)));
			}*/
			$nbytes -= strlen($chunk);
			$data .= $chunk;
		}
		return $data;
	}

	function close() {
		if (!$this->sock) {
			return;
		}
		socket_clear_error($this->sock);
		@socket_shutdown($this->sock, 2);
		$err = socket_last_error($this->sock);
		if ($err) {
			throw new DigakuSockAPIClientError(socket_strerror($err));
		}

		// It is impossible to get errors from a socket_close, we will let
		// error_reporting report it.
		socket_close($this->sock);
		$err = socket_last_error();
		if ($err) {
			throw new DigakuSockAPIClientError(socket_strerror($err));
		}
		$this->sock = null;
	}

	function call($command, $params=array()) {
		if (count($params) == 1) {
			$params[] = '';
		}
		if ($this->msgpacklib == 'php-msgpack') {
			$ped = msgpack_serialize($params);
		} else {
			$ped = MsgPack_Coder::encode($params);
		}
		$clen = strlen($command) + strlen($ped);

		$data = pack('NN', 12345, $clen);
		$data = $data . $command . $ped;
		$this->_write($data);

		list(, $msid) = unpack('N', $this->_read(4));
		list(, $rlen) = unpack('N', $this->_read(4));
		$resp = $this->_read($rlen);
		$rv = null;
		if ($this->msgpacklib == 'php-msgpack') {
			$rv = msgpack_unserialize($resp);
		} else {
			$rv = MsgPack_Coder::decode($resp);
		}

		return $rv;
	}
}


class DigakuSockAPIClientError extends Exception {
}

class DigakuSockAPIClientRemoteError extends Exception {
	var $error_code;
	var $description;

	function __construct($response) {
		$this->error_code = $response['_error_code'];
		$this->description = $response['_error_description'];
		Exception::__construct($this->code.': '.$this->description);
	}
}