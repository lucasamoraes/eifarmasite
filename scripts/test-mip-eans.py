"""Read-only CMED EAN cross-check. Never changes storefront or approves medicines."""
from pathlib import Path
import collections, hashlib, json, re
import openpyxl
ROOT=Path(__file__).resolve().parents[1]
source=ROOT/'storage/cmed-20261009.xlsx'
url='https://www.gov.br/anvisa/pt-br/assuntos/medicamentos/cmed/precos/arquivos/lista_pmc__20261009_115232672.xlsx/@@download/file'
book=openpyxl.load_workbook(source,read_only=True,data_only=True)
rows=iter(book['Lista PMC'].values);headers=[str(x).strip() for x in next(rows)]
index=collections.defaultdict(list);tarjas=collections.Counter()
fields=['PRODUTO','SUBSTÂNCIA','APRESENTAÇÃO','REGISTRO','LABORATÓRIO','TARJA','RESTRIÇÃO HOSPITALAR','DESTINAÇÃO COMERCIAL']
for n,row in enumerate(rows,2):
 item=dict(zip(headers,row));tarjas[str(item.get('TARJA')).strip()]+=1
 detail={k:item.get(k) for k in fields};detail['source_row']=n
 for key in ['EAN 1','EAN 2','EAN 3']:
  ean=str(item.get(key) or '').strip()
  if re.fullmatch(r'\d{8,14}',ean) and detail not in index[ean]:index[ean].append(detail)
erp=json.loads((ROOT/'data/catalog-audit.json').read_text(encoding='utf-8'))['rows']
results=[]
for item in erp:
 matches=index.get(item['ean'],[])
 labels={str(m.get('TARJA') or '').strip().upper() for m in matches}
 if not matches:status='nao_encontrado'
 elif len(labels)>1:status='conflito_tarjas'
 elif labels and labels.issubset({'TARJA VERMELHA','TARJA PRETA','TARJA VERMELHA SOB RESTRIÇÃO'}):status='sob_prescricao'
 elif labels=={'VENDA LIVRE'} or labels=={'SEM TARJA'} or labels=={'TARJA SEM TARJA'}:status='candidato_mip'
 else:status='tarja_nao_conclusiva'
 results.append(dict(ean=item['ean'],name=item['name'],erp_class=item['classification'],status=status,matches=matches))
report=dict(source=url,source_date='2026-10-09',sha256=hashlib.sha256(source.read_bytes()).hexdigest(),tarja_values=dict(tarjas),summary=dict(collections.Counter(x['status'] for x in results)),rows=results,publication='Nenhum produto publicado. Candidato não comprova registro vigente nem fornece advertências de publicidade.')
(ROOT/'storage/teste-mips-ean.json').write_text(json.dumps(report,ensure_ascii=False,indent=2),encoding='utf-8')
print(json.dumps({k:report[k] for k in ['tarja_values','summary']},ensure_ascii=False,indent=2))
for x in [p for p in results if p['status']=='candidato_mip'][:8]:print(json.dumps(x,ensure_ascii=False))
