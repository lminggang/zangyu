import requests
from lxml import etree
import glob
import os
import time
import hashlib
import copy
import sys

hostname = sys.argv[0]

if not hostname:
    print('not fond hostname')
    exit()

hostname = hostname[0:-3]


urls = [
	'http://' + hostname
]

save_path_base = hostname
if not os.path.exists(save_path_base):
	os.makedirs(save_path_base)


set_urls = set()
num = 0

def create_task_id(page_addr=''):
    m = hashlib.md5()
    b = page_addr.encode(encoding='utf-8')
    m.update(b)
    task_id = m.hexdigest().upper()
    return task_id

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

def get_href(url):
	global num
	num += 1

	# time.sleep(1)
	try:
		response = requests.get(url, timeout=5)
	except:
		return
	print(num)
	html = response.text
	if len(html) < 3000:
		print('小于3000字符')

	dom = etree.HTML(html)
	hrefs = dom.xpath('//a/@href')

	for href in hrefs:
		page_addr = clean_href(url, href.strip())
		if page_addr != '' and page_addr not in urls and page_addr not in set_urls:
			save_path = './{}/{}.webloc'.format(save_path_base, create_task_id(page_addr))
			if os.path.exists(save_path):
				continue

			print(page_addr)
			set_urls.add(page_addr)

			if page_addr[len(page_addr) - 5:] == '.html':
				with open(save_path, 'w') as file:
					content = get_webloc_xml(page_addr)
					file.write(content)
			else:
				page_addr = page_addr.replace('target=', '')
				get_href(page_addr)


def clean_href(url, href):

	if href == '/':
		return ''
	if 'javascript' in href:
		return ''
    
	if 'register' in href:
		return ''
    
	if 'login' in href:
		return ''
	
	# if 'http://tb.xzxw.com/xw/sz' in href:
	# 	print(href)
	# 	raise Exception('进入到 index 了')
	# 	return href

	url_base = url
	print('*************', url, href)

	if urls[0] == href:
		href = ''
	elif url_base + '/index.html' == href:
		href = ''
	elif 'https://' in href:
		href = ''
	elif 'http://' in href:
		if urls[0] not in href:
			href = ''
	elif '.html' in href:
		if '/index.html' == href:
			href = ''
		else:
			href = '{}{}'.format(url_base, href[1:len(href)])
	elif '.aspx' in href:
		if '/' == href[0]:
			href = '{}{}'.format(url, href)
		else:
			href = '{}/{}'.format('/'.join(url.split('/')[:3]), href)
	elif '#' in href:
		href = ''
	elif '.html' not in href and href != '':
		if '../' in href:
			str_count = href.count('../')
			url_base = url_base.split('/')[:3] + [url for url in url_base.split('/')[3:] if url != '']
			url_base = '/'.join(url_base[:-str_count])
			href =  '{}{}'.format(url_base, href.replace('../', '/'))
		elif './' in href:
			href =  '{}{}'.format(url_base, href.replace('./', '/'))
		else:
			href =  '{}{}'.format(url_base, href)
		
		href_list = href.split('/')
		href = '/'.join(href_list[:3] + [href for href in href_list[3:] if href != ''])
		href = href.replace('target=', '')

	return href


if __name__ == "__main__":
	# get_href(urls[0])
	# for url in urls[1:]:
	# 	for i in range(0, 20):
	# 		if i == 0:
	# 			copy_url = '{}index.html'.format(url)
	# 		else:
	# 			copy_url = '{}index{}.html'.format(url, i)
	# 		print(copy_url)
	# 		get_href(copy_url)

	# while True:
	# 	try:
	# 		get_href(urls[0])
	# 	except Exception as e:
	# 		print('报错了！！！！！')
	# 		print(e)
	# 	time.sleep(10)

	get_href(urls[0])
	# get_href('http://tb.xzxw.com/xw/sz/index.html')
	

	print(set_urls)

	# href = clean_href('http://tibet.people.com.cn/index.html')
	# print(href)
	# for x in range(10000):
	# 	print(x + 1)
	# 	response = requests.get('http://tibet.people.com.cn/15778943.html')
	# 	print(response.text)


