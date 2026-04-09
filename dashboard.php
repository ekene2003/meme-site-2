<?php
/**
 * dashboard.php — Example protected page
 * Only accessible after wallet login
 */

require_once __DIR__ . '/auth/auth_check.php';
// $walletAddress is now set and verified
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Dashboard — MyApp</title>
  <style>
    body {
      min-height: 100vh; background: #0d0f12; color: #e8eaf0;
      font-family: 'Segoe UI', system-ui, sans-serif;
      display: flex; align-items: center; justify-content: center;
    }
    .card {
      background: #161a20; border: 1px solid #1f2530;
      border-radius: 20px; padding: 2.5rem 2rem;
      max-width: 420px; width: 100%; text-align: center;
    }
    .badge {
      display: inline-block; background: #0d2318; color: #34d399;
      border: 1px solid #14432e; border-radius: 8px;
      font-family: monospace; font-size: 13px; padding: 6px 14px;
      margin: 12px 0 24px;
    }
    h1 { font-size: 22px; font-weight: 600; color: #f0f2f5; }
    p { font-size: 14px; color: #6b7280; margin-bottom: 1.5rem; }
    .logout-btn {
      padding: 10px 24px; background: transparent;
      border: 1px solid #2a3040; border-radius: 10px;
      color: #e8eaf0; cursor: pointer; font-size: 14px;
      transition: border-color 0.2s;
    }
    .logout-btn:hover { border-color: #f87171; color: #f87171; }
  </style>
</head>
<body>
  <div class="card">
    <h1>Welcome back 👋</h1>
    <div class="badge">
      ✓ <?= htmlspecialchars(substr($walletAddress, 0, 6) . '…' . substr($walletAddress, -4)) ?>
    </div>
    <p>You are authenticated with your wallet.<br>This page is only visible to connected users.</p>
    <form method="POST" action="/auth/logout.php">
      <button class="logout-btn" type="submit">Disconnect wallet</button>
    </form>
  </div>
</body>
</html>
