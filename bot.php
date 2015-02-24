<?php
unlink("./botbrain");
require_once "bot.class.php";

$bot = new tsbot();

$bot->start();

?>
