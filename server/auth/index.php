<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Connect Wallet — MyApp</title>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/ethers/6.7.1/ethers.umd.min.js"></script>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background: #0d0f12;
      font-family: 'Segoe UI', system-ui, sans-serif;
      color: #e8eaf0;
    }

    .bg-grid {
      position: fixed; inset: 0; z-index: 0;
      background-image:
        linear-gradient(rgba(29,158,117,0.05) 1px, transparent 1px),
        linear-gradient(90deg, rgba(29,158,117,0.05) 1px, transparent 1px);
      background-size: 40px 40px;
    }

    .card {
      position: relative; z-index: 1;
      background: #161a20;
      border: 1px solid #1f2530;
      border-radius: 20px;
      padding: 2.5rem 2rem;
      width: 100%;
      max-width: 400px;
      text-align: center;
    }

    .logo {
      width: 52px; height: 52px;
      background: linear-gradient(135deg, #1D9E75, #0F6E56);
      border-radius: 14px;
      display: flex; align-items: center; justify-content: center;
      margin: 0 auto 1.25rem;
      font-size: 22px;
    }

    h1 {
      font-size: 20px; font-weight: 600;
      color: #f0f2f5;
      margin-bottom: 6px;
    }

    .subtitle {
      font-size: 13px; color: #6b7280;
      margin-bottom: 2rem;
      line-height: 1.5;
    }

    .wallet-btn {
      width: 100%;
      display: flex; align-items: center; gap: 12px;
      padding: 13px 16px;
      background: #1e2330;
      border: 1px solid #2a3040;
      border-radius: 12px;
      cursor: pointer;
      transition: border-color 0.2s, background 0.2s;
      margin-bottom: 10px;
      color: #e8eaf0;
      font-size: 14px; font-weight: 500;
    }

    .wallet-btn:hover { border-color: #1D9E75; background: #1a2a24; }
    .wallet-btn:disabled { opacity: 0.5; cursor: not-allowed; }

    .wallet-icon {
      width: 36px; height: 36px;
      border-radius: 8px;
      display: flex; align-items: center; justify-content: center;
      font-size: 18px; flex-shrink: 0;
    }
    .metamask-icon { background: #fff3e0; }
    .wc-icon { background: #e3f0ff; }

    .wallet-btn-text { text-align: left; }
    .wallet-btn-label { display: block; font-size: 14px; font-weight: 500; }
    .wallet-btn-sub { display: block; font-size: 11px; color: #6b7280; margin-top: 1px; }

    .divider {
      display: flex; align-items: center; gap: 10px;
      margin: 16px 0; color: #3a4050; font-size: 12px;
    }
    .divider::before, .divider::after {
      content: ''; flex: 1; height: 1px; background: #2a3040;
    }

    .status {
      margin-top: 1.25rem;
      padding: 10px 14px;
      border-radius: 10px;
      font-size: 13px;
      display: none;
    }
    .status.info    { display: block; background: #0d2031; color: #5bb8f5; border: 1px solid #1a3a55; }
    .status.success { display: block; background: #0d2318; color: #34d399; border: 1px solid #14432e; }
    .status.error   { display: block; background: #2a0f0f; color: #f87171; border: 1px solid #4a1f1f; }

    .address-badge {
      display: inline-flex; align-items: center; gap: 6px;
      background: #1a2a24; border: 1px solid #1D9E75;
      border-radius: 8px; padding: 6px 12px;
      font-family: monospace; font-size: 13px; color: #34d399;
      margin-top: 10px;
    }

    .spinner {
      display: inline-block; width: 14px; height: 14px;
      border: 2px solid currentColor; border-top-color: transparent;
      border-radius: 50%;
      animation: spin 0.7s linear infinite;
    }
    @keyframes spin { to { transform: rotate(360deg); } }

    .footer {
      margin-top: 1.5rem;
      font-size: 11px; color: #3a4050;
    }
  </style>
</head>
<body>
  <div class="bg-grid"></div>

  <div class="card">
    <div class="logo">🔐</div>
    <h1>Connect your wallet</h1>
    <p class="subtitle">Sign in securely with your crypto wallet.<br>No password needed.</p>

    <button class="wallet-btn" id="btn-metamask" onclick="connectWallet('metamask')">
      <div class="wallet-icon metamask-icon">🦊</div>
      <div class="wallet-btn-text">
        <span class="wallet-btn-label">MetaMask</span>
        <span class="wallet-btn-sub">Browser extension</span>
      </div>
    </button>

    <div class="divider">or</div>

    <button class="wallet-btn" id="btn-wc" onclick="connectWallet('walletconnect')" disabled>
      <div class="wallet-icon wc-icon">🔗</div>
      <div class="wallet-btn-text">
        <span class="wallet-btn-label">WalletConnect</span>
        <span class="wallet-btn-sub">Mobile wallets (coming soon)</span>
      </div>
    </button>

    <div class="status" id="status"></div>

    <p class="footer">By connecting, you agree to our Terms of Service.<br>This request will not trigger a blockchain transaction.</p>
  </div>

  <script>
    const statusEl = document.getElementById('status');

    function setStatus(msg, type = 'info') {
      statusEl.className = 'status ' + type;
      statusEl.innerHTML = msg;
    }

    function setLoading(btnId, loading) {
      const btn = document.getElementById(btnId);
      btn.disabled = loading;
      if (loading) {
        btn.querySelector('.wallet-btn-label').innerHTML =
          '<span class="spinner"></span> Waiting...';
      } else {
        btn.querySelector('.wallet-btn-label').textContent =
          btnId === 'btn-metamask' ? 'MetaMask' : 'WalletConnect';
      }
    }

    async function connectWallet(type) {
      if (type !== 'metamask') return;

      if (!window.ethereum) {
        setStatus('MetaMask not detected. <a href="https://metamask.io" target="_blank" style="color:inherit;text-decoration:underline;">Install it here</a>.', 'error');
        return;
      }

      setLoading('btn-metamask', true);
      setStatus('<span class="spinner"></span> Requesting wallet access…', 'info');

      try {
        // 1. Connect wallet
        const provider = new ethers.BrowserProvider(window.ethereum);
        await provider.send('eth_requestAccounts', []);
        const signer  = await provider.getSigner();
        const address = await signer.getAddress();

        setStatus(`<span class="spinner"></span> Fetching nonce for <code>${shortAddr(address)}</code>…`, 'info');

        // 2. Get nonce from PHP
        const nonceRes = await fetch(`/auth/nonce.php?address=${address}`);
        if (!nonceRes.ok) throw new Error('Failed to get nonce from server');
        const { nonce } = await nonceRes.json();

        setStatus('<span class="spinner"></span> Please sign the message in MetaMask…', 'info');

        // 3. Sign the nonce (no gas, no transaction)
        const signature = await signer.signMessage(nonce);

        setStatus('<span class="spinner"></span> Verifying signature…', 'info');

        // 4. Send signature to PHP for verification
        const verifyRes = await fetch('/auth/verify.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ address, signature }),
        });

        const result = await verifyRes.json();

        if (!verifyRes.ok || !result.success) {
          throw new Error(result.error || 'Verification failed');
        }

        // 5. Success — redirect to dashboard
        setStatus(
          `Signed in as <div class="address-badge">✓ ${shortAddr(address)}</div>`,
          'success'
        );

        setTimeout(() => { window.location.href = '/dashboard.php'; }, 1200);

      } catch (err) {
        const msg = err.code === 4001
          ? 'You rejected the signature request.'
          : (err.message || 'Something went wrong');
        setStatus(msg, 'error');
        setLoading('btn-metamask', false);
      }
    }

    function shortAddr(addr) {
      return addr.slice(0, 6) + '…' + addr.slice(-4);
    }
  </script>
</body>
</html>
