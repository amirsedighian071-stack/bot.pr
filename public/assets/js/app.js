document.addEventListener('DOMContentLoaded',()=>{
  // ===== Theme Toggle (Task 7) =====
  const themeBtn = document.getElementById('themeToggle');
  const themeIcon = document.getElementById('themeIcon');
  function applyTheme(t){
    if(t==='light'){
      document.body.classList.add('light-mode');
      if(themeIcon){ themeIcon.className='bi bi-sun-fill'; }
    } else {
      document.body.classList.remove('light-mode');
      if(themeIcon){ themeIcon.className='bi bi-moon-stars'; }
    }
  }
  const saved = localStorage.getItem('admin_theme') || 'dark';
  applyTheme(saved);
  if(themeBtn){
    themeBtn.addEventListener('click',()=>{
      const isLight = document.body.classList.contains('light-mode');
      const next = isLight ? 'dark' : 'light';
      localStorage.setItem('admin_theme', next);
      applyTheme(next);
    });
  }

  // ===== Sidebar hamburger + overlay (Task 3) =====
  const toggle = document.getElementById('sidebarToggle');
  const sidebar = document.getElementById('sidebar');
  const overlay = document.getElementById('overlay');
  function openSidebar(){
    if(sidebar) sidebar.classList.add('show');
    if(overlay) overlay.classList.add('show');
    document.body.style.overflow='hidden';
  }
  function closeSidebar(){
    if(sidebar) sidebar.classList.remove('show');
    if(overlay) overlay.classList.remove('show');
    document.body.style.overflow='';
  }
  if(toggle && sidebar){
    toggle.addEventListener('click',(e)=>{
      e.preventDefault(); e.stopPropagation();
      if(sidebar.classList.contains('show')) closeSidebar();
      else openSidebar();
    });
  }
  if(overlay){
    overlay.addEventListener('click', closeSidebar);
  }
  // Close sidebar when clicking a nav link on mobile
  document.querySelectorAll('#sidebar .nav-link').forEach(a=>{
    a.addEventListener('click',()=>{
      if(window.innerWidth < 992) closeSidebar();
    });
  });
  // ESC closes sidebar
  document.addEventListener('keydown',e=>{
    if(e.key==='Escape'){
      if(sidebar && sidebar.classList.contains('show')) closeSidebar();
      else closeBootstrapModal();
    }
  });

  // Auto hide alerts after 4s
  setTimeout(()=>{ document.querySelectorAll('.alert-auto').forEach(a=>{ a.style.transition='opacity 0.4s'; a.style.opacity='0'; setTimeout(()=>a.remove(),400); }); },4000);

  // Intercept data-confirm to use bootstrap modal
  document.querySelectorAll('[data-confirm]').forEach(el=>{
    el.addEventListener('click',e=>{
      e.preventDefault();
      const msg = el.getAttribute('data-confirm') || 'آیا مطمئن هستید؟';
      const href = el.getAttribute('href');
      const isFormBtn = el.tagName === 'BUTTON' && el.closest('form');
      showConfirm('تأیید عملیات', msg, ()=>{
        if(href && href !== '#' && href !== window.location.href) window.location.href = href;
        else if(isFormBtn) el.closest('form').submit();
        else if(el.dataset.confirmAction) handleConfirmAction(el.dataset.confirmAction, el);
      });
    });
  });
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
});

