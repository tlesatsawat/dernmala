/*
 * Customer page JavaScript
 * Handles menu display, cart management, order submission and call staff.
 */

document.addEventListener('DOMContentLoaded', () => {
  // menuData is embedded by PHP as a global variable
  const menuData = window.menuData || [];
  const tableNumber = window.tableNumber || 0;
  const cart = [];

  const menuContainer = document.getElementById('menu-container');
  const cartCountSpan = document.getElementById('cart-count');
  const cartTotalSpan = document.getElementById('cart-total');
  const checkoutBtn = document.getElementById('checkout-btn');
  const callStaffBtn = document.getElementById('call-staff-btn');

  // Render menu
  function renderMenu() {
    menuContainer.innerHTML = '';
    menuData.forEach(cat => {
      const catEl = document.createElement('div');
      const h3 = document.createElement('h3');
      h3.textContent = cat.name;
      catEl.appendChild(h3);
      const grid = document.createElement('div');
      grid.className = 'menu-grid';
      cat.items.forEach(item => {
        const card = document.createElement('div');
        card.className = 'menu-card';
        const img = document.createElement('img');
        img.src = 'assets/images/' + (item.photo || 'placeholder.jpg');
        img.alt = item.name;
        const name = document.createElement('h4');
        name.textContent = item.name;
        const price = document.createElement('div');
        price.className = 'price';
        price.textContent = '฿' + item.price.toFixed(2);
        const addBtn = document.createElement('button');
        addBtn.className = 'btn btn-primary';
        addBtn.textContent = '+';
        addBtn.addEventListener('click', () => openItemModal(item));
        card.appendChild(img);
        card.appendChild(name);
        card.appendChild(price);
        card.appendChild(addBtn);
        grid.appendChild(card);
      });
      catEl.appendChild(grid);
      menuContainer.appendChild(catEl);
    });
  }

  // Update cart summary
  function updateCartSummary() {
    let count = 0;
    let total = 0;
    cart.forEach(item => {
      count += item.qty;
      total += item.qty * item.price;
    });
    cartCountSpan.textContent = count;
    cartTotalSpan.textContent = total.toFixed(2);
    checkoutBtn.disabled = cart.length === 0;
  }

  // Modal handling
  let currentModal = null;
  function openItemModal(item) {
    // Build modal content
    const overlay = document.createElement('div');
    overlay.className = 'modal-overlay';
    const modal = document.createElement('div');
    modal.className = 'modal';
    const header = document.createElement('div');
    header.className = 'modal-header';
    const title = document.createElement('h3');
    title.textContent = item.name + ' – ฿' + item.price.toFixed(2);
    const closeBtn = document.createElement('button');
    closeBtn.className = 'btn btn-outline';
    closeBtn.textContent = 'X';
    closeBtn.addEventListener('click', () => closeModal());
    header.appendChild(title);
    header.appendChild(closeBtn);
    const body = document.createElement('div');
    body.className = 'modal-body';
    // Spicy options
    const spicyLabel = document.createElement('label');
    spicyLabel.textContent = 'ระดับความเผ็ด:';
    body.appendChild(spicyLabel);
    const spicySelect = document.createElement('select');
    ['อ่อน','กลาง','จัด'].forEach(val => {
      const opt = document.createElement('option');
      opt.value = val;
      opt.textContent = val;
      spicySelect.appendChild(opt);
    });
    body.appendChild(spicySelect);
    // Sauce options
    const sauceLabel = document.createElement('label');
    sauceLabel.textContent = 'ซอส:';
    body.appendChild(document.createElement('br'));
    body.appendChild(sauceLabel);
    const sauceSelect = document.createElement('select');
    ['หม่าล่า','พริกไทย','กระเทียม'].forEach(val => {
      const opt = document.createElement('option');
      opt.value = val;
      opt.textContent = val;
      sauceSelect.appendChild(opt);
    });
    body.appendChild(sauceSelect);
    // Note
    const noteLabel = document.createElement('label');
    noteLabel.textContent = 'โน้ต:';
    body.appendChild(document.createElement('br'));
    body.appendChild(noteLabel);
    const noteInput = document.createElement('input');
    noteInput.type = 'text';
    noteInput.placeholder = 'หมายเหตุ (ไม่จำเป็น)';
    noteInput.style.width = '100%';
    body.appendChild(noteInput);
    // Quantity
    const qtyLabel = document.createElement('label');
    qtyLabel.textContent = 'จำนวน:';
    body.appendChild(document.createElement('br'));
    body.appendChild(qtyLabel);
    const qtyInput = document.createElement('input');
    qtyInput.type = 'number';
    qtyInput.min = 1;
    qtyInput.value = 1;
    qtyInput.style.width = '60px';
    body.appendChild(qtyInput);
    const footer = document.createElement('div');
    footer.className = 'modal-footer';
    const cancelBtn = document.createElement('button');
    cancelBtn.className = 'btn btn-outline';
    cancelBtn.textContent = 'ยกเลิก';
    cancelBtn.addEventListener('click', () => closeModal());
    const addCartBtn = document.createElement('button');
    addCartBtn.className = 'btn btn-primary';
    addCartBtn.textContent = 'ใส่ตะกร้า';
    addCartBtn.addEventListener('click', () => {
      const mods = {
        spicy: spicySelect.value,
        sauce: sauceSelect.value,
      };
      if (noteInput.value.trim() !== '') mods.note = noteInput.value.trim();
      const qty = parseInt(qtyInput.value) || 1;
      cart.push({ id: item.id, name: item.name, price: item.price, qty: qty, mods: mods });
      updateCartSummary();
      closeModal();
    });
    footer.appendChild(cancelBtn);
    footer.appendChild(addCartBtn);
    modal.appendChild(header);
    modal.appendChild(body);
    modal.appendChild(footer);
    overlay.appendChild(modal);
    document.body.appendChild(overlay);
    currentModal = overlay;
  }

  function closeModal() {
    if (currentModal) {
      document.body.removeChild(currentModal);
      currentModal = null;
    }
  }

  // Submit order
  checkoutBtn.addEventListener('click', () => {
    if (cart.length === 0) return;
    const payload = {
      table: tableNumber,
      items: cart.map(it => ({ id: it.id, qty: it.qty, mods: it.mods }))
    };
    fetch('api/order.php?action=create', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    })
    .then(resp => resp.json())
    .then(data => {
      if (data.success) {
        alert('ส่งออเดอร์เรียบร้อย!');
        cart.length = 0;
        updateCartSummary();
      } else {
        alert('เกิดข้อผิดพลาด: ' + (data.error || 'ไม่สามารถส่งออเดอร์ได้'));
      }
    })
    .catch(err => {
      console.error(err);
      alert('Network error');
    });
  });

  // Call staff
  callStaffBtn.addEventListener('click', () => {
    fetch('api/call_staff.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ table: tableNumber })
    })
    .then(resp => resp.json())
    .then(data => {
      alert('เรียกพนักงานแล้ว');
    })
    .catch(() => alert('Network error'));
  });

  renderMenu();
  updateCartSummary();
});