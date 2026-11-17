document.addEventListener('DOMContentLoaded',()=>{
  const toggle=document.getElementById('sidebarToggle');
  const sidebar=document.getElementById('sidebar');
  const overlay=document.getElementById('overlay');
  if(toggle){
    toggle.addEventListener('click',()=>{
      sidebar.classList.toggle('show');
      if(overlay) overlay.classList.toggle('show');
    });
  }
  if(overlay) overlay.addEventListener('click',()=>{
    sidebar.classList.remove('show');
    overlay.classList.remove('show');
  });
  // auto hide alerts
  setTimeout(()=>{ document.querySelectorAll('.alert-auto').forEach(a=>a.remove()); },4000);

  // copy helper
  window.copyText=(t)=>{
    navigator.clipboard.writeText(t).then(()=> alert('کپی شد: '+t));
  };

  // confirm deletes
  document.querySelectorAll('[data-confirm]').forEach(el=>{
    el.addEventListener('click',e=>{
      if(!confirm(el.dataset.confirm)) e.preventDefault();
    });
  });
});
function previewQR(url){
  window.open('https://api.qrserver.com/v1/create-qr-code/?size=400x400&data='+encodeURIComponent(url),'_blank');
}
