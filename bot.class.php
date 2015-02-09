<?php
    require_once("libraries/TeamSpeak3/TeamSpeak3.php");
    class tsbot {
        private $config;
        private $server;

        public function tsbot($configFile = "./config.json") {
            if(file_exists($configFile)) {
                $this->config = json_decode(file_get_contents($configFile), true);
                if($this->config === NULL) {
                    die("Config file invalid");
                }
            } else {
                die("Config file doesn't exist");
            }

            $this->connect();
        }

        private function connect() {
            print_r($this->config);
            $this->server = TeamSpeak3::factory("serverquery://{$this->config['user']}:{$this->config['pass']}@{$this->config['address']}:{$this->config['port']}/?server_port={$this->config['sport']}");
        }


        private function log($string, $level = 1, $cmd = false) {
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
