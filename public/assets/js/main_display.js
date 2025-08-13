/*
 * Main display JS
 * Shows a grid of all table statuses and plays voice alerts when
 * customers call staff, new orders arrive or payments are completed.
 */
document.addEventListener('DOMContentLoaded', () => {
  const gridEl = document.getElementById('table-grid');
  let lastNotifId = 0;

  /**
   * Render the table grid based on latest statuses.
   * Each item shows the table number and a coloured badge for its status.
   * @param {Array} tables
   */
  function renderTables(tables) {
    gridEl.innerHTML = '';
    tables.forEach(tbl => {
      const item = document.createElement('div');
      item.className = 'grid-item';
      let badgeClass = '';
      let badgeText = '';
      // Determine badge class and text based on status
      switch (tbl.status) {
        case 'NEED_STAFF':
          badgeClass = 'badge-call';
          badgeText = 'เรียก';
          break;
        case 'PAYING':
          badgeClass = 'badge-paying';
          badgeText = 'รอจ่าย';
          break;
        case 'PAID':
          badgeClass = 'badge-paid';
          badgeText = 'ชำระแล้ว';
          break;
        case 'ORDERING':
          badgeClass = 'badge-ordering';
          badgeText = 'สั่งอยู่';
          break;
        default:
          badgeClass = '';
          badgeText = 'ว่าง';
      }
      item.innerHTML = `โต๊ะ ${tbl.number}<br><span class="badge ${badgeClass}">${badgeText}</span>`;
      gridEl.appendChild(item);
    });
  }

  /**
   * Fetch statuses and notifications from the server.
   */
  function fetchStatus() {
    fetch('api/fetch_status.php?role=display&last_id=' + lastNotifId)
      .then(resp => resp.json())
      .then(data => {
        if (data.tables) {
          renderTables(data.tables);
        }
        if (data.notifications && data.notifications.length > 0) {
          data.notifications.forEach(notif => {
            if (notif.id > lastNotifId) lastNotifId = notif.id;
            handleNotification(notif);
          });
        }
      })
      .catch(err => console.error(err));
  }

  /**
   * Handle incoming notification: show toast and speak message.
   * @param {Object} notif
   */
  function handleNotification(notif) {
    let message = '';
    switch (notif.type) {
      case 'CALL_STAFF':
        message = `ลูกค้าโต๊ะ ${notif.table_number} เรียกพนักงาน`;
        break;
      case 'NEW_ORDER':
        message = `มีออเดอร์ใหม่ที่โต๊ะ ${notif.table_number}`;
        break;
      case 'PAID':
        message = `โต๊ะ ${notif.table_number} ชำระเงินแล้ว`;
        break;
      default:
        message = notif.message;
    }
    showToast(message);
    speak(message);
  }

  /**
   * Display a toast message at the top of the screen.
   * @param {string} message
   */
  function showToast(message) {
    const toast = document.createElement('div');
    toast.className = 'toast';
    toast.textContent = message;
    document.body.appendChild(toast);
    // Show animation
    setTimeout(() => {
      toast.classList.add('show');
      // Hide after 5 seconds
      setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => {
          if (toast.parentNode) toast.parentNode.removeChild(toast);
        }, 500);
      }, 5000);
    }, 100);
  }

  /**
   * Speak a Thai message using Web Speech API.
   * @param {string} text
   */
  function speak(text) {
    if (!('speechSynthesis' in window)) return;
    const utter = new SpeechSynthesisUtterance(text);
    utter.lang = 'th-TH';
    utter.rate = 1;
    try {
      // Cancel any ongoing speech to avoid overlap
      window.speechSynthesis.cancel();
      window.speechSynthesis.speak(utter);
    } catch (e) {
      console.error('Speech error', e);
    }
  }

  // Initial fetch and polling
  fetchStatus();
  setInterval(fetchStatus, 5000);
});