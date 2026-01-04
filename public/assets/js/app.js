document.addEventListener('DOMContentLoaded',function(){
  var input=document.getElementById('search-input');
  if(!input) return;
  var box=document.getElementById('suggestions');
  var timer=null;
  input.addEventListener('input',function(){
    clearTimeout(timer);
    var q=input.value.trim();
    if(!q){box.innerHTML='';return;}
    timer=setTimeout(function(){
      fetch('/api/suggest?q='+encodeURIComponent(q)).then(function(r){return r.json()}).then(function(items){
        box.innerHTML='';
        items.forEach(function(it){
          var d=document.createElement('div');
          d.textContent=it.name+' ('+it.part_code+')';
          d.addEventListener('click',function(){input.value=it.part_code;box.innerHTML='';});
          box.appendChild(d);
        });
      }).catch(function(){});
    },200);
  });
});
