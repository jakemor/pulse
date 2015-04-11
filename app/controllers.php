<?php
include "./settings.php";

// optionally include some usefull functions
include "helpers.php";

// API Endpoints


function test() {
	echo "the update worked (again)";
}

function github_push()  {
    $text = "" . shell_exec("git pull");
    require "Twilio/Services/Twilio.php";
    $AccountSid = "ACbd652dd257ef5f7fdbf246a6e7af8d3a";
    $AuthToken = "e22f767658650152da61ff7dc93ad57e";
    $client = new Services_Twilio($AccountSid, $AuthToken);
    $sms = $client->account->messages->sendMessage(
        "516-210-4617", 
        "5163535851",
        $text
    );
    echo $text; 
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
		        "Hey! Here's your Chirp code: {$random}. Happy Pulsing :)"
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

			$phone = preg_replace("/[^0-9]/", "", $_GET["phone_number"]);
			$phone = substr($phone, -10);
			$user->phone_number = $phone;
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
			_respondWithMessage($endpoint, null, "Seems like you're a new user, welcome to Chirp!");
		}
	}
}

function getUser() {
	$endpoint = "getUser"; 
	if (_validate(["phone_number"])) {
		if (_userExists("phone_number", $_GET["phone_number"])) {
			_respond($endpoint, _getUser("phone_number", $_GET["phone_number"]));
		} else {
			_respondWithError($endpoint, "This person hasn't downloaded Chirp yet. You should invite them!");
		}
	}
}

function checkIn() { 
	$endpoint = "checkIn";
	if (_validate(["phone_number", "lat", "lon"])) {
		$checkin = new CheckIn();

		if (_userExists("phone_number", $_GET["phone_number"])) {
			$user = _getUser("phone_number", $_GET["phone_number"]);
			$checkin->phone_number = $_GET["phone_number"]; 
			$checkin->owner_id = $user->id; 
			$checkin->lat = $_GET["lat"]; 
			$checkin->lon = $_GET["lon"]; 
			$checkin->save(); 
			_respond($endpoint, "Checked In"); 
		}
	}
}

function addFriend() {

	$endpoint = "requestFriend"; 
	if (_validate(["owner_id", "friend_phone_number"])) {

		$phone = preg_replace("/[^0-9]/", "", $_GET["friend_phone_number"]);
		$phone = substr($phone, -10);
		$_GET["friend_phone_number"] = $phone; 

		if (strlen($phone) != 10) {
			_respondWithError($endpoint, "In order to friend this person, we need a full 10-digit phone number. Update their contact info in the phone app and try again.");
			return; 
		}

		if (_userExists("id", $_GET["owner_id"])) {

			$user = _getUser("id", $_GET["owner_id"]);

			if ($_GET["friend_phone_number"] == $user->phone_number) {
				_respondWithError($endpoint, "You can't friend yourself!");
				return; 
			}

			$friend = new Friend(); 
			$friended_before = $friend->match(["owner_id" => $_GET["owner_id"], "friend_phone_number" => $_GET["friend_phone_number"]]);

			if (sizeof($friended_before) > 0) {
				_respondWithError($endpoint, "You two are already friends.");
				return; 
			} else {
				$friend->owner_id = $_GET["owner_id"]; 
				$friend->friend_phone_number = $_GET["friend_phone_number"];
				$friend->save();

				$username = $user->username; 

				if (!_userExists("phone_number", $_GET["friend_phone_number"])) {
					$phone = $_GET["friend_phone_number"]; 
					$texted = _textPhoneNumber($phone, "{$username} added you on Chirp! Download it here to Chirp back at them. getchirp.com");
					if ($texted) {
						_newNotification($user->id, $_GET["friend_phone_number"], 3, "You added {$phone}");
						_respondWithMessage($endpoint, $friend, "You added {$phone}!");
					} else {
						_newNotification($user->id, $_GET["friend_phone_number"], 3, "{$phone} is not a valid phone number");
						_respondWithMessage($endpoint, $friend, "{$phone} is not a valid phone number!");
					}
				} else {
					$the_friend = _getUser("phone_number", $_GET["friend_phone_number"]);
					_newNotification($the_friend->id, $user->phone_number, 4, "{$username} added you");
					_newNotification($user->id, $the_friend->phone_number, 3, "You added {$the_friend->username}");
					_respondWithMessage($endpoint, $friend, "You added {$the_friend->username}!");
				}

				
			 }

		} else {
			_respondWithError($endpoint, "Invalid owner_id.");
		} 
	}
}

