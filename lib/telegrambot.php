<?php

namespace SimpleTelegram;
/*
   This is a super simple abstract telegram bot class. It helps creating some simple commands.
*/
abstract class TelegramBot {
	protected $loglevel = 0; // 0->Only Errors, 1->Warnings, 2->Notices, 3->Info, 4->Debug
	protected $logfile = "log.txt";
	protected $token;

	/*
	   This creates the bot. Current available options:
	   		token 		-> mandatory bot id
			logfile 	-> file to take logs. if you test your bot with curl, you may want to set it to php://stdout
			loglevel	-> int between 0 (only errors) and 4 (debug)
	*/
	public function __construct( array $options ) {
		if( ! is_array( $options ) || ! array_key_exists( "token", $options ) ) {
			$this->log( "No token was given. This is mandatory." );
			http_response_code( 510 );
			exit();
		}
		else {
			foreach( $options as $key => $value ) {
				if( property_exists( $this, $key ) ) {
					$this->$key = $value;
				}
			}
		}
	}

	/*
	   Log some stuff
	*/
	protected function log( $s, $severity=2 ) {
		if( $severity <= $this->loglevel ) 
			file_put_contents( $this->logfile, "TelegramBot: " . $severity . ":  " . print_r( $s, true ) . "\n", FILE_APPEND );
	}

	/*
	   Parse the command
	*/
	private function parseCommand( $s ) {
		$quoted = false;
		$escape = false;
		$part = 0;
		$result = array("");
		$lastChar = "";
		for( $i = 0; $i < strlen( $s ); $i++ ) {
			$char = $s[$i];
			switch( $char ) {
				case " ":
					if( $quoted || $escape ) {
						$result[$part] .= $char;
						$escape = false;
					} else {
						$part++;
					}
					break;
				case "\\":
					if( $escape ) {
						$result[$part] .= "\\";
						$escape = false;
					} else {
						$escape = true;
					}
					break;
				case "\"":
					if( $escape ) { $result[$part] .= $char; $escape = false; } else { $quoted = ( $quoted ? false : true ); }
					$escape = false;
					break;
				default: 
					$result[$part] .= $char;
					$escape = false;
					break;
			}
		}
		return $result;
	}

	/*
	   send a message
	*/
	protected function sendMessage( $chat_id, $text ) {
		$this->log( "Send message. Token: " . $this->token . "; Chat-ID: " . $chat_id, 3 );
		$this->log( "Message content: " . $text, 4 );
		$data = array( "chat_id" => $chat_id, "text" => $text );

		// Send request
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL,"https://api.telegram.org/bot".$this->token."/sendMessage" );
		curl_setopt( $ch, CURLOPT_POST, 1 );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( $data ) );
		curl_setopt( $ch, CURLOPT_HEADER, true );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		$server_output = curl_exec( $ch );
		$status = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
		$this->log( "Message sent. Statuscode: " . $status . "; Server output: " . $server_output, 4 );
		curl_close( $ch );

		return ( $status == 200 ) ? true : false;
	}

	/*
	   handle the request
	*/
	public function run() {
		$data = json_decode( file_get_contents( 'php://input' ) );
		$this->log( "Request received.", 3 );
		$this->log( "Request data: " . print_r( $data, true ) . "\n----", 4 );
		if( property_exists( $data, 'message') && $data->message != NULL ) {
			$this->log( "Message exists in request.", 4 );
			if( $data->message->text[0] == '/' ) {
				$this->log( "Message contains a command.", 3);
				$params = $this->parseCommand( substr( $data->message->text, 1 ) );
				$command = array_shift( $params );
				$command = 'comm_' . $command;

				if( is_callable( array( $this, $command ) ) ) {
					$this->log( "Command " . $command . " is callable, so I try to run it.", 4 );
					$this->$command( $params, $data->message );
				} else {
					$this->log( "Could not find command " . $command, 1 );
					$this->sendMessage( $data->message->chat->id, "Command is not implemented yet.", 1 );
				}
			} else {
				$this->log( "Message does not contain a command.", 3 );
			}
		} else {
			$this->log( "No message exists in request.", 2 );
		}
	}
}
