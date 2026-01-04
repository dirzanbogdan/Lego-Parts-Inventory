<?php $title = 'Configurare'; ?>
<h2>Configurare aplicatie</h2>
<?php if (!empty($_GET['ok'])): ?>
  <div class="card">Operatie finalizata: <?php echo htmlspecialchars($_GET['ok']); ?></div>
<?php endif; ?>
<h3>Importa culori</h3>
<form method="post" action="/admin/config/seed_colors">
  <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
  <button type="submit">Importa culori</button>
  <p>Culori standard din BrickLink.</p>
</form>
<h3>Importa piese</h3>
<form method="post" action="/admin/config/scrape_parts" id="form-parts">
  <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
  <textarea name="codes" placeholder="Listeaza codurile BrickLink pe linii separate (ex: 3001)"></textarea>
  <button type="submit">Importa piese</button>
  <div id="parts-progress" class="card" style="display:none"></div>
</form>
<h3>Importa seturi</h3>
<form method="post" action="/admin/config/scrape_sets" id="form-sets">
  <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
  <textarea name="codes" placeholder="Listeaza codurile BrickLink pe linii separate (ex: 1234-1)"></textarea>
  <button type="submit">Importa seturi</button>
  <div id="sets-progress" class="card" style="display:none"></div>
</form>
<script>
document.addEventListener('DOMContentLoaded',function(){
  function process(formId, endpoint, boxId){
    var form=document.getElementById(formId);
    if(!form) return;
    var box=document.getElementById(boxId);
    form.addEventListener('submit',function(e){
      e.preventDefault();
      var codes=(form.querySelector('textarea[name=codes]').value||'').split(/\r?\n/).map(function(s){return s.trim()}).filter(Boolean);
      if(!codes.length) return;
      box.style.display='block';
      var i=0;
      box.textContent='Pornit... (0/'+codes.length+')';
      function step(){
        if(i>=codes.length){box.textContent='Finalizat ('+codes.length+'/'+codes.length+')';return;}
        var code=codes[i++];
        box.textContent='Procesez '+code+' ('+i+'/'+codes.length+')';
        var fd=new FormData();
        fd.append('csrf','<?php echo htmlspecialchars($csrf); ?>');
        fd.append('code',code);
        fetch(endpoint,{method:'POST',body:fd}).then(function(r){return r.text()}).then(function(){
          var delay=3000+Math.floor(Math.random()*7000);
          setTimeout(step,delay);
        }).catch(function(){
          var delay=3000+Math.floor(Math.random()*7000);
          setTimeout(step,delay);
        });
      }
      step();
    });
  }
  process('form-parts','/admin/config/scrape_parts_one','parts-progress');
  process('form-sets','/admin/config/scrape_sets_one','sets-progress');
});
</script>
