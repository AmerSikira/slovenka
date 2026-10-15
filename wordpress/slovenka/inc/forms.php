<?php
defined('ABSPATH') || exit;
add_action('init', function () {
    register_post_type('mk_inquiry',['label'=>'Upiti / Inquiries','public'=>false,'show_in_rest'=>false,'exclude_from_search'=>true,'show_ui'=>true,'supports'=>['title','editor'],'capability_type'=>'post','map_meta_cap'=>true,'menu_icon'=>'dashicons-email-alt']);
});
add_action('wp_ajax_mk_inquiry','mk_inquiry_request');
add_action('wp_ajax_nopriv_mk_inquiry','mk_inquiry_request');
function mk_inquiry_request() {
    check_ajax_referer('mk-commerce','nonce');
    $en = ($_POST['lang'] ?? '') === 'en';
    $limit = 'mk_form_' . hash_hmac('sha256', $_SERVER['REMOTE_ADDR'] ?? '', wp_salt());
    if ((int)get_transient($limit) >= 5) wp_send_json_error(['message'=>$en?'Please wait before sending another inquiry.':'Molimo sačekajte prije slanja novog upita.'],429);
    foreach ($_POST as $key => $value) {
        if ($key === 'services') {
            if (!is_array($value) || count($value) > 10 || array_filter($value, 'is_array')) wp_send_json_error(['message'=>'Invalid services.'],400);
        } elseif (!is_scalar($value)) wp_send_json_error(['message'=>'Invalid form field.'],400);
    }
    $kind = sanitize_key($_POST['kind'] ?? 'inquiry');
    if (!in_array($kind, ['inquiry','contact'], true)) wp_send_json_error(['message'=>'Invalid inquiry type.'],400);
    $required = $kind === 'contact' ? ['name','email','message'] : ['name','email','company','garmentType','description'];
    foreach ($required as $field) if (empty(trim((string)($_POST[$field] ?? '')))) wp_send_json_error(['message'=>$en?'Please complete all required fields.':'Molimo popunite sva obavezna polja.'],400);
    $email = sanitize_email(wp_unslash($_POST['email'] ?? ''));
    if (!is_email($email)) wp_send_json_error(['message'=>$en?'Enter a valid email address.':'Unesite ispravnu e-mail adresu.'],400);
    $bounds = ['name'=>120,'email'=>200,'company'=>150,'phone'=>40,'topic'=>100,'country'=>100,'garmentType'=>40,'quantity'=>40,'unitsPerStyle'=>10,'timing'=>20,'targetDate'=>10,'description'=>5000,'message'=>5000];
    foreach ($bounds as $field => $max) {
        if (isset($_POST[$field]) && (function_exists('mb_strlen') ? mb_strlen(wp_unslash((string)$_POST[$field])) : strlen(wp_unslash((string)$_POST[$field]))) > $max) wp_send_json_error(['message'=>$en?'A form field is too long.':'Unos u polju je predug.'],400);
    }
    if ($kind === 'inquiry') {
        if (!in_array($_POST['garmentType'], ['Trousers','Blazers','Suits','Other'], true)) wp_send_json_error(['message'=>'Invalid garment type.'],400);
        if (isset($_POST['quantity']) && !in_array($_POST['quantity'], ['undecided','under-100','100-500','500-1000','over-1000'], true)) wp_send_json_error(['message'=>'Invalid quantity.'],400);
        if (!empty($_POST['unitsPerStyle']) && !filter_var($_POST['unitsPerStyle'], FILTER_VALIDATE_INT, ['options'=>['min_range'=>1,'max_range'=>1000000]])) wp_send_json_error(['message'=>'Invalid units per style.'],400);
        if (isset($_POST['timing']) && !in_array($_POST['timing'], ['flexible','target'], true)) wp_send_json_error(['message'=>'Invalid timing.'],400);
        if (($_POST['timing'] ?? '') === 'target') {
            $date = DateTimeImmutable::createFromFormat('!Y-m-d', $_POST['targetDate'] ?? '');
            if (!$date || $date->format('Y-m-d') !== $_POST['targetDate'] || $_POST['targetDate'] < wp_date('Y-m-d')) wp_send_json_error(['message'=>$en?'Choose a valid future date.':'Odaberite ispravan budući datum.'],400);
        }
        if (array_diff($_POST['services'] ?? [], ['sewing','sampling','patterns','sourcing','packaging','undecided'])) wp_send_json_error(['message'=>'Invalid services.'],400);
    }
    $fields = [];
    foreach (['name','email','company','phone','topic','country','garmentType','quantity','unitsPerStyle','timing','targetDate','services','description','message'] as $field) {
        if (isset($_POST[$field])) $fields[$field] = is_array($_POST[$field]) ? implode(', ', array_map('sanitize_text_field',wp_unslash($_POST[$field]))) : sanitize_textarea_field(wp_unslash($_POST[$field]));
    }
    if (strlen($fields['description'] ?? $fields['message'] ?? '') > 10000) wp_send_json_error(['message'=>'Message is too long.'],400);
    $attachment = '';
    if (!empty($_FILES['attachment']['name'])) {
        $file = $_FILES['attachment'];
        foreach (['name','tmp_name','error','size'] as $field) if (!isset($file[$field]) || !is_scalar($file[$field])) wp_send_json_error(['message'=>'Invalid attachment.'],400);
        if (!is_uploaded_file($file['tmp_name'])) wp_send_json_error(['message'=>'Invalid upload.'],400);
        if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > 10 * MB_IN_BYTES) wp_send_json_error(['message'=>$en?'Attachment must be under 10 MB.':'Prilog mora biti manji od 10 MB.'],400);
        $valid = wp_check_filetype_and_ext($file['tmp_name'],$file['name'],['pdf'=>'application/pdf','jpg|jpeg'=>'image/jpeg','png'=>'image/png']);
        if (!$valid['ext'] || !$valid['type']) wp_send_json_error(['message'=>'PDF, JPG or PNG files only.'],400);
        // Retain inquiry attachments outside the public WordPress document root.
        $webroot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: realpath(ABSPATH);
        $directory = apply_filters('mk_private_inquiry_directory', trailingslashit(dirname($webroot)) . 'mk-private-inquiries/');
        if (!wp_mkdir_p($directory)) wp_send_json_error(['message'=>'Attachment storage is unavailable. Please contact us by email.'],500);
        $resolved = realpath($directory);
        if (!$resolved || $resolved === $webroot || str_starts_with($resolved, trailingslashit($webroot))) wp_send_json_error(['message'=>'Private attachment storage is unavailable.'],500);
        $attachment = trailingslashit($directory) . wp_generate_uuid4() . '-' . sanitize_file_name($file['name']);
        if (!$attachment || !move_uploaded_file($file['tmp_name'],$attachment)) wp_send_json_error(['message'=>'Attachment could not be received.'],500);
    }
    $body = '';
    foreach ($fields as $label=>$value) $body .= $label . ': ' . $value . "\n\n";
    $id = wp_insert_post(wp_slash(['post_type'=>'mk_inquiry','post_status'=>'private','post_title'=>($kind === 'contact'?'Kontakt':'Proizvodnja') . ' — ' . $fields['name'],'post_content'=>$body]),true);
    if (is_wp_error($id)) { if($attachment)unlink($attachment); wp_send_json_error(['message'=>'Could not save inquiry. Please try again.'],500); }
    try { $sent = wp_mail(get_option('mk_inquiry_email','slovenka.dd@gmail.com'),'MK Slovenka — Upit #' . $id,$body,['Reply-To: ' . $email],$attachment?[$attachment]:[]); } catch (Throwable $error) { $sent = false; }
    if ($attachment) { update_post_meta($id,'_mk_attachment',$attachment); update_post_meta($id,'_mk_attachment_name',sanitize_file_name($_FILES['attachment']['name'])); }
    update_post_meta($id,'_mk_email_delivered_to_mailer',(bool)$sent);
    set_transient($limit,(int)get_transient($limit)+1,HOUR_IN_SECONDS);
    wp_send_json_success(['reference'=>'MK-' . $id,'message'=>$en?'Your inquiry has been received. Our team will get back to you.':'Vaš upit je zaprimljen. Naš tim će vam se javiti.']);
}

