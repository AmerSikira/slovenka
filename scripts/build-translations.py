#!/usr/bin/env python3
"""Build script-specific Jed catalogues from the upstream PO plus reviewed storefront terms.
Requires polib only at build time; WordPress uses the packaged JSON directly.
"""
from pathlib import Path
import hashlib, json
import polib
ROOT = Path(__file__).resolve().parents[1]
LANG = ROOT / 'wordpress/slovenka/languages'
po = polib.pofile(str(LANG / 'woocommerce-bs_BA.po'))
overrides = json.loads((LANG / 'storefront-bs.json').read_text())
header = {'domain':'woocommerce','lang':'bs_BA','plural-forms':po.metadata['Plural-Forms']}
catalogues = {}
for entry in po:
    if entry.obsolete:
        continue
    key = (entry.msgctxt + '\u0004' if entry.msgctxt else '') + entry.msgid
    values = ([entry.msgstr_plural.get(i, '') for i in range(3)] if entry.msgid_plural else [entry.msgstr])
    if entry.msgid in overrides:
        value = overrides[entry.msgid]
        values = value if isinstance(value, list) else [value]
    if not all(values) or 'fuzzy' in entry.flags:
        continue
    for source, _ in entry.occurrences:
        if source.startswith(('assets/client/blocks/', 'assets/js/frontend/')) and source.endswith('.js'):
            catalogues.setdefault(source.replace('.min.js', '.js'), {})[key] = values
out = LANG / 'woocommerce-js'
out.mkdir(exist_ok=True)
for old in out.glob('*.json'):
    old.unlink()
for source, messages in catalogues.items():
    # Include supplemental strings so new WC minor releases also get these terms.
    for key, value in overrides.items():
        messages[key] = value if isinstance(value, list) else [value]
    data = {'translation-revision-date':'2026-10-16 00:00+0000','generator':'Slovenka storefront translation build','source':source,'domain':'woocommerce','locale_data':{'messages':{'':header, **messages}}}
    (out / ('woocommerce-bs_BA-' + hashlib.md5(source.encode()).hexdigest() + '.json')).write_text(json.dumps(data, ensure_ascii=False, separators=(',', ':')))
print(f'{len(catalogues)} JavaScript catalogues; {len(overrides)} reviewed storefront translations')
