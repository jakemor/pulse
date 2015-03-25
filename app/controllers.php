<?php
include "./settings.php";

// optionally include some usefull functions
include "helpers.php";

// API Endpoints

function add() {
	echo "THIS IS A TEST";
}

function github_push() {
	echo shell_exec("git pull");
}

function verifyPhone() {
	$endpoint = "verifyPhone"; 
	if (_validate(["phone_number"])) {
		if (strlen($_GET["phone_number"]) == 10) {
		    require "Twilio/Services/Twilio.php";
		    $AccountSid = "ACbd652dd257ef5f7fdbf246a6e7af8d3a";
		    $AuthToken = "e22f767658650152da61ff7dc93ad57e";
		    $random = "" . rand(0,9) . rand(0,9) . rand(0,9) . rand(0,9); 
		    $client = new Services_Twilio($AccountSid, $AuthToken);
		    $sms = $client->account->messages->sendMessage(
		        "516-210-4617", 
		        $_GET["phone_number"],
		        "Hey! Here's your Pulse pin: {$random}. Happy Pulsing :)"
		    );
		    _respond($endpoint, $random); 
		} else {
			_respondWithError($endpoint, "Please enter a valid 10 digit phone number."); 
		}
	}
}

function createUser() {
	$endpoint = "createUser"; 
	if (_validate(["phone_number", "first_name", "last_name", "username"])) {
		$user = new User(); 
		$found = $user->get("phone_number", $_GET["phone_number"]); 
		if ($found === False) {

			if (_userExists("username", strtolower($_GET["username"]))) {
				_respondWithError($endpoint, "That username already exists.");
				return;
			}

			$user->phone_number = $_GET["phone_number"];
			$user->first_name = ucfirst(strtolower($_GET["first_name"]));
			$user->last_name = ucfirst(strtolower($_GET["last_name"]));
			$user->username = strtolower($_GET["username"]);
			$user->save();
			_respond($endpoint, $user); 
		} else {
			_respondWithError($endpoint, "An account with that phone number already exists.");
		}
		
	}
}

function logIn() {
	$endpoint = "logIn"; 
	if (_validate(["phone_number"])) {
		if (_userExists("phone_number", $_GET["phone_number"])) {
			_respond($endpoint, _getUser("phone_number", $_GET["phone_number"]));
		} else {
			_respondWithMessage($endpoint, null, "Seems like you're a new user, welcome to Pulse!");
		}
	}
}

function getUser() {
	$endpoint = "getUser"; 
	if (_validate(["phone_number"])) {
		if (_userExists("phone_number", $_GET["phone_number"])) {
			_respond($endpoint, _getUser("phone_number", $_GET["phone_number"]));
		} else {
			_respondWithError($endpoint, "This person hasn't downloaded Pulse yet. You should invite them!");
		}
	}
}

function checkIn() { 
	$endpoint = "createUser";
	if (_validate(["owner_id", "lat", "lon"])) {
		$checkin = new CheckIn(); 
		$checkin->owner_id = $_GET["owner_id"]; 
		$checkin->lat = $_GET["lat"]; 
		$checkin->lon = $_GET["lon"]; 
	}
}

function requestFriend() {
	$endpoint = "createUser"; 
	if (_validate(["friender_id", "friendee_id"])) {
		if (_userExists("id", $_GET["friendee_id"]) && _userExists("id", $_GET["friender_id"])) {

			if ($_GET["friendee_id"] == $_GET["friender_id"]) {
				_respondWithError($endpoint, "You can't friend yourself!");
				return; 
			}

			$friend = new Friend(); 
			$other_friended = $friend->match(["friender_id" => $_GET["friendee_id"], "friendee_id" => $_GET["friender_id"]]);
			$friended_before = $friend->match(["friender_id" => $_GET["friender_id"], "friendee_id" => $_GET["friendee_id"]]);

			// nobody friended anyone
			if (sizeof($other_friended) == 0 && sizeof($friended_before) == 0) {
				$friend->friender_id = $_GET["friender_id"];
				$friend->friendee_id = $_GET["friendee_id"];
				$friend->accepted_at = 0; 
				$friend->save(); 
				_respondWithMessage($friend, "Your request was sent!"); 
			}

			// friender friended before
			if (sizeof($other_friended) == 0 && sizeof($friended_before) == 1) {
				_respondWithError($endpoint, "You already friended that person!");
			}

			// friendee friended before 
			if (sizeof($other_friended) == 1 && sizeof($friended_before) == 0) {
				$id = $other_friended[0]["id"];
				$friend->get("id", $id);
				if ($friend->accepted_at == 0) {
					$friend->accepted_at = time(); 
					$friend->save();
					_respondWithMessage($friend, "You two are now friends!");   
				} else {
					_respondWithError($endpoint, "You two are already friends!");
				}
			}

			// already friends
			if (sizeof($other_friended) == 1 && sizeof($friended_before) == 1) {
				_respondWithError($endpoint, "You two are already friends!");
			}
		} elseif(_userExists("id", $_GET["friendee_id"])) {
			_respondWithError($endpoint, "Friender_id {$_GET["friender_id"]} is not a valid user_id.");
		} else {
			_respondWithError($endpoint, "Friendee_id {$_GET["friendee_id"]} is not a valid user_id.");
		}
	}
}