function getFriends() {
	$endpoint = "getFriends";
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

		$friendships = $friend->search("owner_id",$user_id);

		if (sizeof($friendships) == 0) {
			_respond($endpoint, []); 
			return; 
		}

		$friend_phone_numbers = [];
		$friends_since = []; 

		foreach ($friendships as $friendship) {
			$friends_since[$friendship["friend_phone_number"]] = $friendship["created_at"]; 
			array_push($friend_phone_numbers, $friendship["friend_phone_number"]);
		}

		$friend_phone_numbers = array_unique($friend_phone_numbers); 

		$results = $user->getMultiple("phone_number", $friend_phone_numbers);
		$respond = []; 

		for ($i=0; $i < sizeof($results); $i++) { 
			$friend = []; 
			$friend["user_id"] = $results[$i]["id"]; 
			$friend["verified_account"] = TRUE; 
			$friend["display_name"] = $results[$i]["first_name"] . " " . $results[$i]["last_name"]; 
			$friend["username"] = $results[$i]["username"]; 
			$friend["phone_number"] = $results[$i]["phone_number"];
			$friend["friends_since"] = $friends_since[$friend["phone_number"]];
			array_push($respond, $friend);
			
			$found = array_search($friend["phone_number"], $friend_phone_numbers);

			if ($found === FALSE) {
				break;
			} else {
				unset($friend_phone_numbers[$found]);
			}
		}

		$friend_phone_numbers = array_values($friend_phone_numbers);

		for ($i=0; $i < sizeof($friend_phone_numbers); $i++) { 
			$friend = [];
			$friend["verified_account"] = FALSE; 
			$friend["phone_number"] = $friend_phone_numbers[$i];
			$friend["friends_since"] = $friends_since[$friend_phone_numbers[$i]];
			array_push($respond, $friend);
		}

		_respond($endpoint, $respond); 
	} else {
		_respondWithError($endpoint, "Missing either user_id or phone_number");
	}
}

function chirpUser() {
	$endpoint = "chirpUser"; 
 	if (_validate(["sender_id", "phone_number", "lat", "lon"])) {
		if (_userExists("id", $_GET["sender_id"])) {
			_chirpUser($_GET["sender_id"], $_GET["phone_number"], "", $_GET["lat"], $_GET["lon"]); 
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
					$phone = preg_replace("/[^0-9]/", "", $creds[0]);
					$phone = substr($phone, -10);
					if (strlen($phone) == 10) {
						if (!_contactExists($phone)) {
							$contact = new Contact(); 
							$contact->owner_id = $_GET["user_id"];
							$contact->phone_number = "" . $phone; 
							$contact->first_name = preg_replace("/[^A-Za-z0-9 ]/", "", strtolower($creds[1]));  
							$contact->last_name = preg_replace("/[^A-Za-z0-9 ]/", "", strtolower($creds[2]));;
							$contact->save();
						}

						if (_userExists("phone_number", $phone)) {
							$user = new User(); 
							$user->get("phone_number", $phone);

							$friend = []; 
							$friend["first_name"] = $user->first_name;
							$friend["last_name"] = $user->last_name;
							$friend["id"] = $user->id;
							$friend["phone_number"] = $user->phone_number;
							$friend["username"] = $user->username;
							$friend["profile_pic_url"] = $user->profile_pic_url;

							array_push($friends, $friend); 
						}
					}
				}
				_respond($endpoint, $friends); 
			} else {
				_respondWithError($endpoint, "A user with that ID does not exists."); 
			}
		} else {
			_respondWithError($endpoint, "No post data.");
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
				_respondWithError($endpoint, "A user with that ID does not exists."); 
			}
		}
	}
}

function getNotifications() {
	$endpoint = "getNotifications"; 
	if (_validate(["owner_id", "start", "length"])) {
		if (_userExists("id", $_GET["owner_id"])) {
			$notification = new Notification(); 
			$return = $notification->search("owner_id", $_GET["owner_id"]);
			$return = array_reverse($return);
			$return = array_slice($return, $_GET["start"], $_GET["length"]);
			_respond($endpoint, $return); 
		} else {
			_respondWithError($endpoint, "The requested user_id doesn't exist.");
		}
	}	
}

