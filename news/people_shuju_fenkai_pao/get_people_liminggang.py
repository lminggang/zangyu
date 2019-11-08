import glob
import time
import requests
from lxml import etree
import hashlib

def get_webloc_file_list():
	webloc_file_list = glob.glob('people_liminggang/*.webloc')
	return webloc_file_list

def get_webloc_str(path):
	with open(path, 'r') as file:
		row_list = file.readlines()
		webloc_str = ''.join(row_list)
	return webloc_str

def get_page_addr(webloc_str):
	dom = etree.HTML(webloc_str)
	page_addr = dom.xpath('//string/text()')
	if not page_addr:
		return ''
	page_addr = page_addr[0].strip()
	return page_addr

def run():
	num = 0
	webloc_file_list = get_webloc_file_list()
	for webloc_file in webloc_file_list:
		num += 1
		print(webloc_file)
		webloc_str = get_webloc_str(webloc_file)
		page_addr = get_page_addr(webloc_str)
		if page_addr == '':
			continue
		if 'http' not in page_addr:
			page_addr = 'http://tibet.cpc.people.com.cn' + page_addr
		task_id = create_task_id(page_addr)
		print(num, page_addr, task_id)
		if glob.glob('liminggang/{}*'.format(task_id)):
			continue
		if num % 1000 == 0:
			time.sleep(1)
		html = get_html(page_addr)
		if html == '':
			continue
		save_html(task_id, html)
		save_webloc(task_id, html)

def get_html(page_addr):
    # time.sleep(1)
    try:
    	result = requests.get(page_addr, timeout=2)
    except Exception as e:
    	result = None
    	save_error(page_addr, '超时...')
    	print('超时...')

    if not result:
    	return ''

    if result.status_code != 200:
    	save_error(page_addr, '{}...'.format(result.status_code))
    	print(result.status_code)

    try:
    	html = result.content.decode('utf-8')
    except Exception as e:
    	return ''
    if len(html) < 5000:
        save_error(page_addr, '小于5000字')
        # raise Exception("输入验证码...")
        print('小于5000字..')
        return ''
    return html

def get_webloc_xml(page_addr=''):
    webloc_xml = '''
        <?xml version="1.0" encoding="UTF-8"?>
        <!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
        <plist version="1.0">
            <dict>
                <key>URL</key>
                <string>{page_addr}</string>
            </dict>
        </plist>
    '''.format(page_addr=page_addr)
    return webloc_xml

def create_task_id(page_addr=''):
    m = hashlib.md5()
    b = page_addr.encode(encoding='utf-8')
    m.update(b)
    task_id = m.hexdigest().upper()
    return task_id

def save_html(task_id, content):
	save_path = 'liminggang/{}.html'.format(task_id)
	save(save_path, content)

def save_webloc(task_id, content):
	save_path = 'liminggang/{}.webloc'.format(task_id)
	save(save_path, content)

def save(path, content):
	with open(path, 'w') as file:
		file.write(content)

def save_error(page_addr, err_str):
	with open('error.log', 'a+') as file:
		content = '{} {} {}\n'.format(time.time(), page_addr, err_str)
		file.write(content)

if __name__ == '__main__':
	run()
