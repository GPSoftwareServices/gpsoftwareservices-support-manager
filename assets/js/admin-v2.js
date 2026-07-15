(function(){
  document.addEventListener('DOMContentLoaded', function(){
    document.querySelectorAll('.gpsuma-stat-card, .gpsuma-card, .gpsuma-box').forEach(function(el){ el.classList.add('gpsuma-v2-enter'); });
    var search=document.querySelector('.gpsuma-dashboard-search input[type="search"]');
    if(search){ search.setAttribute('autocomplete','off'); }
  });
})();


/* Page-specific behaviour moved from inline scripts for WordPress.org compliance. */

/* admin/interventi.php */
document.addEventListener('DOMContentLoaded',function(){const s=document.getElementById('gpsuma-intervento-cliente');const labels=document.querySelectorAll('.gpsuma-device-checks label');const ps=document.getElementById('gpsuma-intervento-pacchetto');const ts=document.getElementById('gpsuma-ticket-id');function f(){const v=s.value;labels.forEach(l=>{const ok=v&&l.dataset.cliente===v;l.style.display=ok?'block':'none';if(!ok)l.querySelector('input').checked=false;});if(ts){Array.from(ts.options).forEach((o,n)=>{if(n===0)return;const ok=v&&o.dataset.cliente===v;o.hidden=!ok;o.disabled=!ok;});if(ts.selectedOptions.length&&ts.selectedOptions[0].disabled)ts.value='';}if(ps){Array.from(ps.options).forEach((o,n)=>{if(n===0)return;const ok=v&&o.dataset.cliente===v;o.hidden=!ok;o.disabled=!ok;});if(ps.selectedOptions.length&&ps.selectedOptions[0].disabled)ps.value='';}}s.addEventListener('change',f);f();});
function gatCalcTotal(){const vals=[...document.querySelectorAll('.gpsuma-cost')].map(e=>parseFloat(e.value)||0);const sub=Math.max(0,vals[0]+vals[1]+vals[2]-vals[3]);const iva=parseFloat(document.getElementById('gpsuma-iva')?.value)||0;const tot=sub+(sub*iva/100);const out=document.getElementById('gpsuma-total-preview');if(out)out.textContent=new Intl.NumberFormat('it-IT',{style:'currency',currency:'EUR'}).format(tot);}document.querySelectorAll('.gpsuma-cost,#gpsuma-iva').forEach(e=>e.addEventListener('input',gatCalcTotal));gatCalcTotal();
const canvas=document.getElementById('gpsuma-signature'), input=document.getElementById('gpsuma-firma-input'), clear=document.getElementById('gpsuma-firma-pulisci');if(canvas){const ctx=canvas.getContext('2d');ctx.lineWidth=2;ctx.lineCap='round';let draw=false;function pos(e){const r=canvas.getBoundingClientRect(),p=e.touches?e.touches[0]:e;return {x:(p.clientX-r.left)*(canvas.width/r.width),y:(p.clientY-r.top)*(canvas.height/r.height)}}function start(e){draw=true;const p=pos(e);ctx.beginPath();ctx.moveTo(p.x,p.y);e.preventDefault()}function move(e){if(!draw)return;const p=pos(e);ctx.lineTo(p.x,p.y);ctx.stroke();input.value=canvas.toDataURL('image/png');e.preventDefault()}function stop(){draw=false}canvas.addEventListener('mousedown',start);canvas.addEventListener('mousemove',move);window.addEventListener('mouseup',stop);canvas.addEventListener('touchstart',start,{passive:false});canvas.addEventListener('touchmove',move,{passive:false});canvas.addEventListener('touchend',stop);clear.addEventListener('click',()=>{ctx.clearRect(0,0,canvas.width,canvas.height);input.value='';});}

/* admin/pacchetti.php */
document.addEventListener('DOMContentLoaded',function(){var c=document.getElementById('gpsuma-interventi-illimitati'),n=document.getElementById('gpsuma-interventi-inclusi');if(!c||!n)return;function sync(){n.disabled=c.checked;if(c.checked)n.removeAttribute('required');else n.setAttribute('required','required');}c.addEventListener('change',sync);sync();});

/* admin/scadenze.php */
document.addEventListener('DOMContentLoaded',function(){const c=document.getElementById('gpsuma-scadenza-cliente'),d=document.getElementById('gpsuma-scadenza-dispositivo');function f(){const v=c.value;[...d.options].forEach((o,i)=>{if(i===0)return;o.hidden=!!v&&o.dataset.cliente!==v;if(o.hidden&&o.selected)d.value='';});}c.addEventListener('change',f);f();});
