"""Read the supplied workbook; never export purchase costs to the catalog."""
import json, pathlib
import openpyxl

ROOT = pathlib.Path(__file__).resolve().parents[1]
SELECTION = {
 '7898422746759': ('Sabonete Dove Original 90 g', 'Corpo e banho', 'Dove'),
 '7899706187343': ('Shampoo Elseve Hidra Hialurônico 200 ml', 'Cabelos', "L’Oréal Paris"),
 '7899706170833': ('Shampoo Elseve Longo dos Sonhos 200 ml', 'Cabelos', "L’Oréal Paris"),
 '7500435125420': ('Shampoo Pantene Liso Extremo 175 ml', 'Cabelos', 'Pantene'),
 '7891024111444': ('Creme dental Colgate Total 12 90 g', 'Higiene bucal', 'Colgate'),
 '7891010501105': ('Fio dental Johnson’s 100 m', 'Higiene bucal', 'Johnson’s'),
 '4005808335435': ('Hidratante corporal Nivea 200 ml', 'Cuidados com a pele', 'Nivea'),
 '4005900950987': ('Creme facial Nivea 100 g', 'Cuidados com a pele', 'Nivea'),
 '7891150073098': ('Desodorante Dove aerosol 150 ml', 'Higiene pessoal', 'Dove'),
 '4005900707536': ('Desodorante Nivea Men Deep 150 ml', 'Higiene pessoal', 'Nivea'),
 '7891010793463': ('Absorvente Sempre Livre suave 16 unidades', 'Higiene pessoal', 'Sempre Livre'),
 '7891010032937': ('Hastes flexíveis Cotonetes 150 unidades', 'Higiene pessoal', 'Cotonetes'),
}
rows = list(openpyxl.load_workbook(ROOT/'PRODUTOS EIFARMA.xlsx',read_only=True,data_only=True).active.values)[1:]
lookup = {str(r[1]):r for r in rows}
(ROOT/'storage').mkdir(exist_ok=True)
(ROOT/'data').mkdir(exist_ok=True)
def fetch(item):
    ean, (name,category,brand) = item
    row = lookup[ean]
    product = dict(ean=ean,name=name,category=category,brand=brand,image='',image_source='',original_name=row[0],group=row[2],classification=row[3])
    product['image'] = 'https://cdn-cosmos.bluesoft.com.br/products/'+ean
    product['image_source'] = 'https://cosmos.bluesoft.com.br/produtos/'+ean
    return product
products = [fetch(item) for item in SELECTION.items()]
(ROOT/'data/sample.json').write_text(json.dumps(products,ensure_ascii=False,indent=2),encoding='utf-8')
print('Sample:',len(products),'with image:',sum(bool(p['image']) for p in products))
