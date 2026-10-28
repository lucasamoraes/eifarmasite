'use strict';
const data=JSON.parse(document.getElementById('site-data').textContent);
const $=selector=>document.querySelector(selector);
const icons={bag:'<path d="M2 3h3l3 13h11l3-9H6"/><circle cx="9" cy="21" r="1"/><circle cx="18" cy="21" r="1"/>',bottle:'<path d="M9 3h6v4l3 3v11H6V10l3-3V3Z"/><path d="M9 3h8M6 14h12"/>',arrow:'<path d="M4 12h16m-6-6 6 6-6 6"/>',plus:'<path d="M12 5v14M5 12h14"/>'};
const icon=name=>`<svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">${icons[name]||icons.bag}</svg>`;
const escapeHtml=value=>String(value).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const normalize=value=>String(value).normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLowerCase();
const safeUrl=value=>{try{const url=new URL(value);return url.protocol==='https:'?url.href:'';}catch{return '';}};
let category='',query='',sort='featured',cart={},visibleCount=24;
try{const stored=JSON.parse(localStorage.getItem('eifarma-cart')||'{}');for(const p of data.products){const n=Number(stored[p.ean]);if(Number.isInteger(n)&&n>0)cart[p.ean]=Math.min(n,99);}}catch{}
let toastTimer;
function toast(message){$('#toast').textContent=message;$('#toast').classList.add('visible');clearTimeout(toastTimer);toastTimer=setTimeout(()=>$('#toast').classList.remove('visible'),2500);}
function placeholder(){return `<div class="image-placeholder">${icon('bottle')}<small>Foto em breve</small></div>`;}
function medicineDetails(p){
 const m=p.medicine;if(!m)return '';
 const warnings=[m.warning,m.symptoms_warning].filter(Boolean);
 return `<div class="medicine-details"><p class="medicine-ingredient">${escapeHtml(m.ingredient)}</p><p>Registro Anvisa: ${escapeHtml(m.registration)}<br>Detentor: ${escapeHtml(m.manufacturer)}</p>${m.indication?`<p><strong>Indicação:</strong> ${escapeHtml(m.indication)}</p>`:''}${m.usage?`<p>${escapeHtml(m.usage)}</p>`:''}${safeUrl(m.leaflet)?`<a href="${escapeHtml(safeUrl(m.leaflet))}" target="_blank" rel="noopener">Leia a bula completa</a>`:''}</div>${warnings.length?`<div class="medicine-warnings">${warnings.map(w=>`<p>${escapeHtml(w)}</p>`).join('')}</div>`:''}`;
}
function renderProducts(more=false){
 if(!more)visibleCount=24;
 let list=data.products.filter(p=>(!category||p.category===category)&&normalize(`${p.name} ${p.brand} ${p.ean}`).includes(normalize(query)));
 if(sort!=='featured')list.sort((a,b)=>a.name.localeCompare(b.name,'pt-BR')*(sort==='az'?1:-1));
 $('#catalog-title').textContent=query?`Resultados para “${query}”`:category||'Uma seleção para se cuidar';
 $('#result-count').textContent=`${list.length} ${list.length===1?'produto':'produtos'}`;
 $('#empty-state').hidden=list.length>0;
 $('#load-more').hidden=list.length<=visibleCount;
 $('#product-grid').innerHTML=list.slice(0,visibleCount).map(p=>`<article class="product-card ${p.medicine?'medicine-card':''}"><div class="product-image">${safeUrl(p.image)?`<img src="${escapeHtml(safeUrl(p.image))}" alt="${escapeHtml(p.name)}" loading="lazy" referrerpolicy="no-referrer">${safeUrl(p.image_source)?`<a class="source" href="${escapeHtml(safeUrl(p.image_source))}" target="_blank" rel="noopener">Crédito da foto</a>`:''}`:placeholder()}</div><span class="product-brand">${escapeHtml(p.brand)}</span><h3>${escapeHtml(p.name)}</h3><span class="product-category">${escapeHtml(p.category)}</span>${medicineDetails(p)}<button class="button" data-add="${escapeHtml(p.ean)}" aria-label="Adicionar ${escapeHtml(p.name)} ao carrinho">${icon('plus')} Adicionar ao carrinho</button></article>`).join('');
 $('#product-grid').querySelectorAll('img').forEach(img=>img.addEventListener('error',()=>{img.parentNode.innerHTML=placeholder();},{once:true}));
 document.querySelectorAll('#filters [data-category]').forEach(b=>{b.classList.toggle('active',b.dataset.category===category);b.setAttribute('aria-pressed',String(b.dataset.category===category));});
}
$('#load-more').addEventListener('click',()=>{visibleCount+=24;renderProducts(true);});
function setCategory(value,scroll=true){category=value;query='';$('#search').value='';$('#catalog-search').value='';renderProducts();if(scroll)$('#produtos').scrollIntoView({behavior:'smooth'});}
document.querySelectorAll('[data-category]').forEach(button=>button.addEventListener('click',()=>setCategory(button.dataset.category)));
function searchProducts(value,scroll=false){query=value.trim();category='';$('#search').value=value;$('#catalog-search').value=value;renderProducts();if(scroll)$('#produtos').scrollIntoView({behavior:'smooth'});}
for(const [form,input] of [['#search-form','#search'],['#catalog-search-form','#catalog-search']]){
 $(form).addEventListener('submit',event=>{event.preventDefault();searchProducts($(input).value,true);});
 $(input).addEventListener('input',()=>searchProducts($(input).value));
}
const macroCategories={'Beleza e cuidados':['Cabelos','Cuidados com a pele','Maquiagem e unhas'],'Higiene':['Corpo e banho','Higiene pessoal','Higiene bucal'],'Fraldas e cuidados':['Fraldas'],'Medicamentos':['Medicamentos sem receita']};
document.querySelectorAll('[data-macro]').forEach(button=>button.addEventListener('click',()=>{
 const opened=button.getAttribute('aria-expanded')==='true';
 document.querySelectorAll('[data-macro]').forEach(b=>b.setAttribute('aria-expanded','false'));
 $('#subcategory-menu').hidden=opened;
 if(opened)return;
 button.setAttribute('aria-expanded','true');
 $('#subcategory-menu').innerHTML=`<h3>${escapeHtml(button.dataset.macro)}</h3><div>${macroCategories[button.dataset.macro].map(name=>`<button class="button" data-subcategory="${escapeHtml(name)}">${escapeHtml(name)} ${icon('arrow')}</button>`).join('')}</div>`;
}));
$('#subcategory-menu').addEventListener('click',event=>{const button=event.target.closest('[data-subcategory]');if(button)setCategory(button.dataset.subcategory);});
$('#sort').addEventListener('change',event=>{sort=event.target.value;renderProducts();});
$('#clear-search').addEventListener('click',()=>setCategory(''));
function saveCart(){try{localStorage.setItem('eifarma-cart',JSON.stringify(cart));}catch{}renderCart();}
$('#product-grid').addEventListener('click',event=>{const button=event.target.closest('[data-add]');if(!button)return;const ean=button.dataset.add;if((cart[ean]||0)>=99){toast('Limite de 99 unidades por produto.');return;}cart[ean]=(cart[ean]||0)+1;saveCart();toast('Produto adicionado ao carrinho');});
function renderCart(){
 const entries=data.products.filter(p=>cart[p.ean]);const count=Object.values(cart).reduce((a,b)=>a+b,0);$('#cart-count').textContent=count;
 $('#cart-open').setAttribute('aria-label',`Abrir carrinho, ${count} itens`);
 $('#checkout').disabled=!count;
 $('#bottom-cart').hidden=!count;
 document.body.classList.toggle('has-cart',count>0);
 $('#bottom-cart-count').textContent=`${count} ${count===1?'item adicionado':'itens adicionados'}`;
 $('#cart-items').innerHTML=entries.length?`<div style="display:flex;justify-content:space-between;align-items:center"><small>${count} ${count===1?'item':'itens'} no carrinho</small><button class="remove-item" data-clear>Limpar carrinho</button></div>`+entries.map(p=>`<article class="cart-row">${safeUrl(p.image)?`<img class="cart-thumb" src="${escapeHtml(safeUrl(p.image))}" alt="">`:`<div class="cart-thumb">${icon('bottle')}</div>`}<div><h3>${escapeHtml(p.name)}</h3><div class="quantity"><button data-ean="${p.ean}" data-delta="-1" aria-label="Diminuir quantidade de ${escapeHtml(p.name)}">−</button><span>${cart[p.ean]}</span><button data-ean="${p.ean}" data-delta="1" aria-label="Aumentar quantidade de ${escapeHtml(p.name)}" ${cart[p.ean]>=99?'disabled':''}>+</button><button class="remove-item" style="width:auto;border:0" data-remove="${p.ean}">Remover</button></div></div></article>`).join(''):`<div class="empty-state">${icon('bag')}<h3>Seu carrinho está vazio</h3><p>Adicione seus produtos favoritos para fazer seu pedido.</p><button class="button" data-close="cart-dialog">Explorar produtos</button></div>`;
 $('#cart-items').querySelectorAll('img').forEach(img=>img.addEventListener('error',()=>{const box=document.createElement('div');box.className='cart-thumb';box.innerHTML=icon('bottle');img.replaceWith(box);},{once:true}));
}
$('#cart-items').addEventListener('click',event=>{const target=event.target.closest('button');if(!target)return;if(target.hasAttribute('data-clear'))cart={};else if(target.dataset.remove)delete cart[target.dataset.remove];else if(target.dataset.ean){const ean=target.dataset.ean;cart[ean]=Math.max(0,Math.min(99,(cart[ean]||0)+Number(target.dataset.delta)));if(!cart[ean])delete cart[ean];}else return;saveCart();});
$('#cart-open').addEventListener('click',()=>$('#cart-dialog').showModal());
$('#bottom-cart-open').addEventListener('click',()=>$('#cart-dialog').showModal());
$('#privacy-open').addEventListener('click',()=>$('#privacy-dialog').showModal());
document.addEventListener('click',event=>{const close=event.target.closest('[data-close]');if(close)document.getElementById(close.dataset.close).close();});
document.querySelectorAll('dialog').forEach(dialog=>dialog.addEventListener('click',event=>{if(event.target===dialog){const rect=dialog.getBoundingClientRect();if(event.clientX<rect.left||event.clientX>rect.right||event.clientY<rect.top||event.clientY>rect.bottom)dialog.close();}}));
$('#checkout').addEventListener('click',()=>{
 const entries=data.products.filter(p=>cart[p.ean]);if(!entries.length)return;
 const lines=entries.map(p=>`• ${cart[p.ean]} un. — ${p.name}`);
 const note=$('#order-note').value.trim();
 const message=`Olá, EI! FARMA! Gostaria de fazer este pedido:\n\n${lines.join('\n\n')}${note?'\n\nObservação: '+note:''}\n\nPodem confirmar os valores e a disponibilidade?`;
 window.open(`https://wa.me/${data.whatsapp}?text=${encodeURIComponent(message)}`,'_blank','noopener,noreferrer');
});
let slide=0,paused=window.matchMedia('(prefers-reduced-motion: reduce)').matches;
function renderSlide(){const s=data.slides[slide];$('#hero').className=`hero ${s.theme==='rose'?'rose':''}`;$('#hero-eyebrow').textContent=s.eyebrow;$('#hero-title').textContent=s.title;$('#hero-subtitle').textContent=s.subtitle;$('#hero-cta').innerHTML=escapeHtml(s.cta)+icon('arrow');$('#slide-dots').innerHTML=data.slides.map((_,i)=>`<button class="${i===slide?'active':''}" aria-label="Ver campanha ${i+1}" aria-pressed="${i===slide}" data-slide="${i}"></button>`).join('');}
function moveSlide(delta){slide=(slide+delta+data.slides.length)%data.slides.length;renderSlide();}
$('#prev-slide').addEventListener('click',()=>moveSlide(-1));$('#next-slide').addEventListener('click',()=>moveSlide(1));
$('#slide-dots').addEventListener('click',event=>{if(event.target.dataset.slide!==undefined){slide=Number(event.target.dataset.slide);renderSlide();}});
$('#hero-cta').addEventListener('click',()=>setCategory(data.slides[slide].category));
function renderPause(){$('#pause-slide').textContent=paused?'▶':'Ⅱ';$('#pause-slide').setAttribute('aria-label',paused?'Retomar carrossel':'Pausar carrossel');}
$('#pause-slide').addEventListener('click',()=>{paused=!paused;renderPause();});
setInterval(()=>{if(!paused&&!document.hidden&&!$('#hero').matches(':hover')&&!$('#hero').contains(document.activeElement)&&!document.querySelector('dialog[open]'))moveSlide(1);},7000);
renderProducts();renderCart();renderSlide();renderPause();
