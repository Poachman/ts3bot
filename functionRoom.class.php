<?php
	class functionRoom {
		protected $channelId;
		protected $admin;
		protected $bot;

		public function functionRoom(&$server, $channelAdmin, &$bot) {
			$this->bot = $bot;
			$this->admin = $channelAdmin;
			$this->init();
			$this->channel = $server->channelCreate(array(
				"channel_name"	=> "{$this->admin->getProperty("client_nickname")}'s Room",
				"cpid"			=> $this->bot->config['functionRoomSpacerId']
			));
			$this->admin->move($this->channel);
		}

		private function init() {

		}

		public function tick() {

		}
	}
?>
