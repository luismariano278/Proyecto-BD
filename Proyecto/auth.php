<?php
if (session_status() === PHP_SESSION_NONE) {
session_start();
}

function require_roles($roles)
{
if (!isset($_SESSION['rol'])) {
header("Location: login.html");
exit;
}

if (!in_array((int)$_SESSION['rol'], $roles, true)) {
http_response_code(403);
echo "Acceso no autorizado para este modulo";
exit;
}
}

function current_role()
{
return isset($_SESSION['rol']) ? (int)$_SESSION['rol'] : 0;
}