function getFriends() {
	$endpoint = "createUser";
	if (_validateWithoutError(["user_id"]) or _validateWithoutError(["phone_number"])) {
		$user = new User(); 
		
		if (_validateWithoutError(["user_id"])) {
			$user = _getUser("id", $_GET["user_id"]); 
		} else {
			$user = _getUser("phone_number", $_GET["phone_number"]); 
		}

		if (is_null($user)) {
			_respondWithError($endpoint, "A user with those credentials does not exist.");
			return; 
		}

		$user_id = $user->id; 
		$friend = new Friend(); 

		$friends1 = $friend->search("friender_id",$user_id);
		$friends2 = $friend->search("friendee_id",$user_id);

		if (sizeof($friends1) == 0 && sizeof($friends2) == 0) {
			_respond($endpoint, []); 
			return; 
		}

		$friendships = array_merge($friends1, $friends2);
		$friend_ids = [];
		$friends_since = []; 

		foreach ($friendships as $friendship) {
			if ($friendship["friender_id"] != $user_id) {
				$friend_id = $friendship["friender_id"]; 
			} else {
				$friend_id = $friendship["friendee_id"]; 
			}
			$friends_since[$friend_id] = $friendship["accepted_at"]; 
			array_push($friend_ids, $friend_id);
		}

		$friend_ids = array_unique($friend_ids); 

		$results = $user->getMultiple("id", $friend_ids);
		$respond = []; 

		for ($i=0; $i < sizeof($results); $i++) { 
			$friend = []; 
			$friend["user_id"] = $results[$i]["id"]; 
			$friend["display_name"] = $results[$i]["first_name"] . " " . $results[$i]["last_name"]; 
			$friend["username"] = $results[$i]["username"]; 
			$friend["phone_number"] = $results[$i]["phone_number"];
			$friend["accepted_at"] = $friends_since[$friend["user_id"]];
			array_push($respond, $friend); 
		}
		_respond($endpoint, $respond); 
	} else {
		_respondWithError($endpoint, "Missing either user_id or phone_number");
	}
}

function pulseUser() {
	$endpoint = "pulseUser"; 
 	if (_validate(["sender_id", "phone_number", "lat", "lon"])) {
		if (_userExists("id", $_GET["sender_id"])) {
			_pulseUser($_GET["sender_id"], $_GET["phone_number"], "", $_GET["lat"], $_GET["lon"]); 
		} else {
			_respondWithError($endpoint, "sender_id is invalid.");
		}
	}
}

function uploadAddressBook() {
	$endpoint = "uploadAddressBook"; 
	// send json data as array of arrays
	// [0] = ["phone_number", "first_name", "last_name"] 

	$post = file_get_contents('php://input');

	if (_validate(["user_id"])) {
		if (isset($post)) {
			if (_userExists("id", $_GET["user_id"])) {
				$address_book = json_decode($post);
				if (is_null($address_book)) {
					_respondWithError($endpoint, "error parsing json"); 
					return; 
				} 

				$friends = []; 

				for ($i=0; $i < sizeof($address_book); $i++) { 
					$creds = $address_book[$i];
					$phone = preg_replace("/[^0-9,.]/", "", $creds[0]);
					$phone = substr($phone, -10);
					
					if (!_contactExists($phone)) {
						$contact = new Contact(); 
						$contact->owner_id = $_GET["user_id"];
						$contact->phone_number = $phone; 
						$contact->first_name = strtolower($creds[1]);  
						$contact->last_name = strtolower($creds[2]);
						$contact->save();
					}

					if (_userExists("phone_number", $phone)) {
						$user = new User(); 
						$user->get("phone_number", $phone);
						array_push($friends, $user); 
					}

				}
				_respond($endpoint, $friends); 
			} else {
				_respondWithError($endpoint, "A user with that ID doesn't exists."); 
			}
		}
	}
}

function getFriends2() {
	$endpoint = "uploadAddressBook"; 
	$post = file_get_contents('php://input');
	if (_validate(["user_id"])) {
		if (isset($post)) {
			if (_userExists("id", $_GET["user_id"])) {
				$address_book = json_decode($post);
				if (is_null($address_book)) {
					_respondWithError($endpoint, "error parsing json"); 
					return; 
				}

				$index = 0; 

				

				for ($i=0; $i < sizeof($address_book); $i++) { 
					$creds = $address_book[$i];
					$phone = preg_replace("/[^0-9,.]/", "", $creds[0]);
					$phone = substr($phone, -10);
					if (_userExists("phone_number", $phone)) {
						$user = new User(); 
						$user->get("phone_number", $phone);
						array_push($friends, $user); 
					}
				}

				_respond($endpoint, $friends); 
			} else {
				_respondWithError($endpoint, "A user with that ID doesn't exists."); 
			}
		}
	}
}

// Must include this function. You can change its name in settings.php
function home() {
	// CODE HERE
	include("views/home.php"); 
}

// Must include this function. You can change its name in settings.php
function notfound() {
	// CODE HERE

	include("views/notfound.php"); 
}


// Useful for system wide announcments / debugging
function _everypage() {

}

?>