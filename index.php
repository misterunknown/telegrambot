<?php
require_once( "lib/telegrambot.php" );

class MyBot extends SimpleTelegram\TelegramBot {
	public function comm_ping( $params, $message ) {
		$this->sendMessage( $message->chat->id, "pong" );
	}
	public function comm_date( $params, $message ) {
		$this->sendMessage( $message->chat->id, date( DateTime::COOKIE ) );
	}
	public function comm_help( $params, $message ) {
		$this->sendMessage( $message->chat->id, print_r( $params, true ) );
	}
}

$mybot = new MyBot( array(
	"token" => "123456789:ABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHI", // set your token
	"loglevel" => 10
));

$mybot->run();
