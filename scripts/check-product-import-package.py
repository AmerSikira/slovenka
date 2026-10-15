#!/usr/bin/env python3
"""Validate approved retail prices, ICON ranges and the generated import outputs."""
import csv
import io
import json
import re
import zipfile
from pathlib import Path

root = Path(__file__).resolve().parents[1]
expected = {'pepito':123.5, 'signature':123.5, 'signature-trousers':65, 'ami':123.5, 'coco':123.5, 'jakna-hasi':190, 'over':175, 'mija':220, 'aria':75, 'hasi':65, 'icon-extra':75, 'alba':65}
with zipfile.ZipFile(root / 'dist/slovenka-product-import.zip') as archive:
    data = json.loads(archive.read('slovenka-catalogue-import/data/catalogue.json'))
products = {item['id']:item for item in data['products']}
assert set(products) == set(expected)
for key, price in expected.items():
    assert products[key]['price'] == price, f'Wrong import price: {key}'
assert products['hasi']['name'] == 'ICON'
assert products['hasi']['sizes'] == ['36','38','40','42','44','46']
assert products['icon-extra']['name'] == 'ICON EXTRA'
assert products['icon-extra']['sizes'] == ['48','50','52','54']
assert products['mija']['status'] == 'draft'
rows = list(csv.DictReader((root / 'dist/slovenka-products.csv').read_text(encoding='utf-8-sig').splitlines()))
assert len(rows) == 102
for row in rows:
    if row['Type'] == 'variable':
        assert not row['Regular price']
        continue
    key = (row['Parent'] or row['SKU']).removeprefix('slovenka-')
    assert row['Regular price'] == f'{expected[key]:.2f}', f'Wrong CSV price: {row["SKU"]}'
price_bytes = (root / 'dist/slovenka-price-updates.csv').read_bytes()
updates = list(csv.DictReader(io.StringIO(price_bytes.decode('utf-8-sig'))))
assert len(updates) == 91
assert all(set(row) == {'SKU','Regular price'} for row in updates), 'Unexpected destructive update fields'
assert {row['SKU']:row['Regular price'] for row in updates} == {row['SKU']:row['Regular price'] for row in rows if row['Type'] in ['variation','simple']}
with zipfile.ZipFile(root / 'dist/slovenka-products-csv-and-images.zip') as archive:
    assert archive.read('price-updates.csv') == price_bytes
print('PASS: approved prices, ICON names/ranges, MIJA draft, 102 full CSV rows and 91 price-only updates; matching ZIP outputs')