// ===== Bootstrap Modal helper (replaces custom-modal, Task 4 & 5) =====
let _bsModalInstance = null;
function ensureBsModal(){
  let el = document.getElementById('bsDynamicModal');
  if(el) return el;
  const html = `<div class="modal fade" id="bsDynamicModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content" style="background:#12121a"><div class="modal-header" id="bsModalHeader"><h5 class="modal-title" id="bsModalTitle"></h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div><div class="modal-body" id="bsModalBody"></div><div class="modal-footer" id="bsModalFooter"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">بستن</button><button type="button" class="btn btn-primary" id="bsModalConfirmBtn" style="display:none">تأیید</button></div></div></div></div>`;
  document.body.insertAdjacentHTML('beforeend', html);
  return document.getElementById('bsDynamicModal');
}
function openBootstrapModal(){
  const el = ensureBsModal();
  if(window.bootstrap && window.bootstrap.Modal){
    _bsModalInstance = new bootstrap.Modal(el, {backdrop:true});
    _bsModalInstance.show();
    el.addEventListener('hidden.bs.modal',()=>{ el.remove(); _bsModalInstance=null; }, {once:true});
  } else {
    // fallback: simple display
    el.style.display='block'; el.classList.add('show'); el.style.background='rgba(0,0,0,0.6)';
  }
}
function closeBootstrapModal(){
  const el = document.getElementById('bsDynamicModal');
  if(_bsModalInstance){ _bsModalInstance.hide(); }
  else if(el){ el.remove(); }
}
function showModal(title, body, type='info'){
  const el = ensureBsModal();
  const titleEl = document.getElementById('bsModalTitle');
  const bodyEl = document.getElementById('bsModalBody');
  const confirmBtn = document.getElementById('bsModalConfirmBtn');
  const header = document.getElementById('bsModalHeader');
  let icon='bi-info-circle text-primary';
  if(type==='success') icon='bi-check-circle-fill text-success';
  else if(type==='error') icon='bi-x-circle-fill text-danger';
  else if(type==='warning') icon='bi-exclamation-triangle-fill text-warning';
  titleEl.innerHTML = `<i class="bi ${icon} me-1"></i> ${title}`;
  bodyEl.innerHTML = body;
  if(header){
    header.className='modal-header';
    if(type==='success') header.style.borderBottomColor='rgba(16,185,129,0.3)';
    else if(type==='error') header.style.borderBottomColor='rgba(239,68,68,0.3)';
    else header.style.borderBottomColor='rgba(139,92,246,0.12)';
  }
  confirmBtn.style.display='none';
  confirmBtn.onclick=null;
  openBootstrapModal();
}
function showConfirm(title, body, onConfirm){
  const el = ensureBsModal();
  const titleEl = document.getElementById('bsModalTitle');
  const bodyEl = document.getElementById('bsModalBody');
  const confirmBtn = document.getElementById('bsModalConfirmBtn');
  const header = document.getElementById('bsModalHeader');
  titleEl.innerHTML = `<i class="bi bi-question-circle text-warning me-1"></i> ${title}`;
  bodyEl.innerHTML = typeof body === 'string' && body.includes('<') ? body : `<p>${body}</p>`;
  if(header) header.style.borderBottomColor='rgba(245,158,11,0.3)';
  confirmBtn.style.display='';
  confirmBtn.textContent='تأیید';
  confirmBtn.onclick = ()=>{ closeBootstrapModal(); if(onConfirm) onConfirm(); };
  openBootstrapModal();
}
function closeModal(){ closeBootstrapModal(); }

// legacy alias
window.showModal = showModal;
window.showConfirm = showConfirm;
window.closeModal = closeModal;

window.copyText = (t)=>{
  navigator.clipboard.writeText(t).then(()=>{
    showModal('کپی شد', `<div class="text-center"><i class="bi bi-clipboard-check fs-3 text-success"></i><div class="mt-2 small" style="word-break:break-all"><code>${t}</code></div><div class="small text-muted mt-2">در کلیپ‌بورد ذخیره شد</div></div>`, 'success');
  }).catch(()=>{
    showModal('کپی', `<code style="word-break:break-all">${t}</code>`, 'info');
  });
};
function previewQR(url){
  showModal('QR کد', `<div class="text-center"><img src="https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${encodeURIComponent(url)}" style="max-width:260px;border-radius:12px;border:1px solid rgba(139,92,246,0.2)"><div class="small text-muted mt-2" style="word-break:break-all">${url}</div></div>`, 'info');
}
window.previewQR = previewQR;
window.deleteWebhook = ()=>{ document.getElementById('deleteWebhookForm')?.submit(); };
