#!/usr/bin/env python3
"""Package the complete static site and an idempotent WordPress/WooCommerce import."""
from pathlib import Path
from bs4 import BeautifulSoup
import json, subprocess, shutil, zipfile
ROOT=Path(__file__).resolve().parents[1]
THEME=ROOT/'wordpress/slovenka'
prefix=(ROOT/'script.js').read_text().split('// Small helpers.')[0]
data=json.loads(subprocess.check_output(['node','-e',prefix+'\nprocess.stdout.write(JSON.stringify({products:PRODUCTS,images:IMAGES,categories:CATEGORIES}));'],text=True))
pages={}
for file in sorted(ROOT.glob('*.html')):
    doc=BeautifulSoup(file.read_text(),'html.parser')
    main=doc.select_one('main')
    if not main: continue
    for note in main.select('.catalog-note, .preview-helper'):
        if doc.body['data-page'] == '/shop' or doc.body['data-page'].startswith('/product/'):
            note.decompose()
    before=[]; after=[]; found=False
    for node in doc.body.contents:
        if node==main: found=True; continue
        (after if found else before).append(str(node))
    pages[file.name]={'title':doc.title.get_text().replace(' — MK Slovenka',''),'lang':doc.html['lang'],'route':doc.body['data-page'],'head':doc.head.decode_contents(),'before':''.join(before),'after':''.join(after),'content':main.decode_contents()}
data['pages']=pages
# No invented photographs, copy, or sizes for MIJA. It stays unpublished.
data['products'].append({'id':'mija','name':'MIJA','label':{'bs':'Kaput Mija','en':'Mija coat'},'type':'coats','price':220,'description':{'bs':'','en':''},'colors':[],'sizes':[],'unavailable':[],'status':'draft'})
(THEME/'data').mkdir(exist_ok=True)
(THEME/'data/site.json').write_text(json.dumps(data,ensure_ascii=False,separators=(',',':')))
for name in ['styles.css','script.js','motion.js','contact-map.js','commerce.css']:
    shutil.copy2(ROOT/name,THEME/name)
shutil.copytree(ROOT/'assets',THEME/'assets',dirs_exist_ok=True,ignore=shutil.ignore_patterns('img','.DS_Store'))
# Original photographs are authoring sources; every displayed asset is bundled above.
output=ROOT/'dist/slovenka.zip';output.parent.mkdir(exist_ok=True)
with zipfile.ZipFile(output,'w',zipfile.ZIP_DEFLATED) as archive:
    for file in sorted(THEME.rglob('*')):
        if file.is_file(): archive.write(file,file.relative_to(THEME.parent))
print(output)
