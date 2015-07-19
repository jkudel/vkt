<?php
require_once('libraries/common.php');

\sessions\logout();
header('Location: index.php');