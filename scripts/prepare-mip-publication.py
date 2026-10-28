"""Build private MIP drafts from CMED plus official registration data."""
import csv,json,hashlib
from pathlib import Path
root=Path(__file__).resolve().parents[1]
registrations={}
with (root/'storage/anvisa-registros.csv').open(encoding='latin1',newline='') as f:
 for row in csv.DictReader(f,delimiter=';'):
  registrations.setdefault(row['NUMERO_REGISTRO_PRODUTO'],set()).add(row['SITUACAO_REGISTRO'])
audit=json.loads((root/'storage/teste-mips-ean.json').read_text(encoding='utf-8'))
drafts=[]
for p in audit['rows']:
 if p['status']!='candidato_mip':continue
 matches=p['matches']; identities={(m['REGISTRO'],m['APRESENTAÇÃO']) for m in matches}
 m=matches[0];record=str(m['REGISTRO'])[:9]
 states=registrations.get(record,set())
 drafts.append(dict(ean=p['ean'],source_name=p['name'],name=m['PRODUTO'],presentation=m['APRESENTAÇÃO'],ingredient=m['SUBSTÂNCIA'],manufacturer=m['LABORATÓRIO'],registration=str(m['REGISTRO']),registration_status=sorted(states),status='draft',pending=['Bula, indicação e advertências por apresentação'],source_row=m['source_row'],ambiguous=len(identities)!=1))
ready=[]
for d in drafts:
 if d['ean']!='7896523201887':continue
 assert d['registration_status']==['Ativo'] and not d['ambiguous']
 d['status']='content_ready';d['pending']=[]
 ready.append(dict(ean=d['ean'],name='Dipirona monoidratada 1 g — 10 comprimidos',category='Medicamentos sem receita',brand='Cimed',image='https://cdn-cosmos.bluesoft.com.br/products/'+d['ean'],image_source='https://cosmos.bluesoft.com.br/produtos/'+d['ean'],medicine=dict(ingredient='Dipirona monoidratada',registration=d['registration'],manufacturer=d['manufacturer'],indication='Alívio da dor e da febre.',usage='Uso oral. Para adultos e adolescentes acima de 15 anos.',warning='NÃO USE ESTE MEDICAMENTO DURANTE A GRAVIDEZ E EM CRIANÇAS MENORES DE TRÊS MESES DE IDADE.',symptoms_warning='SE PERSISTIREM OS SINTOMAS, O MÉDICO DEVERÁ SER CONSULTADO.',leaflet='https://bula.cimedremedios.com.br/008/006/001/001/104064/104064-B.pdf',source='https://cimedremedios.com.br/remedio/dipirona/',checked_on='2026-10-28',registration_status='Ativo')))
(root/'data/mips-ready.json').write_text(json.dumps(ready,ensure_ascii=False,indent=2),encoding='utf-8')
report=dict(candidates=len(drafts),active_unambiguous=sum(d['registration_status']==['Ativo'] and not d['ambiguous'] for d in drafts),content_ready=len(ready),registration_source='https://dados.anvisa.gov.br/dados/DADOS_ABERTOS_MEDICAMENTOS.csv',registration_sha256=hashlib.sha256((root/'storage/anvisa-registros.csv').read_bytes()).hexdigest(),drafts=drafts)
(root/'storage/mips-publication-drafts.json').write_text(json.dumps(report,ensure_ascii=False,indent=2),encoding='utf-8')
print(json.dumps({k:report[k] for k in ['candidates','active_unambiguous','content_ready']}))
