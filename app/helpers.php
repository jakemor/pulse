<?php

/*
	HELPER FUNCTIONS - Add as you need
*/

function _chirpUser($owner_id, $other_phone_number, $message, $lat, $lon) {
	
	$owner_username = _getUserDisplayName($owner_id); 
	$pulse = new Pulse(); 
		$pulse->owner_id = $owner_id; 
		$pulse->other_phone_number = $other_phone_number; 
		$pulse->message = $message;
		$pulse->lat = $lat;
		$pulse->lon = $lon;
	 

	if (_userExists("phone_number", $other_phone_number)) {
		$other_id = _getUser("phone_number", $other_phone_number)->id;
		$pulse->other_id = $other_id; 
		$other_username = _getUserDisplayName($other_id); 
		_newNotification($owner_id, $other_id, 2, "You chirped @{$other_username}"); 
		_newNotification($other_id, $owner_id, 1, "{$owner_username}");
	} else {
		//$texted = _textPhoneNumber($other_phone_number, "Your friend {$owner_username} chirped at you! Download the app to chirp back at them. getchirp.com"); 
		$texted = _textPhoneNumber($other_phone_number, "Your friend chirped at you! Download the app to chirp back at them. getchirp.com"); 
		
		if ($texted) {
			_newNotification($owner_id, $other_phone_number, 2, "You chirped @{$other_phone_number}"); 
		} else {
			_newNotification($owner_id, $other_phone_number, 2, "{$other_phone_number} is not a valid phone number");
		}
	}

	$pulse->save();

	_respond("pulseUser", $pulse);
}

function _newNotification($owner_id, $other_phone_number, $type, $message) {
	$notification = new Notification(); 
		$notification->owner_id = $owner_id; 
		$notification->other_phone_number = $other_phone_number; 
		$notification->message = $message; 
		$notification->type = $type; 
	$notification->save(); 
}

function _userExists($id, $value) {
	$user = new User(); 
	return $user->get($id, $value); 
}

function _contactExists($phone) {
	$contact = new Contact(); 
	return $contact->get("phone_number", $phone); 
}

function _getUser($id, $value) {
	if (_userExists($id, $value)) {
		$user = new User(); 
		$user->get($id, $value); 
		return $user; 
	} else {
		return NULL;
	}
}

function _getUserDisplayName($user_id) {
	$user = _getUser("id", $user_id); 

	if (is_null($user)) {
		return "";
	}

	return $user->first_name . " " . $user->last_name;
}

function _textPhoneNumber($phone_number, $message) {
    require "Twilio/Services/Twilio.php";
    $AccountSid = "ACbd652dd257ef5f7fdbf246a6e7af8d3a";
    $AuthToken = "e22f767658650152da61ff7dc93ad57e";
    $client = new Services_Twilio($AccountSid, $AuthToken);
	try {
	    $sms = $client->account->messages->sendMessage(
	        "516-210-4617", 
	        $phone_number,
	        $message
	    );
	    return TRUE; 
	} catch (Exception $e) {
   		return FALSE; 
	}
}

function _getUserId($id, $value) {
	$user = _getUser($id, $value);
	if (is_null($user)) {
		return NULL; 
	}
	return $user->id; 
}

function _getUserByPhoneNumber($phone_number) {
	// extra line so i can fold
	return _getUser("phone_number", $phone_number); 
}

function _validate($endpoints) {
	for ($i=0; $i < sizeof($endpoints); $i++) { 

		$attribute = $endpoints[$i]; 

		if (isset($_GET[$attribute]) && trim($_GET[$attribute]) != "") {
			$_GET[$attribute] = trim($_GET[$attribute]); 

			// if ($attribute == "email" or $attribute == "first_name" or $attribute == "last_name") {
			// 	$_GET[$attribute] = strtolower($_GET[$attribute]);
			// }

		} else {
			_respondWithError("missing parameters","Missing " . $endpoints[$i]);
			return False; 
		}
	}

	return True; 
}

function _log($endpoint, $response) {
	$log = new Log();
	$log->endpoint = $endpoint; 
	$log->response = $response["error_description"]; 
	$log->get_params = json_encode($_GET); 
	$log->post_params = json_encode($_POST); 
	$log->save(); 
}

function _validateWithoutError($endpoints) {
	for ($i=0; $i < sizeof($endpoints); $i++) { 

		$attribute = $endpoints[$i]; 

		if (isset($_GET[$attribute]) && trim($_GET[$attribute]) != "") {
			$_GET[$attribute] = trim($_GET[$attribute]); 
			// if ($attribute == "email" or $attribute == "first_name" or $attribute == "last_name") {
			// 	$_GET[$attribute] = strtolower($_GET[$attribute]);
			// }
		} else {
			return False; 
		}
	}

	return True; 
}

function _respond($endpoint, $input) {
	$array = [];
	$array["error"] = False;
	$array["error_description"] = ""; 
	$array["message"] = False;
	$array["message_description"] = ""; 
	$array["data"] = $input; 
	echo json_encode($array);
	//_log($endpoint, "success"); 
}

function _respondWithMessage($endpoint, $input, $message) {
	$array = [];
	$array["error"] = False;
	$array["error_description"] = ""; 
	$array["message"] = True;
	$array["message_description"] = $message; 
	$array["data"] = $input; 
	echo json_encode($array); 
	//_log($endpoint, "success from _respondWithMessage"); 
}

function _respondWithError($endpoint, $message) {
	$array = [];
	$array["error"] = True; 
	$array["error_description"] = $message;
	$array["message"] = False; 
	$array["message_description"] = ""; 
	$array["data"] = []; 
	echo json_encode($array);
	//_log($endpoint, $array);  
}

function _hash($string) {
	//
	return hash('sha256', $string);
}


?>