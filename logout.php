<?php
require_once 'classes/AuthManager.php';

$auth = AuthManager::getInstance();
$auth->logout();

// Redirecionar para login com mensagem
header('Location: login.php?message=' . urlencode('Logout realizado com sucesso!'));
exit;
?>
