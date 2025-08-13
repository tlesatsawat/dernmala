/*
 * Staff interface JS
 * Displays table statuses, notifications and allows payment processing.
 */
document.addEventListener('DOMContentLoaded', () => {
  const tableListEl = document.getElementById('table-list');
  const modalContainer = document.getElementById('modal-container');
  let lastNotifId = 0;

  function renderTables(tables) {
    tableListEl.innerHTML = '';
    tables.forEach(tbl => {
      const row = document.createElement('div');
      row.className = 'table-row';
      row.dataset.number = tbl.number;
      let badgeClass = '';
      let badgeText = '';
      if (tbl.status === 'NEED_STAFF') {
        badgeClass = 'badge-call'; badgeText = 'เรียกพนักงาน';
      } else if (tbl.status === 'PAYING') {
        badgeClass = 'badge-paying'; badgeText = 'รอชำระ';
      } else if (tbl.status === 'PAID') {
        badgeClass = 'badge-paid'; badgeText = 'ชำระแล้ว';
      } else if (tbl.status === 'ORDERING') {
        badgeClass = 'badge-ordering'; badgeText = 'กำลังสั่ง';
      }
      row.innerHTML = `โต๊ะ ${tbl.number} <span class="badge ${badgeClass}">${badgeText}</span>`;
      row.addEventListener('click', () => openOrderModal(tbl.number));
      tableListEl.appendChild(row);
    });
  }

  function fetchStatus() {
    fetch('api/fetch_status.php?role=staff&last_id=' + lastNotifId)
      .then(resp => resp.json())
      .then(data => {
        if (data.tables) {
          renderTables(data.tables);
        }
        if (data.notifications && data.notifications.length > 0) {
          data.notifications.forEach(notif => {
            if (notif.id > lastNotifId) lastNotifId = notif.id;
            // Display toast or simple alert for new notifications (call staff)
            if (notif.type === 'CALL_STAFF') {
              const note = `โต๊ะ ${notif.table_number} เรียกพนักงาน`;
              showToast(note);
            }
            if (notif.type === 'NEW_ORDER') {
              const note = `มีออเดอร์ใหม่ที่โต๊ะ ${notif.table_number}`;
              showToast(note);
            }
            if (notif.type === 'PAID') {
              const note = `โต๊ะ ${notif.table_number} ชำระเงินแล้ว`;
              showToast(note);
            }
          });
        }
      })
      .catch(err => console.error(err));
  }

  function showToast(message) {
    const toast = document.createElement('div');
    toast.className = 'toast';
    toast.textContent = message;
    document.body.appendChild(toast);
    setTimeout(() => {
      toast.classList.add('show');
      setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => document.body.removeChild(toast), 500);
      }, 3000);
    }, 100);
  }

  // Open modal to show order details and payment options
  function openOrderModal(tableNumber) {
    // Fetch order details
    fetch(`api/order.php?action=get&table=${tableNumber}`)
      .then(resp => resp.json())
      .then(data => {
        const order = data.order;
        const overlay = document.createElement('div');
        overlay.className = 'modal-overlay';
        const modal = document.createElement('div');
        modal.className = 'modal';
        const header = document.createElement('div');
        header.className = 'modal-header';
        const title = document.createElement('h3');
        title.textContent = `โต๊ะ ${tableNumber}`;
        const closeBtn = document.createElement('button');
        closeBtn.className = 'btn btn-outline';
        closeBtn.textContent = 'X';
        closeBtn.addEventListener('click', () => closeModal(overlay));
        header.appendChild(title);
        header.appendChild(closeBtn);
        const body = document.createElement('div');
        body.className = 'modal-body';
        if (!order) {
          body.textContent = 'ยังไม่มีออเดอร์ที่เปิด';
        } else {
          let html = '';
          order.items.forEach(it => {
            html += `<div>${it.qty}× ${it.name} – ฿${(it.price * it.qty).toFixed(2)}</div>`;
            if (it.modifications && Object.keys(it.modifications).length > 0) {
              html += '<small> (' + Object.keys(it.modifications).map(k => `${k}:${it.modifications[k]}`).join(', ') + ')</small>';
            }
          });
          html += `<hr><strong>รวม: ฿${order.total.toFixed(2)}</strong>`;
          body.innerHTML = html;
        }
        const footer = document.createElement('div');
        footer.className = 'modal-footer';
        if (order) {
          const cashBtn = document.createElement('button');
          cashBtn.className = 'btn btn-primary';
          cashBtn.textContent = 'เงินสด';
          cashBtn.addEventListener('click', () => {
            processPayment(order.order_id, 'cash');
            closeModal(overlay);
          });
          const promptBtn = document.createElement('button');
          promptBtn.className = 'btn btn-outline';
          promptBtn.textContent = 'PromptPay';
          promptBtn.addEventListener('click', () => {
            // Create payment and show QR
            processPayment(order.order_id, 'promptpay');
            closeModal(overlay);
          });
          footer.appendChild(cashBtn);
          footer.appendChild(promptBtn);
        }
        modal.appendChild(header);
        modal.appendChild(body);
        modal.appendChild(footer);
        overlay.appendChild(modal);
        document.body.appendChild(overlay);
      })
      .catch(err => console.error(err));
  }

  function closeModal(overlay) {
    if (overlay) {
      document.body.removeChild(overlay);
    }
  }

  function processPayment(orderId, method) {
    fetch('api/payment.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ order_id: orderId, method: method })
    })
    .then(resp => resp.json())
    .then(data => {
      if (!data.success) {
        alert(data.error || 'Payment error');
        return;
      }
      if (method === 'cash') {
        alert('ชำระเงินสดเรียบร้อย');
        fetchStatus();
      } else if (method === 'promptpay') {
        // Show QR modal
        showQRModal(data.qr_data, data.expires_at);
      }
    })
    .catch(err => { console.error(err); alert('Network error'); });
  }

  function showQRModal(qrData, expires) {
    const overlay = document.createElement('div');
    overlay.className = 'modal-overlay';
    const modal = document.createElement('div');
    modal.className = 'modal';
    const header = document.createElement('div');
    header.className = 'modal-header';
    const title = document.createElement('h3');
    title.textContent = 'PromptPay QR';
    const closeBtn = document.createElement('button');
    closeBtn.className = 'btn btn-outline';
    closeBtn.textContent = 'X';
    closeBtn.addEventListener('click', () => {
      closeModal(overlay);
    });
    header.appendChild(title);
    header.appendChild(closeBtn);
    const body = document.createElement('div');
    body.className = 'modal-body';
    if (qrData) {
      const img = document.createElement('img');
      // If qrData is base64 string, create data URI, else treat as URL
      if (qrData.startsWith('data:image') || qrData.startsWith('http')) {
        img.src = qrData;
      } else {
        img.src = 'data:image/png;base64,' + qrData;
      }
      img.style.width = '100%';
      body.appendChild(img);
    } else {
      body.textContent = 'ไม่สามารถสร้าง QR ได้';
    }
    if (expires) {
      const expiresDiv = document.createElement('div');
      expiresDiv.style.marginTop = '0.5rem';
      expiresDiv.textContent = 'หมดอายุ: ' + expires;
      body.appendChild(expiresDiv);
    }
    const footer = document.createElement('div');
    footer.className = 'modal-footer';
    const closeBtn2 = document.createElement('button');
    closeBtn2.className = 'btn btn-primary';
    closeBtn2.textContent = 'ปิด';
    closeBtn2.addEventListener('click', () => closeModal(overlay));
    footer.appendChild(closeBtn2);
    modal.appendChild(header);
    modal.appendChild(body);
    modal.appendChild(footer);
    overlay.appendChild(modal);
    document.body.appendChild(overlay);
  }

  // Initial load
  fetchStatus();
  setInterval(fetchStatus, 5000);
});