function getChirps() {
	$endpoint = "getChirps"; 
	if (_validate(["phone_number", "start", "length"])) {
		if (_userExists("phone_number", $_GET["phone_number"])) {
			
			$pulse = new Pulse(); 
			$all = $pulse->search("other_phone_number", $_GET["phone_number"]);
			$all = array_slice($all, $_GET["start"], $_GET["length"]);

			$return = []; 

			foreach ($all as $one) {
				$pulse = []; 
				$user = new User(); 
				$user->get("id", $one["owner_id"]); 
				$pulse["phone_number"] = $user->phone_number;
				$pulse["type"] = "received";
				$pulse["first_name"] = $user->first_name; 
				$pulse["last_name"] = $user->last_name; 
				$pulse["username"] = $user->username; 
				$pulse["lat"] = $one["lat"]; 
				$pulse["lon"] = $one["lon"]; 
				$pulse["radius"] = $one["radius"]; 
				$pulse["created_at"] = $one["created_at"]; 
				array_push($return, $pulse); 
			}

			$user = new User(); 
			$user->get("phone_number", $_GET["phone_number"]);

			$pulse = new Pulse(); 
			$all = $pulse->search("owner_id", $user->id);
			$all = array_slice($all, $_GET["start"], $_GET["length"]);

			foreach ($all as $one) {
				$pulse = []; 

				if (_userExists("phone_number", $one["other_phone_number"])) {
					$user = new User(); 
					$user->get("phone_number", $one["other_phone_number"]); 
					$pulse["type"] = "sent";
					$pulse["phone_number"] = $user->phone_number; 
					$pulse["first_name"] = $user->first_name; 
					$pulse["last_name"] = $user->last_name; 
					$pulse["username"] = $user->username; 
				} else {
					$pulse["type"] = "sent";
					$pulse["phone_number"] = $one["other_phone_number"]; 
					$pulse["first_name"] = $one["other_phone_number"]; 
					$pulse["last_name"] = ""; 
					$pulse["username"] = $one["other_phone_number"]; 
				}

					$pulse["lat"] = $one["lat"]; 
					$pulse["lon"] = $one["lon"]; 
					$pulse["radius"] = $one["radius"]; 
					$pulse["created_at"] = $one["created_at"]; 

				array_push($return, $pulse); 
			}

			function cmp($a, $b) {
			    if (intval($a["created_at"]) == intval($b["created_at"])) {
			        return 0;
			    }
			    return (intval($a["created_at"]) < intval($b["created_at"])) ? -1 : 1;
			}

			usort($return, "cmp"); 

			$return = array_slice($return, $_GET["start"], $_GET["length"]);
			
			_respond($endpoint, $return);

		} else {
			_respondWithError($endpoint, "The requested phone number doesn't exist.");
		}
	}	
}

function getReceivedChirps() {
	$endpoint = "getChirps"; 
	if (_validate(["phone_number", "start", "length"])) {
		if (_userExists("phone_number", $_GET["phone_number"])) {
			
			$pulse = new Pulse(); 
			$all = $pulse->search("other_phone_number", $_GET["phone_number"]);
			$all = array_slice($all, $_GET["start"], $_GET["length"]);

			$return = []; 

			foreach ($all as $one) {
				$pulse = []; 
				$user = new User(); 
				$user->get("id", $one["owner_id"]); 
				$pulse["phone_number"] = $user->phone_number;
				$pulse["type"] = "received";
				$pulse["first_name"] = $user->first_name; 
				$pulse["last_name"] = $user->last_name; 
				$pulse["username"] = $user->username; 
				$pulse["lat"] = $one["lat"]; 
				$pulse["lon"] = $one["lon"]; 
				$pulse["radius"] = $one["radius"]; 
				$pulse["created_at"] = $one["created_at"]; 
				array_push($return, $pulse); 
			}


			function cmp($a, $b) {
			    if (intval($a["created_at"]) == intval($b["created_at"])) {
			        return 0;
			    }
			    return (intval($a["created_at"]) < intval($b["created_at"])) ? -1 : 1;
			}

			usort($return, "cmp"); 
			
			_respond($endpoint, $return);

		} else {
			_respondWithError($endpoint, "The requested phone number doesn't exist.");
		}
	}	
}


function getNearby() {
	$endpoint = "getNearby"; 
	if (_validate(["owner_id", "lat", "lon"])) {

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
