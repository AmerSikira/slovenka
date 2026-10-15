<?php
/** Reusable garment measurements imported from XLSX, without executing formulas. */
defined('ABSPATH') || exit;

add_action('init', function () {
    register_post_type('mk_size_table', [
        'labels' => ['name'=>'Tabele mjera', 'singular_name'=>'Tabela mjera', 'add_new_item'=>'Dodaj tabelu mjera', 'edit_item'=>'Uredi tabelu mjera'],
        'public'=>false, 'show_ui'=>true, 'show_in_menu'=>'edit.php?post_type=product',
        'supports'=>['title'], 'capability_type'=>'product', 'map_meta_cap'=>true,
    ]);
});

/** Read a bounded XML part; never extract ZIP entries or resolve external entities. */
function mk_size_xml($zip, $name) {
    $stat = $zip->statName($name);
    if (!$stat || $stat['size'] > 2 * MB_IN_BYTES) throw new RuntimeException('Nedostaje ili je prevelik XLSX XML zapis.');
    $text = $zip->getFromName($name);
    if ($text === false || stripos($text, '<!DOCTYPE') !== false || stripos($text, '<!ENTITY') !== false) throw new RuntimeException('Neispravan XLSX XML zapis.');
    $previous = libxml_use_internal_errors(true);
    try {
        $xml = simplexml_load_string($text, SimpleXMLElement::class, LIBXML_NONET);
        if (!$xml) throw new RuntimeException('Neispravan XLSX XML zapis.');
        return $xml;
    } finally {
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
    }
}

function mk_size_text($node) {
    $parts = $node->xpath('.//*[local-name()="t"]');
    return trim(implode('', array_map('strval', $parts ?: [])));
}

/** Normalize both size-across-columns and size-down-rows layouts. */
function mk_size_normalize($grid) {
    $is_size = static function ($value) {
        return (bool) preg_match('/^(?:[2-6][0-9]|[2-5]?X[SL]|XX[SL]|[SML]|OS|ONE SIZE)$/i', trim((string) $value));
    };
    foreach ($grid as $index => $line) {
        $size_columns = array_keys(array_filter($line, $is_size));
        if (count($size_columns) >= 2) {
            $headers = ['Veličina'];
            $measurements = [];
            foreach (array_slice($grid, $index + 1) as $row) {
                $label = implode(' ', array_filter(array_slice($row, 0, $size_columns[0]), static function ($v) { return $v !== ''; }));
                if ($label === '') continue;
                $headers[] = $label;
                $measurements[] = $row;
            }
            if (!$measurements) break;
            $rows = [];
            foreach ($size_columns as $column) {
                $row = [$line[$column]];
                foreach ($measurements as $measurement) $row[] = $measurement[$column] ?? '';
                $rows[] = $row;
            }
            return ['headers'=>$headers, 'rows'=>$rows];
        }
        $first = trim((string) ($line[0] ?? ''));
        if (preg_match('/^(VELIČINA|VELICINA|SIZE)$/iu', $first)) {
            $last = count($line) - 1;
            while ($last > 0 && $line[$last] === '') $last--;
            $headers = array_slice($line, 0, $last + 1);
            if (count($headers) < 2 || in_array('', $headers, true)) throw new RuntimeException('Svaka mjera mora imati naziv kolone.');
            $rows = [];
            foreach (array_slice($grid, $index + 1) as $row) {
                if (!$is_size($row[0] ?? '')) continue;
                $rows[] = array_slice(array_pad($row, count($headers), ''), 0, count($headers));
            }
            if ($rows) return ['headers'=>$headers, 'rows'=>$rows];
        }
    }
    return null;
}

