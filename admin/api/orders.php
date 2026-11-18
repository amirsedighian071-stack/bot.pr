<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../src/Core/Database.php';
require_once __DIR__ . '/../../src/Core/Auth.php';
use Core\Database; use Core\Auth;
Auth::startSession();
header('Content-Type: application/json; charset=utf-8');
if (!Auth::check()) { http_response_code(401); echo json_encode(['error'=>'unauthorized']); exit; }
$status = $_GET['status'] ?? '';
$q = $_GET['q'] ?? '';
$where = '';
$params = [];
if ($status) { $where = "WHERE o.status=?"; $params[]=$status; }
if ($q) { $where = ($where ? $where." AND" : "WHERE") . " (o.order_id_unique LIKE ? OR o.telegram_id LIKE ?)"; $params[]="%$q%"; $params[]="%$q%"; }
$orders = Database::fetchAll("SELECT o.*, p.name as plan_name FROM orders o LEFT JOIN plans p ON p.id=o.plan_id $where ORDER BY o.id DESC LIMIT 100", $params);
echo json_encode(['success'=>true,'orders'=>$orders], JSON_UNESCAPED_UNICODE);
