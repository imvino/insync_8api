<?php

require_once("authSystem.php");

authSystem::InvalidateUser();

header("Location: /auth/login.php");

?>