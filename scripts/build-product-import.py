#!/usr/bin/env python3
"""Build the independent, optional catalogue plugin and standard WooCommerce CSV."""
from pathlib import Path
import csv, io, json, shutil, subprocess, zipfile

ROOT = Path(__file__).resolve().parents[1]
PLUGIN = ROOT / 'wordpress/slovenka-catalogue-import'
prefix = (ROOT / 'script.js').read_text().split('// Small helpers.')[0]
data = json.loads(subprocess.check_output(['node', '-e', prefix + '\nprocess.stdout.write(JSON.stringify({products:PRODUCTS,images:IMAGES,categories:CATEGORIES}));'], text=True))
data['products'].append({'id':'mija','name':'MIJA','label':{'bs':'Kaput Mija','en':'Mija coat'},'type':'coats','price':220,'description':{'bs':'','en':''},'colors':[],'sizes':[],'unavailable':[],'status':'draft'})
data['size_tables'] = json.loads((ROOT / 'templates/size-tables.json').read_text())
used = {key for item in data['products'] for color in item['colors'] for key in [color['cover'], *color['images']]}
data['images'] = {key:value for key,value in data['images'].items() if key in used}
data['image_aliases'] = {image['fallback']:image['src'] for image in data['images'].values() if image['fallback'] != image['src']}
for image in data['images'].values():
    image['fallback'] = image['src']
    image.pop('srcSet', None)  # WordPress generates its own Media Library sizes.
assets = {image['src'] for image in data['images'].values()}
if (PLUGIN / 'assets').exists(): shutil.rmtree(PLUGIN / 'assets')
for asset in assets:
    target = PLUGIN / asset
    target.parent.mkdir(parents=True, exist_ok=True)
    shutil.copy2(ROOT / asset, target)
(PLUGIN / 'data').mkdir(exist_ok=True)
(PLUGIN / 'data/catalogue.json').write_text(json.dumps(data, ensure_ascii=False, separators=(',', ':')))

fields = ['Type','SKU','Name','Published','Visibility in catalog','Short description','Description','Regular price','In stock?','Categories','Images','Parent','Attribute 1 name','Attribute 1 value(s)','Attribute 1 visible','Attribute 1 global','Attribute 2 name','Attribute 2 value(s)','Attribute 2 visible','Attribute 2 global','Meta: _slovenka_product','Meta: _slovenka_variant']
rows = []
def filename(key): return Path(data['images'][key]['src']).name
for item in data['products']:
    draft = item.get('status') == 'draft'
    sku = 'slovenka-' + item['id']
    category = next((c['bs'] for c in data['categories'] if c['id'] == item['type']), item['type'])
    gallery = list(dict.fromkeys(filename(key) for color in item['colors'] for key in [color['cover'], *color['images']]))
    rows.append({'Type':'simple' if draft else 'variable','SKU':sku,'Name':item['name']+' — '+item['label']['bs'],'Published':'-1' if draft else '1','Visibility in catalog':'visible','Short description':item['label']['bs'],'Description':item['description']['bs'],'Regular price':str(item['price']) if draft else '', 'In stock?':'1','Categories':category,'Images':', '.join(gallery),'Attribute 1 name':'colour' if not draft else '', 'Attribute 1 value(s)':', '.join(c['id'] for c in item['colors']),'Attribute 1 visible':'1' if not draft else '', 'Attribute 1 global':'0' if not draft else '', 'Attribute 2 name':'size' if not draft else '', 'Attribute 2 value(s)':', '.join(item['sizes']),'Attribute 2 visible':'1' if not draft else '', 'Attribute 2 global':'0' if not draft else '', 'Meta: _slovenka_product':item['id']})
    for color in item['colors']:
        for size in item['sizes']:
            key = item['id']+':'+color['id']+':'+size
            rows.append({'Type':'variation','Visibility in catalog':'visible','SKU':'slovenka-'+key.replace(':','-'),'Name':item['name']+' — '+color['id']+' / '+size,'Published':'1','Regular price':str(item['price']),'In stock?':'0' if size in item['unavailable'] else '1','Images':filename(color['cover']),'Parent':sku,'Attribute 1 name':'colour','Attribute 1 value(s)':color['id'],'Attribute 1 visible':'1','Attribute 1 global':'0','Attribute 2 name':'size','Attribute 2 value(s)':size,'Attribute 2 visible':'1','Attribute 2 global':'0','Meta: _slovenka_variant':key})
assert len({row['SKU'] for row in rows}) == len(rows)
buffer = io.StringIO(newline='')
writer = csv.DictWriter(buffer, fieldnames=fields)
writer.writeheader(); writer.writerows(rows)
csv_data = buffer.getvalue().encode('utf-8-sig')
output = ROOT / 'dist'; output.mkdir(exist_ok=True)
(output / 'slovenka-products.csv').write_bytes(csv_data)
with zipfile.ZipFile(output / 'slovenka-product-import.zip', 'w', zipfile.ZIP_DEFLATED) as archive:
    for path in sorted([PLUGIN / 'slovenka-catalogue-import.php', PLUGIN / 'data/catalogue.json', *[PLUGIN / asset for asset in assets]]):
        archive.write(path, 'slovenka-catalogue-import/' + str(path.relative_to(PLUGIN)))
with zipfile.ZipFile(output / 'slovenka-products-csv-and-images.zip', 'w', zipfile.ZIP_DEFLATED) as archive:
    archive.writestr('products.csv', csv_data)
    archive.write(ROOT / 'wordpress/PRODUCT-IMPORT.md', 'README.md')
    archive.write(ROOT / 'templates/size-tables.json', 'size-tables.json')
    for asset in sorted(assets): archive.write(ROOT / asset, 'images/' + Path(asset).name)
print(f'PASS: 12 products, {len(rows)-12} variations, {len(assets)} photos; independent importer ZIP and portable CSV/image package built.')
