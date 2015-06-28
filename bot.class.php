<?php
	error_reporting(E_ALL);
	require_once("./libraries/TeamSpeak3/TeamSpeak3.php");
	require_once("./functionRoom.class.php");
	require_once("./idleCheck.class.php");
	require_once("./serverList.class.php");
	class tsbot {
		public $config;
		public $server;
		private $clients;
		private $functionRooms = array();
		private $idleCheck = NULL;

		public function tsbot($configFile = "./config.json") {
			if(file_exists($configFile)) {
				if(is_readable($configFile)) {
					$this->config = json_decode(file_get_contents($configFile), true);
					if($this->config === NULL) {
						die("Config file invalid");
					}
				} else {
					die("Config file can't be read");
				}
			} else {
				die("Config file doesn't exist");
			}

			$this->connect();
		}

		private function connect() {
			$this->server = TeamSpeak3::factory("serverquery://{$this->config['user']}:{$this->config['pass']}@{$this->config['address']}:{$this->config['port']}/?server_port={$this->config['sport']}");
			// https://docs.planetteamspeak.com/ts3/php/framework/class_team_speak3___node___server.html
		}

		public function stop() {
			if(file_exists($this->config['brain']))
				rename($this->config['brain'], $this->config['brain'] . ".dead");
		}

		public function start() {
			if(file_exists($this->config['brain']) === false) {
				if($this->server !== NULL) {
					$this->init();
					if(file_put_contents($this->config['brain'], "") === false) {
						$this->log("Unable to write " . $this->config['brain']);
						return false;
					}
					while(file_exists($this->config['brain'])) {
						$this->tick();
						sleep($this->config['loopTime']);
					}
					$this->deinit();
				}
				exit();
			} else {
				$this->log("Bot Already Online", 2);
				exit("Bot Already Online\n");
				return false;
			}
		}

		private function init() {
			$this->idleCheck = new idleCheck($this);
			$this->serverList = new serverList($this);
			$this->server->serverSelectByPort($this->config['sport']);
			$this->log("Server Port: " . $this->server->serverSelectedPort());
			$this->server->execute("clientupdate client_nickname=" . $this->escape($this->config['botNickName']));
			$this->server->execute("clientmove clid=" . $this->server->whoamiGet("client_id") . " cid=" . $this->config['botCh']);

			$this->getNewServerInfo();
		}

		private function deinit() {

		}

		private function tick() {
//			$brain = file($this->config['brain']);
	/*		foreach($brain as $command) {
				$this->brainCommand($command);
			}*/
			$this->getNewServerInfo();
			$this->idleCheck();
			$this->functionRooms();
//			$this->setNormalUsers();
			if(filemtime($this->config['listFile']) >= 30) {
				$this->createList();
			}
		}

		private function getNewServerInfo() {
			$this->server->channelListReset();
			$this->server->clientListReset();
			$this->clients = $this->server->clientList();
		}

		private function idleCheck() {
			$this->idleCheck->tick($this->clients);
		}

		private function hasFunctionRoom($client) {
			foreach ($this->functionRooms as $roomId => $functionRoom) {
				if($functionRoom->getAdmin()->getProperty("client_database_id") === $client->getProperty("client_database_id")) {
					return true;
				}
			}
			return false;
		}

		private function functionRooms() {
			$functionRoom = $this->server->channelList(array("cid" => $this->config['newFnRoomId']))[$this->config['newFnRoomId']];

			foreach($functionRoom->clientList() as $client) {
				if(!$this->hasFunctionRoom($client)) {
					$this->functionRooms[] = new functionRoom($client, $this);
				} else {
					$client->kick(TeamSpeak3::KICK_CHANNEL);
					$client->poke("You already have a function room.  You don't get another...");
				}
			}

			foreach($this->functionRooms as $roomId => $functionRoom) {
				if($functionRoom->isAlive()) {
					$functionRoom->tick();
				} else {
					unset($this->functionRooms[$roomId]);
				}
			}
		}

		private function createList() {
			$this->serverList->tick();
		}

		public function escape($string, $unescape = false) {
			$escaped = array('\\\\', "\/", "\s", "\p", "\a", "\b", "\f", "\n", "\r", "\t", "\v");
			$unescaped = array(chr(92), chr(47), chr(32), chr(124), chr(7), chr(8), chr(12), chr(10), chr(3), chr(9), chr(11));
			if($unescape)
				return str_replace($escaped, $unescaped, $string);
			else
				return str_replace($unescaped, $escaped, $string);
		}

		public function unescape($string) {
			return $this->escape($string, true);
		}

		public function log($string, $level = 0, $cmd = false) {
			if(!is_string($string)) $string = var_export($string, true);
			$string = date("m/d/y H:i:s - ") . $string;
			if($level > 0 || $this->config['debug'] == true) {
				$string .= "\n";
				echo $string;
				explode("\n", $string);
				if(file_exists($this->config['logFile'])) {
					$log = file($this->config['logFile']);
				}
				$log[] = $string;
				implode("\n", $log);
				file_put_contents($this->config['logFile'], $log);
			}
			if($level > 5) {
				die();
			}
		}

	}
?>
