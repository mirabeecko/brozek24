async function updateStatus() {
  const statusBox = document.getElementById('status-indicator');
  const statusText = document.getElementById('status-text');
  const messageBox = document.getElementById('availability-message');

  try {
    const response = await fetch('/status.json');
    const data = await response.json();

    if (data.status === 'GREEN') {
      statusBox.className = 'status-box status-green';
      statusText.innerText = 'LIVE: AVAILABLE NOW';
      messageBox.innerHTML = `
        <div style="color: var(--accent);">"VOLEJTE I VE 3 RÁNO. DEADLINE ZAČÍNÁ TEĎ."</div>
        <p style="font-size: 1rem; color: #888; margin-top: 1rem;">Miroslav Brožek je připraven vyřešit váš problém okamžitě.</p>
      `;
    } else {
      statusBox.className = 'status-box status-red';
      statusText.innerText = 'BUSY: ON DEADLINE';
      messageBox.innerHTML = `
        <div style="color: var(--accent-red);">"RESPEKTUJI DEADLINE KLIENTA. NECHTE ZPRÁVU, OZVU SE HNED."</div>
        <p style="font-size: 1rem; color: #888; margin-top: 1rem;">Probíhá kritická práce. Vaše zpráva bude první v řadě.</p>
      `;
    }
  } catch (error) {
    console.error('Status fetch error:', error);
    statusText.innerText = 'CONNECTION ERROR';
  }
}

// Initial fetch
updateStatus();

// Polling every 30s
setInterval(updateStatus, 30000);

// Simple interaction effect: Squint Test optimization
document.querySelectorAll('.service-card').forEach(card => {
  card.addEventListener('mouseenter', () => {
    card.style.transform = 'translateY(-5px)';
  });
  card.addEventListener('mouseleave', () => {
    card.style.transform = 'translateY(0)';
  });
});