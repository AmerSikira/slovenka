#!/usr/bin/env python3
"""Integration checks against a disposable localhost WordPress store, with mail disabled."""
import argparse, json, re, subprocess, urllib.request, urllib.parse, urllib.error, uuid
from pathlib import Path

parser=argparse.ArgumentParser()
parser.add_argument('--wp', required=True, help='wp-cli.phar path')
parser.add_argument('--path', required=True, help='Disposable WordPress directory')
parser.add_argument('--url', default='http://127.0.0.1:5191')
args=parser.parse_args()
assert urllib.parse.urlsplit(args.url).hostname in ['localhost','127.0.0.1'], 'Local test store required'
def wp(code):
    return subprocess.check_output(['php','-d','memory_limit=512M','-d','error_reporting=22527',args.wp,'eval',code,'--path='+args.path],text=True)
assert wp('echo has_filter("pre_wp_mail","__return_false") !== false ? "disabled":"active";').strip()=='disabled','Disable all test mail first'
config_url=json.loads(wp('echo wp_json_encode(get_permalink(get_option("slovenka_pages")["inquiry.html"]));'))
page=urllib.request.urlopen(config_url).read().decode()
config=json.loads(re.search(r'window.SlovenkaSite = (.*?);</script>',page).group(1))
base={'action':'mk_inquiry','nonce':config['inquiryNonce'],'lang':'en','name':'Local QA inquiry','email':'qa@example.invalid','company':'QA test','garmentType':'Trousers','description':'Synthetic local integration test project.','quantity':'100-500','timing':'flexible','services[]':['sewing','sampling']}
created=[]
def send(fields, attachment=None):
    if attachment:
        boundary='----QA'+uuid.uuid4().hex
        data=b''
        for key,values in fields.items():
            for value in values if isinstance(values,list) else [values]:
                data+=f'--{boundary}\r\nContent-Disposition: form-data; name="{key}"\r\n\r\n{value}\r\n'.encode()
        name,content,mime=attachment
        data+=f'--{boundary}\r\nContent-Disposition: form-data; name="attachment"; filename="{name}"\r\nContent-Type: {mime}\r\n\r\n'.encode()+content+b'\r\n'+f'--{boundary}--\r\n'.encode()
        content_type='multipart/form-data; boundary='+boundary
    else:
        data=urllib.parse.urlencode(fields,doseq=True).encode();content_type='application/x-www-form-urlencoded'
    request=urllib.request.Request(config['ajaxUrl'],data=data,headers={'Content-Type':content_type})
    try:
        response=urllib.request.urlopen(request);return response.status,json.loads(response.read())
    except urllib.error.HTTPError as error:
        return error.code,json.loads(error.read())
# Keep the rate-limit fixture local and isolated.
wp('delete_transient("mk_form_".hash_hmac("sha256","127.0.0.1",wp_salt()));')
try:
    for changes in [{'nonce':'bad'},{'email':'not-an-email'},{'name[]':'bad','name':None},{'kind':'invalid'},{'unitsPerStyle':'-3'}]:
        fields={**base,**changes};fields={k:v for k,v in fields.items() if v is not None}
        status,result=send(fields)
        assert status in [400,403] and (not isinstance(result,dict) or not result.get('success')), (changes,status,result)
    status,result=send(base,('bad.php',b'<?php echo "test";','application/x-php'))
    assert status==400 and not result['success'],result
    pdf=b'%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\n%%EOF\n'
    status,result=send(base,('technical-pack.pdf',pdf,'application/pdf'))
    assert status==200 and result['success'],result
    first=int(result['data']['reference'].split('-')[-1]);created.append(first)
    record=json.loads(wp(f'$id={first};echo wp_json_encode(["type"=>get_post_type($id),"status"=>get_post_status($id),"body"=>get_post($id)->post_content,"attachment"=>get_post_meta($id,"_mk_attachment",true),"mailed"=>get_post_meta($id,"_mk_email_delivered_to_mailer",true)]);'))
    assert record['type']=='mk_inquiry' and record['status']=='private' and not record['mailed']
    assert 'sewing, sampling' in record['body']
    attachment=Path(record['attachment']);assert attachment.is_file()
    assert not attachment.resolve().is_relative_to(Path(args.path).resolve()),'Attachment is public'
    status,result=send({'action':'mk_inquiry','nonce':config['inquiryNonce'],'kind':'contact','name':'Local QA contact','email':'qa@example.invalid','message':'Synthetic local contact submission.'})
    assert status==200 and result['success'],result
    created.append(int(result['data']['reference'].split('-')[-1]))
    # Verify the actual admin pages expose both restored tools.
    cookies=json.loads(wp('$u=get_users(["role"=>"administrator","number"=>1])[0];echo wp_json_encode([AUTH_COOKIE=>wp_generate_auth_cookie($u->ID,time()+300,"auth"),LOGGED_IN_COOKIE=>wp_generate_auth_cookie($u->ID,time()+300,"logged_in")]);'))
    cookie='; '.join(key+'='+value for key,value in cookies.items())
    def admin(path):
        request=urllib.request.Request(args.url+path,headers={'Cookie':cookie})
        return urllib.request.urlopen(request).read().decode()
    inbox=admin('/wp-admin/edit.php?post_type=mk_inquiry')
    assert 'Local QA inquiry' in inbox and 'Local QA contact' in inbox and 'post_type=mk_size_table' in inbox
    editor=admin('/wp-admin/post-new.php?post_type=mk_size_table')
    assert 'name="mk_size_file"' in editor and 'Import XLSX' in editor
    product_id=wp('echo slovenka_find("product","_slovenka_product","alba");').strip()
    editor=admin('/wp-admin/post.php?post='+product_id+'&action=edit')
    assert 'mk_size_table_ids[]' in editor and 'Tabele mjera' in editor
    # Tables, shared assignments and seed idempotency.
    result=json.loads(wp('$out=[];foreach(["alba","aria","coco"] as $key){$id=slovenka_find("product","_slovenka_product",$key);$out[$key]=mk_product_size_tables($id);}echo wp_json_encode($out);'))
    assert result['alba'][0]['tables'][0]['rows'][0]==['36','76','97','98.5']
    assert result['aria'][0]['tables'][0]['rows'][0]==['36','77','99','113.5']
    assert result['coco'][0]['tables'][0]['headers'][1]=='O GRUDI/2'
    assert result['coco'][0]['tables'][0]['rows'][-1]==['50','62','57','64','63','68.5','47']
    print('PASS: seeded manufacturer tables, exact values, private inquiry/contact persistence, private upload, failed mail persistence, nonce/field/type/upload validation.')
finally:
    for inquiry_id in created:
        wp(f'$path=get_post_meta({inquiry_id},"_mk_attachment",true);if($path && is_file($path))unlink($path);wp_delete_post({inquiry_id},true);')
    wp('delete_transient("mk_form_".hash_hmac("sha256","127.0.0.1",wp_salt()));')
