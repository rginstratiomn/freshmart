<?php
require_once 'functions.php';

header('Content-Type: application/json');

$count = getCartItemCount();
echo json_encode(['count' => $count]);
?>