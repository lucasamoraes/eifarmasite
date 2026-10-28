"""Build an explicit EAN allowlist and auditable exclusions from the supplied ERP workbook.
Conservative editorial scope, not a regulatory-registration certificate.
"""
import collections, json, pathlib, re, unicodedata
import openpyxl

ROOT=pathlib.Path(__file__).resolve().parents[1]
def normalized(value):
    return ''.join(c for c in unicodedata.normalize('NFD',str(value or '').upper()) if unicodedata.category(c)!='Mn').strip()
def valid_ean(ean):
    if not re.fullmatch(r'(?:\d{8}|\d{12}|\d{13}|\d{14})',ean) or ean.startswith('00000'):return False
    return (10-sum(int(n)*(3 if i%2==0 else 1) for i,n in enumerate(reversed(ean[:-1])))%10)%10==int(ean[-1])

SAFE_GROUPS={
 'ABSORVENTE EXTERNO':'Higiene pessoal','ABSORVENTE INTERNO':'Higiene pessoal',
 'DESODORANTES AEROSOL':'Higiene pessoal','DESODORANTES AEROSSO':'Higiene pessoal','DESODORANTES ROLL-ON':'Higiene pessoal','DESODORANTES STICK':'Higiene pessoal','DESODORANTES CREME':'Higiene pessoal',
 'FIOS E FITAS DENTAIS':'Higiene bucal','ESCOVA DENTAIS':'Higiene bucal','ESCOVAS DENTAIS COMU':'Higiene bucal','CREMES DENTAIS':'Higiene bucal','CREME DENTAIS':'Higiene bucal',
 'COLONIAS':'Corpo e banho','SABONETES':'Corpo e banho','SABONETES EM BARRAS':'Corpo e banho','SABONETE LIQUIDO INT':'Corpo e banho',
 'SHAMPOOS COMUNS - NA':'Cabelos','SHAMPOO E CONDICIONA':'Cabelos',
 'ESMALTES CORES VARIA':'Maquiagem e unhas','MAQUIAGEM.':'Maquiagem e unhas','ALICATES, TESOURAS E':'Maquiagem e unhas','LIXAS E PEDRAS':'Maquiagem e unhas',
 'APARELHO DE BARBEAR':'Higiene pessoal','APARELHOS PARA BABEA':'Higiene pessoal','LENCOS UMEDECIDOS E':'Higiene pessoal',
 'HIDRATANTES':'Cuidados com a pele','PROTECAO':'Cuidados com a pele','PROTETOR LABIAL':'Cuidados com a pele',
}
PREFIXES=[
 (r'^(SHAMPOO|SH |CONDICIONADOR|COND |CREME PENTEAR|CREME DE PENTEAR|CR PENT |CREME TRATAMENTO|CREME DE TRATAMENTO|CR TRAT |OLEO CAPILAR|ACIDIFICANTE|FINALIZADOR|MASCARA CAPILAR|REPARADOR PONTAS|QUERATINA|RECARGA QUERATINA|TONICO CAPILAR|TONICO KOLENE|TONALIZANTE|TINT[ .]|TINTURA|CREME ELSEVE|CREME MILAGROSO ELSEVE|KIT ELSEVE|KIT PANTENE|ELIXIR ELSEVE|LEAVE IN|SERUM ELSEVE|SERUM DE TRATAMENTO DOVE BOND)','Cabelos'),
 (r'^(SABONETE|SAB |COLONIA|AGUA COLONIA|OLEO CORPORAL|OLEO DOVE BANHO)','Corpo e banho'),
 (r'^(ABS |ABSORVENTE|DESODORANTE|DES AERO|DES ROLL|HASTES FLEXIVEIS|ALGODAO|LENCO UMEDECIDO|LENCOS UMEDECIDOS|TOALHA UMED|APARELHO BARBEAR|APARELHO DE BARBEAR|APARELHO DEPILATORIO|APARELHO GILLETTE|APAR.GILLETTE)','Higiene pessoal'),
 (r'^(CREME DENTAL|GEL DENTAL|FIO DENTAL|ESCOVA DENTAL)','Higiene bucal'),
 (r'^(PROTETOR SOLAR|PROTETOR LABIAL|HIDRATANTE|LOCAO HIDRATANTE|CREME FACIAL NIVEA|CREME NIVEA|AGUA MICELAR|GEL DE LIMPEZA FACIAL|SERUM DOVE|SERUM LABIAL VULT|OLEO DOVE CORPO|OLEO CORPORAL DOVE)','Cuidados com a pele'),
 (r'^(ESMALTE|BASE LIQUIDA VULT|BATOM|BLUSH|CORRETIVO|DELINEADOR|LAPIS |PO COMPACTO|MASCARA DE CILIOS|GLOSS|LIXA |ALICATE CUTICULA)','Maquiagem e unhas'),
]
BLOCK=re.compile(r'VODOL|SANASAR|CETOCONAZOL|MICONAZOL|MINOXIDIL|PERMETRINA|BENZOATO DE BENZILA|CORTICO|ANTIFUNG|ANTIMICOT|MEDICINAL|\bMG(?:/|\b)|MAMADEIRA|CHUPETA|\bBICO\b|FORMULA INFANTIL|EXTRATOR.*LEITE|PROTETOR.*SEIO|ABS SEIOS|ANTIPIO|PEDICUL|ESCABIO')

