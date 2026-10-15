#!/usr/bin/env python3
"""Check the production ZIP has a complete, deduplicated runtime asset graph."""
from pathlib import Path
import hashlib, json, re, zipfile

ROOT = Path(__file__).resolve().parents[1]
with zipfile.ZipFile(ROOT / 'dist/slovenka.zip') as archive:
    files = {name.removeprefix('slovenka/'): archive.read(name) for name in archive.namelist()}
assert all(not part.startswith('.') for name in files for part in Path(name).parts), 'Hidden build files leaked into the ZIP'
assert not {'commerce.js', 'commerce.css'} & files.keys(), 'Browser-only commerce files were packaged'
assert not any(name.startswith('assets/img/') for name in files), 'Original authoring photos were packaged'
assert not any(name.endswith(('.jpg', '.jpeg')) for name in files), 'Redundant JPEG fallbacks were packaged'
data = json.loads(files['data/site.json'])
assert len(data['pages']) == 50, 'A bilingual page was lost'
assert all(name in files for name in ['inc/size-tables.php','inc/forms.php','woocommerce.php','sidebar.php']), 'Admin feature modules were omitted'
assert {table['key'] for table in data['size_tables']} == {'alba','aria','coco'}, 'Manufacturer tables were omitted'
assert len(data['products']) == 12, 'A product or MIJA draft was lost'
assert len(data['images']) == 68, 'The active photo registry changed unexpectedly'
for image in data['images'].values():
    assert image['src'] == image['fallback'], 'Separate fallback photo remains'
    assert image['src'] in files, 'A displayed photo is missing'
pattern = re.compile(r'assets/[A-Za-z0-9_./%+\-]+')
references = json.dumps({key: value for key, value in data.items() if key != 'image_aliases'}, ensure_ascii=False)
references += '\n'.join(content.decode() for name, content in files.items() if name.endswith(('.css', '.js', '.svg')))
used = {path for path in pattern.findall(references) if not path.endswith('/')}
licences = {'assets/fonts/OFL.txt', 'assets/icons-LICENSE.txt', 'assets/vendor/gsap/LICENSE.txt', 'assets/vendor/maplibre/LICENSE.txt'}
assets = {name for name in files if name.startswith('assets/')}
assert used <= assets, f'Missing runtime files: {sorted(used - assets)}'
assert assets <= used | licences, f'Unused assets: {sorted(assets - used - licences)}'
hashes = {}
for name in sorted(assets):
    if Path(name).suffix not in ['.png', '.webp', '.svg']:
        continue
    digest = hashlib.sha256(files[name]).hexdigest()
    assert digest not in hashes, f'Duplicate photo/image: {hashes.get(digest)} and {name}'
    hashes[digest] = name
for page in data['pages'].values():
    assert 'commerce.css' not in page['head'] and 'commerce.js' not in page['head'], 'Unused preview dependency remains'
    if page['route'] in ['/cart', '/checkout']:
        assert page['content'] in ['[woocommerce_cart]', '[woocommerce_checkout]'], 'Unused preview markup remains'
print(f'PASS: {len(files)} packaged files, {len(assets)} required assets, complete pages/catalogue, no missing assets, duplicate images or preview files.')
