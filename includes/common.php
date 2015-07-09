<?php
require_once('util.php');
require_once('ajax_util.php');
require_once('messages.php');
require_once('sessions.php');
require_once('database.php');

const ORDER_LIST_PART_SIZE = 20;
const ROLE_EXECUTOR = 0;
const ROLE_CUSTOMER = 1;
const COMMISSION = 0.1;

const VIEW_MODE_DONE = 0;
const VIEW_MODE_WAITING = 1;
const VIEW_MODE_AVAILABLE = 2;

$allRoleNames = [msg('executor'), msg('customer')];