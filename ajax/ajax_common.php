<?php
$secondsToSleep = getIfExists(CONFIG, 'show_down_ajax');

if ($secondsToSleep > 0) {
  sleep($secondsToSleep);
}