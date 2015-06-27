<?php
class idleCheck {
	protected $bot;
  private $lcid = array();

	public function idleCheck(&$bot) {
		$this->bot = $bot;
	}

	public function tick(&$clients) {
    foreach($clients as $client) {
      if($client->getProperty("client_type") == 0) {
        if($client->getProperty("cid") != $this->bot->config['idleCh']) {
          if($this->isIdle($client)) {
            $this->lcid[$client->getProperty("cldbid")] = $client->getProperty("cid");
            $client->move($this->bot->config['idleCh']);
          }
        } else {
          if(!($this->isIdle($client) || $this->isMuted($client))) {
            if(array_key_exists($client->getProperty("cldbid"), $this->lcid)) {
              $client->move($this->lcid[$client->getProperty("cldbid")]);
            } else {
              $client->move($this->bot->config['lobbyCh']);
            }
          }
        }
      }
    }
	}

	private function isIdle($client) {
		if($client->getProperty("client_type") === 0) {
				$idletime = $this->bot->config['idletime']['normal'];

				if($client['client_channel_group_id'] == $this->bot->config['channelAdminGroupId'])
					$idletime = $this->bot->config['idletime']['admin'];

				if($client['client_input_muted'])
					$idletime = $this->bot->config['idletime']['muted'];

				if($client['client_output_muted'])
					$idletime = $this->bot->config['idletime']['deafened'];

				if($client['client_away'])
					$idletime = $this->bot->config['idletime']['away'];

				return $idletime < $client->getProperty("client_idle_time");
		} else {
			return false;
		}
	}


	private function isMuted($client) {
		return $client->getProperty("client_away")
			|| $client->getProperty("client_input_muted")
			|| $client->getProperty("client_output_muted");
	}
}
?>