function mk_import_size_table($path) {
    if (!class_exists('ZipArchive') || !function_exists('simplexml_load_string')) return new WP_Error('extensions', 'XLSX import zahtijeva PHP ZIP i SimpleXML ekstenzije.');
    if (!is_file($path) || filesize($path) > 5 * MB_IN_BYTES) return new WP_Error('file', 'Odaberite XLSX datoteku do 5 MB.');
    $zip = new ZipArchive();
    if ($zip->open($path) !== true) return new WP_Error('xlsx', 'Datoteka nije ispravna XLSX radna knjiga.');
    try {
        if ($zip->numFiles > 500) throw new RuntimeException('Radna knjiga sadrži previše zapisa.');
        $total = 0;
        for ($i = 0; $i < $zip->numFiles; $i++) $total += $zip->statIndex($i)['size'];
        if ($total > 20 * MB_IN_BYTES) throw new RuntimeException('Raspakovana radna knjiga je prevelika.');
        $strings = [];
        if ($zip->locateName('xl/sharedStrings.xml') !== false) {
            foreach (mk_size_xml($zip, 'xl/sharedStrings.xml')->xpath('/*[local-name()="sst"]/*[local-name()="si"]') as $item) $strings[] = mk_size_text($item);
        }
        $workbook = mk_size_xml($zip, 'xl/workbook.xml');
        $relations = [];
        foreach (mk_size_xml($zip, 'xl/_rels/workbook.xml.rels')->xpath('/*/*') as $rel) {
            if ((string) $rel['TargetMode'] === 'External') continue;
            $target = (string) $rel['Target'];
            if (str_contains($target, '..') || str_contains($target, ':')) continue;
            $relations[(string) $rel['Id']] = str_starts_with($target, '/') ? ltrim($target, '/') : 'xl/' . $target;
        }
        $tables = [];
        foreach ($workbook->xpath('//*[local-name()="sheet"]') as $sheet) {
            if ((string) $sheet['state'] === 'hidden' || (string) $sheet['state'] === 'veryHidden') continue;
            $attrs = $sheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships');
            $part = $relations[(string) $attrs['id']] ?? '';
            if (!$part) throw new RuntimeException('Neispravna veza radnog lista.');
            $grid = [];
            foreach (mk_size_xml($zip, $part)->xpath('//*[local-name()="sheetData"]/*[local-name()="row"]') as $row) {
                if (count($grid) >= 200) throw new RuntimeException('Najviše 200 redova po radnom listu.');
                $cells = array_fill(0, 50, '');
                foreach ($row->xpath('./*[local-name()="c"]') as $cell) {
                    if (!preg_match('/^([A-Z]+)[0-9]+$/', (string) $cell['r'], $match)) continue;
                    $column = 0;
                    foreach (str_split($match[1]) as $letter) $column = $column * 26 + ord($letter) - 64;
                    if ($column > 50) throw new RuntimeException('Najviše 50 kolona po radnom listu.');
                    $values = $cell->xpath('./*[local-name()="v"]');
                    if ($cell->xpath('./*[local-name()="f"]') && (!isset($values[0]) || (string) $values[0] === '')) throw new RuntimeException('Formula nema sačuvanu vrijednost. Otvorite i sačuvajte datoteku u Excelu pa ponovite import.');
                    $value = isset($values[0]) ? (string) $values[0] : '';
                    if ((string) $cell['t'] === 's') $value = $strings[(int) $value] ?? '';
                    if ((string) $cell['t'] === 'inlineStr') $value = mk_size_text($cell);
                    if ((string) $cell['t'] === 'e') throw new RuntimeException('Radni list sadrži grešku u ćeliji.');
                    $cells[$column - 1] = sanitize_text_field($value);
                }
                if (array_filter($cells, static function ($v) { return $v !== ''; })) $grid[] = $cells;
            }
            if (!$grid) continue;
            $table = mk_size_normalize($grid);
            if (!$table) throw new RuntimeException('Nije prepoznat raspored mjera na listu: ' . (string) $sheet['name']);
            $table['sheet'] = sanitize_text_field((string) $sheet['name']);
            $tables[] = $table;
        }
        if (!$tables) throw new RuntimeException('Datoteka ne sadrži tabelu mjera.');
        return $tables;
    } catch (Throwable $error) {
        return new WP_Error('import', $error->getMessage());
    } finally {
        $zip->close();
    }
}