def classify(name,group,kind):
    if name.startswith(('DEXPANTENOL','BEPANTRIZ','CERA ORTODONTICA')):return None,'Identificação exige análise específica, não incluído'
    if kind in ('ETICOS','GENERICOS','SIMILARES'):return None,'Medicamento: fora do catálogo público adotado'
    if kind in ('LEITES','SUPLEMENTOS E VITAM'):return None,'Alimento/suplemento: exige análise específica, não incluído'
    if BLOCK.search(name):return None,'Medicamento, item infantil restrito ou identificação que exige análise específica'
    if kind=='FRALDAS' and group=='FRALDAS' and name.startswith('FRALDA'):return 'Fraldas','Fralda identificada pelo cadastro'
    if kind!='PERFUMARIA':return None,'Classe fora do escopo de higiene, beleza e fraldas'
    if group in SAFE_GROUPS:return SAFE_GROUPS[group],'Grupo específico de higiene/beleza e identificação compatível'
    for pattern,category in PREFIXES:
        if re.search(pattern,name):return category,'Descrição identifica item de higiene/beleza'
    return None,'Descrição/grupo insuficiente para inclusão automática conservadora'

rows=list(openpyxl.load_workbook(ROOT/'PRODUTOS EIFARMA.xlsx',read_only=True,data_only=True).active.values)[1:]
original={p['ean']:p for p in json.loads((ROOT/'data/sample.json').read_text(encoding='utf-8'))}
accepted=[];audit=[];seen={}
for line,row in enumerate(rows,2):
    name,ean,group,kind=normalized(row[0]),str(row[1]).strip(),normalized(row[2]),normalized(row[3])
    category,reason=classify(name,group,kind)
    if not valid_ean(ean):category=None;reason='EAN inválido ou código interno; precisa do GTIN real'
    if ean in seen:category=None;reason='EAN repetido; não duplicado no catálogo'
    seen[ean]=line
    if category:
        # Keep the ERP description intact rather than inventing missing variants or sizes.
        product=dict(ean=ean,name=name,category=category,brand='',image='https://cdn-cosmos.bluesoft.com.br/products/'+ean,image_source='https://cosmos.bluesoft.com.br/produtos/'+ean)
        if ean in original:
            for key in ('name','brand'):product[key]=original[ean][key]
        accepted.append(product)
    audit.append(dict(row=line,ean=ean,name=name,group=group,classification=kind,status='incluido' if category else 'excluido',category=category,reason=reason))
# Preserve the original curated storefront order, then alphabetize the added products.
order={ean:i for i,ean in enumerate(original)}
accepted.sort(key=lambda p:(order.get(p['ean'],9999),p['name']))
summary=dict(total=len(rows),included=len(accepted),excluded=len(rows)-len(accepted),categories=dict(collections.Counter(p['category'] for p in accepted)),reasons=dict(collections.Counter(a['reason'] for a in audit if a['status']=='excluido')))
(ROOT/'data/catalog.json').write_text(json.dumps(accepted,ensure_ascii=False,indent=2),encoding='utf-8')
(ROOT/'data/catalog-audit.json').write_text(json.dumps(dict(summary=summary,rows=audit),ensure_ascii=False,indent=2),encoding='utf-8')
print(json.dumps(summary,ensure_ascii=False,indent=2))