add_action('add_meta_boxes_mk_inquiry', function () {
    add_meta_box('mk-inquiry-file','Prilog / Attachment',function ($post) {
        $path = get_post_meta($post->ID,'_mk_attachment',true);
        if (!$path || !is_file($path)) { echo '<p>No attachment.</p>'; return; }
        $url = wp_nonce_url(admin_url('admin-post.php?action=mk_inquiry_download&id=' . $post->ID),'mk_inquiry_download_' . $post->ID);
        echo '<a href="' . esc_url($url) . '">' . esc_html(get_post_meta($post->ID,'_mk_attachment_name',true)) . '</a>';
    },'mk_inquiry','side');
});
add_action('admin_post_mk_inquiry_download', function () {
    $id = absint($_GET['id'] ?? 0);
    if (!current_user_can('edit_post',$id) || get_post_type($id) !== 'mk_inquiry') wp_die('Access denied.', '', ['response'=>403]);
    check_admin_referer('mk_inquiry_download_' . $id);
    $path = get_post_meta($id,'_mk_attachment',true);
    if (!$path || !is_file($path)) wp_die('File not found.', '', ['response'=>404]);
    nocache_headers(); header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . sanitize_file_name(get_post_meta($id,'_mk_attachment_name',true)) . '"');
    header('Content-Length: ' . filesize($path)); readfile($path); exit;
});

add_filter('manage_mk_inquiry_posts_columns', function ($columns) {
    $columns['mk_mail'] = 'Obavijest e-mailom';
    return $columns;
});
add_action('manage_mk_inquiry_posts_custom_column', function ($column, $id) {
    if ($column === 'mk_mail') echo get_post_meta($id, '_mk_email_delivered_to_mailer', true) ? 'Predano mail serveru' : 'Upit sačuvan; e-mail nije poslan';
}, 10, 2);
