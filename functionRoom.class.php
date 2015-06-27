<?php
	class functionRoom {
		protected $channel;
		protected $admin;
		protected $bot;
		protected $alive = true;

		public function functionRoom($channelAdmin, &$bot) {
			$this->bot = $bot;
			$this->admin = $channelAdmin;
			$this->channel = $this->bot->server->channelCreate(array(
				"channel_name"				=> "{$this->admin->getProperty("client_nickname")}'s Room",
				"cpid"						=> $this->bot->config['fnRoomSpacerId'],
				"channel_flag_permanent"	=> "1"
			));
			$this->channel = current($this->bot->server->channelList(array("cid" => $this->channel)));
			$this->admin->move($this->channel->getProperty("cid"));
			$this->admin->setChannelGroup($this->channel->getProperty("cid"), $this->bot->config['channelAdminGroupId']);
		}

		public function tick() {
			if($this->isDead()) {
				$this->bot->log($this->channel->getProperty("channel_name") . " had died.");
				$this->alive = false;
				$this->delete();
			}
		}

		public function isAlive() {
			return $this->alive;
		}

		private function isDead() {
			$this->bot->log("Idle: " . $this->channel->getProperty("seconds_empty"));
			return $this->channel->getProperty("seconds_empty") > $this->bot->config['fnRoomRestTime'];
		}

		private function delete() {
			$this->channel->delete();
		}
	}
?>
