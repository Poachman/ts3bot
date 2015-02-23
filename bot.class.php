<?php
    require_once("libraries/TeamSpeak3/TeamSpeak3.php");
    class tsbot {
        private $config;
        private $server;
        private $clients;
        private $lcid;

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
            // TODO - Change bot name & move bot to another cahnnel
//    		$this->cmd("clientupdate client_nickname=" . $this->escape($this->config['botNickName']));
//    		$this->cmd("clientmove clid=" . $this->whoami[0]['client_id'] . " cid=" . $this->config['botCh']);

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
    		$this->idleMove();
/*    		$this->functionRooms();
    		$this->setNormalUsers();
    		$this->createList();
            */
        }

        private function getNewServerInfo() {
            $this->server->clientListReset();
            $this->server->channelListReset();
            $this->clients = $this->server->clientList();
        }

        private function isIdle($client) {
            if($client->getProperty("client_type") === 0) {
                    $idletime = $this->config['idletime']['normal'];

                    if($client['client_away'])
                        $idletime = $this->config['idletime']['away'];

                    if($client['client_input_muted'])
                        $idletime = $this->config['idletime']['muted'];

                    if($client['client_output_muted'])
                        $idletime = $this->config['idletime']['deafened'];

                    if($client['client_channel_group_id'] == $this->config['channelAdminGroupId'])
                        $idletime = $this->config['idletime']['admin'];

                    return $idletime < $client->getProperty("client_idle_time");
            } else {
                return false;
            }
        }

        private function idleMove() {
            foreach($this->clients as $client) {
                if($client->getProperty("cid") != $this->config['idleCh']) {
                    if($this->isIdle($client)) {
                        $this->lcid[$client->getProperty("cldbid")] = $client->getProperty("cid");
                        $client->move($this->config['idleCh']);
                    }
                } else {
                    if(!$this->isIdle($client)) {
                        $client->move($this->lcid[$client->getProperty("cldbid")]);
                    }
                }
            }
        }

        private function log($string, $level = 1, $cmd = false) {
            if(!is_string($string)) $string = var_export($string, true);
            $string = date("m/d/y H:i:s - ") . $string;
            if($level > 0) {
                echo $string . "\n";
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
