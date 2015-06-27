<?php
class serverList {
	protected $bot;
	private $viewer;

	public function serverList(&$bot) {
		$this->bot = $bot;
		$this->viewer = new TeamSpeak3_Viewer_Html("r/img/ts/", "r/img/ts/countryflags/", "data:image");
	}

	public function tick() {
		file_put_contents($this->bot->config['listFile'], $this->bot->server->getViewer($this->viewer));
	}
}
?>
