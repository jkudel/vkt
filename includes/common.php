<?php
require_once('util.php');
require_once('ajax_util.php');
require_once('messages.php');
require_once('sessions.php');
require_once('database.php');

const MAX_ORDER_LIST_PART_SIZE = 50;
const COMMISSION = 0.1;

const ROLE_EXECUTOR = 0;
const ROLE_CUSTOMER = 1;
$allRoleNames = [msg('executor'), msg('customer')];