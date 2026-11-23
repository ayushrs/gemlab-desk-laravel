const $ = (s) => document.querySelector(s);
const formData = (form) => Object.fromEntries(new FormData(form).entries());
const esc = (value) => String(value ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const csrf = document.querySelector('meta[name=csrf-token]')?.content || '';
const pretty = (value) => String(value || '').replace(/\b\w/g, c => c.toUpperCase());

$('#request-form').addEventListener('submit', async (event) => {
  event.preventDefault(); const button = event.submitter; button.disabled = true; button.textContent = 'Submitting…';
  const payload = formData(event.currentTarget);
  try {
    const response = await fetch('/api/public/request', {method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf,'Accept':'application/json'},body:JSON.stringify(payload)});
    const data = await response.json(); if (!response.ok) throw new Error(data.error || 'Could not submit request.');
    $('#request-result').hidden = false;
    $('#request-result').innerHTML = `<div class="success-mark">✓</div><p class="eyebrow">REQUEST RECEIVED</p><h3>Your request is in the system.</h3><p>${esc(data.message)}</p><div class="reference-row"><span>Order number</span><strong>${esc(data.order_no)}</strong><button type="button" class="copy-btn" data-copy="${esc(data.order_no)}">Copy</button></div><div class="reference-row"><span>Sample reference</span><strong>${esc(data.sample_no)}</strong></div><p class="small-muted">Keep your order number and use your registered phone number in Track your submission.</p>`;
    $('#request-result').scrollIntoView({behavior:'smooth',block:'nearest'}); event.currentTarget.reset();
  } catch (error) { $('#request-result').hidden = false; $('#request-result').innerHTML = `<p class="error-text">${esc(error.message)}</p>`; }
  finally { button.disabled = false; button.innerHTML = 'Submit testing request <span>→</span>'; }
});

document.addEventListener('click', async (event) => {
  const button = event.target.closest('[data-copy]'); if (!button) return;
  try { await navigator.clipboard.writeText(button.dataset.copy); button.textContent = 'Copied'; } catch { button.textContent = button.dataset.copy; }
});

$('#track-form').addEventListener('submit', async (event) => {
  event.preventDefault(); const button = event.submitter; button.disabled = true; button.textContent = 'Checking…';
  try {
    const response = await fetch('/api/public/track', {method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf,'Accept':'application/json'},body:JSON.stringify(formData(event.currentTarget))});
    const data = await response.json(); if (!response.ok) throw new Error(data.error || 'Unable to find this order.');
    const order = data.order;
    const statusClass = order.status.toLowerCase().replace(/[^a-z]+/g,'-');
    const samples = data.samples.map(sample => `<article class="sample-result"><div><b>${esc(sample.sample_no)}</b><span>${esc(sample.description)}</span></div><span class="status-pill">${esc(pretty(sample.status))}</span>${sample.report_no ? `<a class="report-link" href="/public/reports/${encodeURIComponent(sample.report_no)}.pdf" target="_blank" rel="noopener">Download report ↗</a>` : ''}</article>`).join('');
    $('#track-result').hidden = false;
    $('#track-result').innerHTML = `<div class="track-top"><div><p class="eyebrow">ORDER ${esc(order.order_no)}</p><h3>${esc(order.service)}</h3><p class="small-muted">Submitted ${esc(new Date(order.created_at).toLocaleDateString())}</p></div><span class="status-pill ${esc(statusClass)}">${esc(pretty(order.status))}</span></div><div class="track-meta"><div><small>Customer</small><b>${esc(order.customer)}</b></div><div><small>Payment</small><b>${esc(pretty(order.payment_status))}</b></div></div><h4>Sample updates</h4>${samples || '<p class="small-muted">No sample details are available yet.</p>'}<p class="small-muted privacy-note">For privacy, order details are shown only when both the order number and registered phone number match.</p>`;
    $('#track-result').scrollIntoView({behavior:'smooth',block:'nearest'});
  } catch (error) { $('#track-result').hidden = false; $('#track-result').innerHTML = `<p class="error-text">${esc(error.message)}</p>`; }
  finally { button.disabled = false; button.innerHTML = 'Track order <span>→</span>'; }
});
