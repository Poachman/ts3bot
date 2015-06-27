<?php
	require_once("./libraries/TeamSpeak3/TeamSpeak3.php");
	require_once("./functionRoom.class.php");
	require_once("./idleCheck.class.php");
	class tsbot {
		public $config;
		public $server;
		private $clients;
		private $lcid = array();
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
			// TODO - Change bot name & move bot to another cahnnel
//			$this->cmd("clientupdate client_nickname=" . $this->escape($this->config['botNickName']));
//			$this->cmd("clientmove clid=" . $this->whoami[0]['client_id'] . " cid=" . $this->config['botCh']);
/*			$bot = $this->server->getParent()->whoami();
			$bot = current($this->server->clientList(array("client_database_id" => $bot['client_database_id'])));
			$bot->move($this->config['botCh']);
//			$bot->modify(array("client_nickname" => $this->config['botNickName']));
*/

			$this->getNewServerInfo();
		}

		private function deinit() {

		}

		private function tick() {
			$brain = file($this->config['brain']);
	/*		foreach($brain as $command) {
				$this->brainCommand($command);
			}*/
			$this->getNewServerInfo();
			$this->idleCheck();
			$this->functionRooms();
/*			$this->setNormalUsers();
			$this->createList();
			*/
		}

		private function getNewServerInfo() {
			$this->server->clientListReset();
			$this->server->channelListReset();
			$this->clients = $this->server->clientList();
			$this->log("Updated Server Info");
		}

		private function idleCheck() {
			$this->idleCheck->tick($this->clients);
		}

		private function functionRooms() {
			$functionRoom = $this->server->channelList(array("cid" => $this->config['newFnRoomId']))[$this->config['newFnRoomId']];

			foreach($functionRoom->clientList() as $client) {
				$this->functionRooms[] = new functionRoom($client, $this);
			}

			foreach($this->functionRooms as $roomId => $functionRoom) {
				if($functionRoom->isAlive()) {
					$functionRoom->tick();
				} else {
					unset($this->functionRooms[$roomId]);
				}
			}
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
