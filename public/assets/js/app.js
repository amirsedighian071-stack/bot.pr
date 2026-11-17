document.addEventListener('DOMContentLoaded',()=>{
  // Sidebar hamburger + overlay
  const toggle=document.getElementById('sidebarToggle');
  const sidebar=document.getElementById('sidebar');
  const overlay=document.getElementById('overlay');
  if(toggle && sidebar){
    toggle.addEventListener('click',()=>{
      sidebar.classList.toggle('show');
      if(overlay) overlay.classList.toggle('show');
    });
  }
  if(overlay){
    overlay.addEventListener('click',()=>{
      sidebar.classList.remove('show');
      overlay.classList.remove('show');
    });
  }

  // Auto hide alerts after 4s
  setTimeout(()=>{ document.querySelectorAll('.alert-auto').forEach(a=>{ a.style.transition='opacity 0.4s'; a.style.opacity='0'; setTimeout(()=>a.remove(),400); }); },4000);

  // Intercept data-confirm to use custom modal
  document.querySelectorAll('[data-confirm]').forEach(el=>{
    el.addEventListener('click',e=>{
      e.preventDefault();
      const msg = el.getAttribute('data-confirm') || 'آیا مطمئن هستید؟';
      const href = el.getAttribute('href');
      const isFormBtn = el.tagName === 'BUTTON' && el.closest('form');
      showConfirm('تأیید عملیات', msg, ()=>{
        if(href && href !== '#') window.location.href = href;
        else if(isFormBtn) el.closest('form').submit();
        else if(el.dataset.confirmAction) handleConfirmAction(el.dataset.confirmAction, el);
      });
    });
  });

  // Handle data-custom-confirm (new)
  document.querySelectorAll('[data-custom-confirm]').forEach(el=>{
    el.addEventListener('click',e=>{
      e.preventDefault();
      const msg = el.getAttribute('data-custom-confirm');
      const action = el.getAttribute('data-confirm-action');
      const href = el.getAttribute('href');
      showConfirm('تأیید', msg, ()=>{
        if(action && window[action]) window[action]();
        else if(href) window.location.href = href;
        else if(el.closest('form')) el.closest('form').submit();
      });
    });
  });

  // ESC to close modal
  document.addEventListener('keydown',e=>{
    if(e.key==='Escape') closeModal();
  });
});

// Custom Modal System - Dark Neon (replaces alert/confirm)
let _confirmCallback = null;
function showModal(title, body, type='info'){
  const modal = document.getElementById('customModal');
  const header = document.getElementById('customModalHeader');
  const titleEl = document.getElementById('customModalTitle');
  const bodyEl = document.getElementById('customModalBody');
  const confirmBtn = document.getElementById('customModalConfirmBtn');
  if(!modal) return alert(body); // fallback

  // title with icon per type
  let icon = 'bi-info-circle';
  if(type==='success') icon='bi-check-circle-fill text-success';
  else if(type==='error') icon='bi-x-circle-fill text-danger';
  else if(type==='warning') icon='bi-exclamation-triangle-fill text-warning';

  titleEl.innerHTML = `<i class="bi ${icon}"></i> ${title}`;
  bodyEl.innerHTML = body;

  header.className = 'custom-modal-header ' + type;
  confirmBtn.style.display = 'none';
  _confirmCallback = null;

  modal.classList.add('show');
  modal.setAttribute('aria-hidden','false');
  document.body.style.overflow='hidden';
}
function showConfirm(title, body, onConfirm){
  const modal = document.getElementById('customModal');
  const header = document.getElementById('customModalHeader');
  const titleEl = document.getElementById('customModalTitle');
  const bodyEl = document.getElementById('customModalBody');
  const confirmBtn = document.getElementById('customModalConfirmBtn');
  if(!modal) return confirm(body) && onConfirm && onConfirm();

  titleEl.innerHTML = `<i class="bi bi-question-circle text-warning"></i> ${title}`;
  // allow html
  bodyEl.innerHTML = typeof body === 'string' && body.includes('<') ? body : `<p>${body}</p>`;
  header.className = 'custom-modal-header warning';
  confirmBtn.style.display = '';
  confirmBtn.onclick = ()=>{ closeModal(); if(onConfirm) onConfirm(); };
  _confirmCallback = onConfirm;
  modal.classList.add('show');
  modal.setAttribute('aria-hidden','false');
  document.body.style.overflow='hidden';
}
function closeModal(){
  const modal = document.getElementById('customModal');
  if(!modal) return;
  modal.classList.remove('show');
  modal.setAttribute('aria-hidden','true');
  document.body.style.overflow='';
  _confirmCallback = null;
}
// helpers expected by legacy code
window.copyText = (t)=>{
  navigator.clipboard.writeText(t).then(()=>{
    showModal('کپی شد', `<div class="text-center"><i class="bi bi-clipboard-check fs-3 text-success"></i><div class="mt-2 small" style="word-break:break-all"><code>${t}</code></div><div class="small text-muted mt-2">در کلیپ‌بورد ذخیره شد</div></div>`, 'success');
  }).catch(()=>{
    showModal('کپی', `<code style="word-break:break-all">${t}</code>`, 'info');
  });
};
window.showModal = showModal;
window.showConfirm = showConfirm;
window.closeModal = closeModal;

function previewQR(url){
  showModal('QR کد', `<div class="text-center"><img src="https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${encodeURIComponent(url)}" style="max-width:260px;border-radius:12px;border:1px solid rgba(139,92,246,0.2)"><div class="small text-muted mt-2" style="word-break:break-all">${url}</div></div>`, 'info');
}

// For deleteWebhook custom action
window.deleteWebhook = ()=>{ document.getElementById('deleteWebhookForm')?.submit(); };

// Auto-close modal on backdrop click is already wired via onclick on backdrop