add_action('post_edit_form_tag', function ($post) {
    if ($post->post_type === 'mk_size_table') echo ' enctype="multipart/form-data"';
});
add_action('add_meta_boxes', function () {
    add_meta_box('mk-size-import', 'Import XLSX', 'mk_size_import_box', 'mk_size_table', 'normal', 'high');
    add_meta_box('mk-product-size-tables', 'Tabele mjera', 'mk_product_size_box', 'product', 'side');
});
function mk_size_import_box($post) {
    wp_nonce_field('mk_size_import_' . $post->ID, 'mk_size_nonce');
    echo '<p><label for="mk-size-file">Naziv upišite iznad, odaberite XLSX datoteku i kliknite Objavi / Ažuriraj.</label></p><input id="mk-size-file" type="file" name="mk_size_file" accept=".xlsx"><p class="description">Do 5 MB. Veličine mogu biti u redovima ili kolonama. Prazni listovi se preskaču. Uvoze se sačuvane vrijednosti, bez izračunavanja formula; prvo sačuvajte izmjene u Excelu. Novi import zamjenjuje prethodne mjere tek nakon uspješne provjere.</p>';
    $error = get_user_meta(get_current_user_id(), '_mk_size_error_' . $post->ID, true);
    if ($error) {
        echo '<div class="notice notice-error inline"><p>' . esc_html($error) . '</p></div>';
        delete_user_meta(get_current_user_id(), '_mk_size_error_' . $post->ID);
    }
    $filename = get_post_meta($post->ID, '_mk_size_filename', true);
    if ($filename) echo '<p>Uvezeno: <strong>' . esc_html($filename) . '</strong></p>';
    foreach ((array) (get_post_meta($post->ID, '_mk_size_tables', true) ?: []) as $table) {
        echo '<div style="overflow:auto"><table class="widefat striped"><caption>' . esc_html($table['sheet']) . '</caption><thead><tr>';
        foreach ($table['headers'] as $header) echo '<th scope="col">' . esc_html($header) . '</th>';
        echo '</tr></thead><tbody>';
        foreach ($table['rows'] as $row) {
            echo '<tr>';
            foreach ($row as $i => $value) echo $i === 0 ? '<th scope="row">' . esc_html($value) . '</th>' : '<td>' . esc_html($value) . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table></div>';
    }
}
add_action('save_post_mk_size_table', function ($id) {
    if (wp_is_post_revision($id) || wp_is_post_autosave($id) || !current_user_can('edit_post', $id)) return;
    if (!isset($_POST['mk_size_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['mk_size_nonce'])), 'mk_size_import_' . $id)) return;
    $file = $_FILES['mk_size_file'] ?? null;
    if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) return;
    if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name']) || strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) !== 'xlsx') {
        $result = new WP_Error('upload', 'Upload nije uspio. Odaberite XLSX datoteku do 5 MB.');
    } else {
        $result = mk_import_size_table($file['tmp_name']);
    }
    if (is_wp_error($result)) {
        update_user_meta(get_current_user_id(), '_mk_size_error_' . $id, $result->get_error_message());
        return;
    }
    update_post_meta($id, '_mk_size_tables', $result);
    update_post_meta($id, '_mk_size_filename', sanitize_file_name($file['name']));
    delete_user_meta(get_current_user_id(), '_mk_size_error_' . $id);
});

function mk_product_size_box($post) {
    wp_nonce_field('mk_product_sizes_' . $post->ID, 'mk_product_sizes_nonce');
    $selected = array_map('absint', (array) get_post_meta($post->ID, '_mk_size_table_ids', true));
    $tables = get_posts(['post_type'=>'mk_size_table', 'post_status'=>'publish', 'numberposts'=>-1, 'orderby'=>'title', 'order'=>'ASC']);
    echo '<p>Odaberite jednu ili više tabela. Ista tabela se može koristiti na više proizvoda.</p><div style="max-height:260px;overflow:auto">';
    foreach ($tables as $table) {
        if (!get_post_meta($table->ID, '_mk_size_tables', true)) continue;
        echo '<p><label><input type="checkbox" name="mk_size_table_ids[]" value="' . esc_attr($table->ID) . '" ' . checked(in_array($table->ID, $selected, true), true, false) . '> ' . esc_html($table->post_title) . '</label></p>';
    }
    echo '</div><p><a href="' . esc_url(admin_url('post-new.php?post_type=mk_size_table')) . '">Dodaj novu tabelu mjera</a></p>';
}
add_action('save_post_product', function ($id) {
    if (wp_is_post_revision($id) || wp_is_post_autosave($id) || !current_user_can('edit_post', $id)) return;
    if (!isset($_POST['mk_product_sizes_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['mk_product_sizes_nonce'])), 'mk_product_sizes_' . $id)) return;
    $ids = array_values(array_unique(array_filter(array_map('absint', (array) ($_POST['mk_size_table_ids'] ?? [])), static function ($table_id) {
        return get_post_type($table_id) === 'mk_size_table' && get_post_status($table_id) === 'publish' && get_post_meta($table_id, '_mk_size_tables', true);
    })));
    update_post_meta($id, '_mk_size_table_ids', $ids);
});

function mk_product_size_tables($product_id) {
    $result = [];
    foreach (array_filter(array_map('absint', (array) get_post_meta($product_id, '_mk_size_table_ids', true))) as $id) {
        if (get_post_type($id) !== 'mk_size_table' || get_post_status($id) !== 'publish') continue;
        $tables = get_post_meta($id, '_mk_size_tables', true);
        if ($tables) $result[] = ['id'=>(int) $id, 'name'=>get_the_title($id), 'tables'=>$tables];
    }
    return $result;
}
