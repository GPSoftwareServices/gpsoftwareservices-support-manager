(function(){
'use strict';
function init(){
 var printButton=document.getElementById('gpsuma-print-report');
 if(printButton){printButton.addEventListener('click',function(){window.print();});}
 if(document.body && document.body.getAttribute('data-gpsuma-auto-print')==='1'){
  window.setTimeout(function(){window.print();},350);
 }
}
if(document.readyState==='loading'){document.addEventListener('DOMContentLoaded',init);}else{init();}
})();
