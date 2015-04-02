<?php

require './jakephp/Model.php';

class User extends Model {
	public $phone_number;
	public $first_name;
	public $last_name;
	public $username;
	public $password;
	public $profile_pic_url;
	public $twitter_id;
	public $facebook_id;
	public $misc_0;
	public $misc_1;
	public $misc_2;
	public $misc_3;
	public $misc_4;
	public $misc_5;
	public $misc_6;
	public $misc_7;
	public $misc_8;
	public $misc_9;
}

class Friend extends Model {
	public $owner_id;
	public $friend_phone_number;
	public $accepted_at; 
	public $misc_0;
	public $misc_1;
	public $misc_2;
	public $misc_3;
	public $misc_4;
}

class Notification extends Model {
	public $owner_id;
	public $other_phone_number;
	public $type; // 0 = generic | 1 = you received a pulse | 2 = you sent a pulse | 3 = you friended someone | 4 = someone friended you | 5 = you became friends with someone
	public $message;
	public $action;
	public $viewed_at;
	public $callback_url;
}

class Pulse extends Model {
	public $owner_id; 
	public $other_id;
	public $other_phone_number;
	public $message;
	public $lat;
	public $lon;
	public $radius;
	public $viewed_at;
	public $misc_0;
	public $misc_1;
	public $misc_2;
	public $misc_3;
	public $misc_4;
	public $misc_5;
	public $misc_6;
	public $misc_7;
	public $misc_8;
	public $misc_9;
}

class CheckIn extends Model { 
	public $phone_number; 
	public $owner_id; 
	public $lat; 
	public $lon; 
	public $misc_0;
	public $misc_1;
	public $misc_2;
	public $misc_3;
	public $misc_4;
	public $misc_5;
	public $misc_6;
	public $misc_7;
	public $misc_8;
	public $misc_9;
}

class Contact extends Model {
	public $phone_number;
	public $first_name;
	public $last_name;
	public $owner_id;
	public $misc_0;
	public $misc_1;
	public $misc_2;
	public $misc_3;
	public $misc_4;
	public $misc_5;
	public $misc_6;
	public $misc_7;
	public $misc_8;
	public $misc_9;
}

class Log extends Model {
	public $endpoint;
	public $response;
	public $get_params;
	public $post_params;
	public $misc_0;
	public $misc_1;
	public $misc_2;
	public $misc_3;
	public $misc_4;
	public $misc_5;
	public $misc_6;
	public $misc_7;
	public $misc_8;
	public $misc_9;
}

?>