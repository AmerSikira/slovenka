#!/usr/bin/env python3
"""Package only the runtime files/assets used by the production WordPress theme."""
from pathlib import Path
from bs4 import BeautifulSoup
import json, re, shutil, subprocess, tempfile, zipfile

ROOT = Path(__file__).resolve().parents[1]
THEME = ROOT / 'wordpress/slovenka'
script = (ROOT / 'script.js').read_text()
prefix = script.split('// Small helpers.')[0]
data = json.loads(subprocess.check_output([
    'node', '-e', prefix + '\nprocess.stdout.write(JSON.stringify({products:PRODUCTS,images:IMAGES,categories:CATEGORIES}));'
], text=True))

pages = {}
for file in sorted(ROOT.glob('*.html')):
    doc = BeautifulSoup(file.read_text(), 'html.parser')
    main = doc.select_one('main')
    if not main:
        continue
    route = doc.body['data-page']
    for note in main.select('.catalog-note, .preview-helper'):
        if route == '/shop' or route.startswith('/product/'):
            note.decompose()
    # Production uses native WooCommerce, so don't ship its unused preview UI/CSS/JS.
    for tag in doc.head.select('script[src="commerce.js"], link[href="commerce.css"]'):
        tag.decompose()
    if route in ['/cart', '/checkout']:
        main.clear()
        main.append('[woocommerce_cart]' if route == '/cart' else '[woocommerce_checkout]')
    if route == '/order':
        main.clear()
        main.append('Detalji narudžbe dostupni su na potvrdi kupovine. / Order details are available in your checkout confirmation.')
    before, after = [], []
    found = False
    for node in doc.body.contents:
        if node is main:
            found = True
            continue
        (after if found else before).append(str(node))
    pages[file.name] = {
        'title': doc.title.get_text().replace(' — MK Slovenka', ''),
        'lang': doc.html['lang'], 'route': route,
        'head': doc.head.decode_contents(), 'before': ''.join(before),
        'after': ''.join(after), 'content': main.decode_contents(),
    }
data['pages'] = pages
data['size_tables'] = json.loads((ROOT / 'templates/size-tables.json').read_text())

# Retain photographs reached by markup, every colour/gallery, categories, or runtime helpers.
used_images = set()
for product in data['products']:
    for colour in product['colors']:
        used_images.add(colour['cover'])
        used_images.update(colour['images'])
used_images.update(category['image'] for category in data['categories'])
page_source = json.dumps(pages, ensure_ascii=False)
runtime = script.split('// Small helpers.')[1]
for key, image in data['images'].items():
    if image['fallback'] in page_source or image['src'] in page_source or key in runtime:
        used_images.add(key)
unused_images = set(data['images']) - used_images
all_images = data['images']
data['images'] = {key: image for key, image in all_images.items() if key in used_images}

# WebP already exists for each JPEG fallback. Share that single full-size file with
# <img>, <source>, the JS registry and the activation importer; keep responsive sizes.
aliases = {image['fallback']: image['src'] for image in all_images.values() if image['fallback'] != image['src']}
def replace_images(text):
    for old, new in aliases.items():
        text = text.replace(old, new)
    return text
for page in pages.values():
    for key in ['head', 'before', 'after', 'content']:
        page[key] = replace_images(page[key])
for image in data['images'].values():
    image['fallback'] = image['src']
# Render old imported HTML against the lean package without overwriting page edits.
# Only retain aliases for photos still in use; unused source photos aren't bundled.
data['image_aliases'] = {old: new for old, new in aliases.items() if any(image['src'] == new for image in data['images'].values())}
start = script.index('const IMAGES = ')
end = script.index('// Production configuration', start)
script = script[:start] + 'const IMAGES = ' + json.dumps(data['images'], ensure_ascii=False) + ';\n\n' + script[end:]
script = replace_images(script)

# No invented photography, sizes or copy for MIJA; it stays unpublished.
data['products'].append({'id':'mija','name':'MIJA','label':{'bs':'Kaput Mija','en':'Mija coat'},'type':'coats','price':220,'description':{'bs':'','en':''},'colors':[],'sizes':[],'unavailable':[],'status':'draft'})

runtime_files = {'script.js': script}
for name in ['styles.css', 'motion.js', 'contact-map.js']:
    runtime_files[name] = (ROOT / name).read_text()
asset_pattern = re.compile(r'assets/[A-Za-z0-9_./%+\-]+')
# Legacy alias keys deliberately name omitted files; don't treat them as dependencies.
references = json.dumps({key: value for key, value in data.items() if key != 'image_aliases'}, ensure_ascii=False)
references += '\n'.join(runtime_files.values())
assets = {path for path in asset_pattern.findall(references) if not path.endswith('/')}
# Licence notices remain required even though the browser doesn't fetch them.
assets.update(['assets/fonts/OFL.txt', 'assets/icons-LICENSE.txt', 'assets/vendor/gsap/LICENSE.txt', 'assets/vendor/maplibre/LICENSE.txt'])
# Resolve dependencies of bundled text assets too (for example CSS font URLs).
pending = list(assets)
while pending:
    path = pending.pop()
    source = ROOT / path
    if not source.is_file():
        raise RuntimeError(f'Missing runtime asset: {path}')
    if source.suffix not in ['.css', '.js', '.svg']:
        continue
    for dependency in asset_pattern.findall(source.read_text()):
        if dependency not in assets and not dependency.endswith('/'):
            assets.add(dependency)
            pending.append(dependency)

# Replace the generated asset tree instead of merging into it: stale files cannot survive.
with tempfile.TemporaryDirectory(prefix='slovenka-assets-', dir=THEME.parent) as temp:
    staged = Path(temp) / 'assets'
    for path in sorted(assets):
        target = Path(temp) / path
        target.parent.mkdir(parents=True, exist_ok=True)
        shutil.copy2(ROOT / path, target)
    if (THEME / 'assets').exists():
        shutil.rmtree(THEME / 'assets')
    shutil.move(str(staged), THEME / 'assets')
for name, content in runtime_files.items():
    (THEME / name).write_text(content)
# Remove obsolete generated files from older builds, keeping all authored sources outside the theme.
for name in ['commerce.css', 'commerce.js']:
    (THEME / name).unlink(missing_ok=True)
(THEME / 'data').mkdir(exist_ok=True)
(THEME / 'data/site.json').write_text(json.dumps(data, ensure_ascii=False, separators=(',', ':')))

# Explicit package manifest also excludes accidental backups, editor files and stale outputs.
package_files = set(runtime_files) | {'data/site.json', 'style.css', 'notices.css', 'production.js', 'functions.php', 'header.php', 'footer.php', 'index.php', 'inc/import.php', 'inc/render.php', 'inc/size-tables.php', 'inc/forms.php'} | assets
output = ROOT / 'dist/slovenka.zip'
output.parent.mkdir(exist_ok=True)
with zipfile.ZipFile(output, 'w', zipfile.ZIP_DEFLATED) as archive:
    for name in sorted(package_files):
        archive.write(THEME / name, 'slovenka/' + name)
print(f'{output}\n{len(assets)} runtime assets; {len(unused_images)} unused photos removed; {output.stat().st_size / 1024 / 1024:.1f} MiB ZIP')
