#!/usr/bin/env python3
"""Package only the runtime files/assets used by the production WordPress theme."""
from pathlib import Path
from bs4 import BeautifulSoup
import json, re, shutil, subprocess, sys, tempfile, zipfile

ROOT = Path(__file__).resolve().parents[1]
THEME = ROOT / 'wordpress/slovenka'
subprocess.run([sys.executable, str(ROOT / 'scripts/build-translations.py')], check=True)
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
    if route.startswith('/product/'):
        continue
    if route == '/order' or (route in ['/shop','/cart','/checkout'] and doc.html['lang'] == 'en'):
        continue
    if route == '/shop':
        intro = main.select_one('p.muted')
        content = '<p>' + (intro.decode_contents() if intro else '') + '</p>'
    elif route in ['/cart','/checkout']:
        content = '[woocommerce_cart]' if route == '/cart' else '[woocommerce_checkout]'
    else:
        for grid in main.select('.product-grid'):
            grid.replace_with('[products limit="4" columns="4" orderby="date" order="DESC"]')
        for note in main.select('.catalog-note, .preview-helper'):
            note.decompose()
        content = main.decode_contents()
    pages[file.name] = {
        'title': doc.title.get_text().replace(' — MK Slovenka', ''),
        'lang': doc.html['lang'], 'route': route, 'content': content,
    }
data['pages'] = pages
# Product seeds and manufacturer measurements belong only in the separate import package.
data.pop('products', None)
data.pop('categories', None)

# Retain photographs reached by markup, every colour/gallery, categories, or runtime helpers.
used_images = set()
page_source = json.dumps(pages, ensure_ascii=False)
runtime = (THEME / 'theme.js').read_text()
for key, image in data['images'].items():
    if image['fallback'] in page_source or image['src'] in page_source or key in runtime:
        used_images.add(key)
unused_images = set(data['images']) - used_images
all_images = data['images']
data['images'] = {key: image for key, image in all_images.items() if key in used_images}

# WebP already exists for each JPEG fallback. Share one full-size file with
# editorial <img>/<source> elements and starter content; keep responsive sizes.
aliases = {image['fallback']: image['src'] for image in all_images.values() if image['fallback'] != image['src']}
def replace_images(text):
    for old, new in aliases.items():
        text = text.replace(old, new)
    return text
for page in pages.values():
    for key in ['content']:
        page[key] = replace_images(page[key])
for image in data['images'].values():
    image['fallback'] = image['src']
# Provide one-time image URL migration for old imported editorial HTML.
# Only retain aliases for photos still in use; unused source photos aren't bundled.
data['image_aliases'] = {old: new for old, new in aliases.items() if any(image['src'] == new for image in data['images'].values())}
runtime_files = {'styles.css': (ROOT / 'styles.css').read_text(), 'motion.js': (ROOT / 'motion.js').read_text(), 'contact-map.js': (ROOT / 'contact-map.js').read_text()}
asset_pattern = re.compile(r'assets/[A-Za-z0-9_./%+\-]+')
# Legacy alias keys deliberately name omitted files; don't treat them as dependencies.
references = json.dumps({key: value for key, value in data.items() if key != 'image_aliases'}, ensure_ascii=False)
references += '\n'.join(runtime_files.values())
references += '\n'.join(path.read_text() for path in THEME.rglob('*.php'))
assets_extra = {'assets/favicon.svg','assets/flags/gb.svg','assets/flags/ba.svg','assets/vendor/gsap/gsap-3.15.0.min.js','assets/vendor/maplibre/maplibre-gl.css','assets/vendor/maplibre/maplibre-gl.js'}
assets = {path for path in asset_pattern.findall(references) if not path.endswith('/')} | assets_extra
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
for name in ['commerce.css', 'commerce.js', 'script.js', 'production.js']:
    (THEME / name).unlink(missing_ok=True)
(THEME / 'data').mkdir(exist_ok=True)
(THEME / 'data/site.json').write_text(json.dumps(data, ensure_ascii=False, separators=(',', ':')))

# Explicit package manifest also excludes accidental backups, editor files and stale outputs.
package_files = set(runtime_files) | {'readme.txt', 'data/site.json', 'style.css', 'notices.css', 'products.css', 'native.css', 'native-commerce.css', 'theme.js', 'functions.php', 'header.php', 'footer.php', 'index.php', 'page.php', 'searchform.php', 'comments.php', '404.php', 'sidebar.php', 'inc/import.php', 'inc/render.php', 'inc/woocommerce.php', 'inc/size-tables.php', 'inc/forms.php', 'inc/language.php', 'inc/translations.php', 'inc/seo.php', 'inc/swatches.php', 'inc/swatch-admin.php', 'inc/product-design.php', 'product-design.js', 'swatch-admin.js', 'product-swatches.js', 'languages/woocommerce-bs_BA.mo', 'languages/woocommerce-bs_BA.po', 'languages/README.md'} | assets
package_files.update(str(path.relative_to(THEME)) for path in (THEME / 'languages/woocommerce-js').glob('*.json'))
package_files.add('languages/storefront-bs.json')
output = ROOT / 'dist/slovenka.zip'
output.parent.mkdir(exist_ok=True)
with zipfile.ZipFile(output, 'w', zipfile.ZIP_DEFLATED) as archive:
    for name in sorted(package_files):
        archive.write(THEME / name, 'slovenka/' + name)
print(f'{output}\n{len(assets)} runtime assets; {len(unused_images)} unused photos removed; {output.stat().st_size / 1024 / 1024:.1f} MiB ZIP')
