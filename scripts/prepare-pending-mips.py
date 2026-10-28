import json
from pathlib import Path
root=Path(__file__).resolve().parents[1]
drafts=json.loads((root/'storage/mips-publication-drafts.json').read_text(encoding='utf-8'))['drafts']
items=[]
for d in drafts:
 if d['status']=='content_ready':continue
 items.append(dict(ean=d['ean'],name=d['name']+' — '+d['presentation'],brand=d['manufacturer'],image='https://cdn-cosmos.bluesoft.com.br/products/'+d['ean'],image_source='https://cosmos.bluesoft.com.br/produtos/'+d['ean'],registration=d['registration'],ingredient=d['ingredient'],registration_status=d['registration_status'],pending=d['pending'],ambiguous=d['ambiguous']))
assert len(items)==278 and len({p['ean'] for p in items})==278
assert all(len(p['name'])<=255 and len(p['brand'])<=100 for p in items)
(root/'data/mips-pending.json').write_text(json.dumps(items,ensure_ascii=False,indent=2),encoding='utf-8')
print('278 cadastros preparados, sem preços ou custos.')
