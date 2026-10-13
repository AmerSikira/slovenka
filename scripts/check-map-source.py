#!/usr/bin/env python3
"""Check a map resource, including policy-blocked images returned with HTTP 200."""
import json
from pathlib import Path
import subprocess
import sys
from tempfile import TemporaryDirectory


def check(url):
    with TemporaryDirectory() as directory:
        headers_path = Path(directory) / 'headers'
        body_path = Path(directory) / 'body'
        subprocess.run(['curl', '--fail', '--silent', '--show-error', '--location', '--max-time', '20', '--dump-header', str(headers_path), '--output', str(body_path), url], check=True)
        headers = {}
        for line in headers_path.read_text().splitlines():
            if ':' in line:
                name, value = line.split(':', 1)
                headers[name.lower()] = value.strip()
        blocked = headers.get('x-blocked')
        if blocked:
            raise RuntimeError(f'Map access blocked despite HTTP success: {blocked}')
        content_type = headers.get('content-type', '')
        body = body_path.read_bytes()
        if not body or 'text/html' in content_type:
            raise RuntimeError(f'Expected map data, received {content_type!r}')
        print(f'OK: {url} ({content_type}, {len(body)} bytes)')
        return json.loads(body) if 'json' in content_type else None


try:
    resource = check(sys.argv[1])
    # A vector style must also reach its TileJSON and the actual address-area tile.
    if resource and 'sources' in resource:
        source = next(value for value in resource['sources'].values() if value.get('type') == 'vector')
        tiles = check(source['url']) if 'url' in source else source
        tile_url = tiles['tiles'][0].replace('{z}', '11').replace('{x}', '1123').replace('{y}', '743')
        check(tile_url)
except Exception as error:
    print(f'FAIL: {error}', file=sys.stderr)
    sys.exit(1)
