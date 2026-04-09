 
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

        // 2. Get nonce
        const nonceRes = await fetch(`./auth/nonce.php?address=${address}`);
        const { nonce, message } = await nonceRes.json();

        // 3. Sign EXACT message
        const signature = await signer.signMessage(message);

        // 4. Send to backend
        const verifyRes = await fetch('./auth/verify.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ address, signature, message }),
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

        setTimeout(() => { window.location.href = './dashboard'; }, 1200);

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
   