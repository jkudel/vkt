<?php
require_once('includes/common.php');

\sessions\logout();
header('Location: index.php');