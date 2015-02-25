<?php
	class functionRoom {
		protected $channelId;
		protected $admin;
		protected $bot;

		public function functionRoom(&$server, $channelAdmin, &$bot) {
			$this->bot = $bot;
			$this->admin = $channelAdmin;
			$this->channel = $server->channelCreate(array(
				"channel_name"				=> "{$this->admin->getProperty("client_nickname")}'s Room",
				"cpid"						=> $this->bot->config['fnRoomSpacerId'],
				"channel_flag_permanent"	=> "1"
			));
			$this->admin->move($this->channel);
			$this->admin->setChannelGroup($this->channel, $this->bot->config['channelAdminGroupId']);
		}

		public function tick() {

		}
	}
?>
