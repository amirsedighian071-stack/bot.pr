<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../src/Core/Database.php';
require_once __DIR__ . '/../../src/Core/Auth.php';
use Core\Database;
use Core\Auth;
Auth::startSession();
header('Content-Type: application/json; charset=utf-8');
if (!Auth::check()) { http_response_code(401); echo json_encode(['error'=>'unauthorized']); exit; }
try {
  $stats = [
    'users' => (int)(Database::fetchOne("SELECT COUNT(*) as c FROM users")['c'] ?? 0),
    'orders' => (int)(Database::fetchOne("SELECT COUNT(*) as c FROM orders")['c'] ?? 0),
    'paid' => (int)(Database::fetchOne("SELECT COUNT(*) as c FROM orders WHERE status='completed'")['c'] ?? 0),
    'revenue' => (int)(Database::fetchOne("SELECT COALESCE(SUM(final_price),0) as s FROM orders WHERE status='completed'")['s'] ?? 0),
    'subs' => (int)(Database::fetchOne("SELECT COUNT(*) as c FROM subscriptions WHERE status='active'")['c'] ?? 0),
    'tickets' => (int)(Database::fetchOne("SELECT COUNT(*) as c FROM tickets WHERE status='open'")['c'] ?? 0),
    'panels' => (int)(Database::fetchOne("SELECT COUNT(*) as c FROM panels WHERE is_active=1")['c'] ?? 0),
  ];
  $recentOrders = Database::fetchAll("SELECT o.*, p.name as plan_name FROM orders o LEFT JOIN plans p ON p.id=o.plan_id ORDER BY o.id DESC LIMIT 5");
  $recentTickets = Database::fetchAll("SELECT t.*, u.username FROM tickets t LEFT JOIN users u ON u.telegram_id=t.telegram_id ORDER BY t.updated_at DESC LIMIT 5");
  echo json_encode(['success'=>true,'stats'=>$stats,'recentOrders'=>$recentOrders,'recentTickets'=>$recentTickets], JSON_UNESCAPED_UNICODE);
} catch(Throwable $e){ http_response_code(500); echo json_encode(['success'=>false,'message'=>$e->getMessage()]); }
