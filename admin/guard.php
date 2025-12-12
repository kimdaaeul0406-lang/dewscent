<?php
if (session_status() === PHP_SESSION_NONE) {
	session_start();
}

function ensure_admin(): void
{
	if (empty($_SESSION['admin_logged_in'])) {
		header('Location: index.php');
		exit;
	}
}


