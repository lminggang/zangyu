import requests
from lxml import etree
import glob
import os
import time
import hashlib
import copy

urls = [
	'http://tb.xzxw.com/'
	# 'http://tibet.people.com.cn/',
	# 'http://tibet.people.com.cn/309822/',
	# 'http://tibet.people.com.cn/309824/',
	# 'http://tibet.people.com.cn/309825/',
	# 'http://tibet.people.com.cn/309826/',
	# 'http://tibet.people.com.cn/309828/',
	# 'http://tibet.people.com.cn/309829/',
	# 'http://tibet.people.com.cn/309836/',
	# 'http://tibet.people.com.cn/309840/'
]

save_path_base = 'http://tb.xzxw.com/'
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
	dom = etree.HTML(html)
	hrefs = dom.xpath('//a/@href')
	for href in hrefs:
		page_addr = clean_href(href.strip())
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
				get_href(page_addr)


def clean_href(href):
	url_base = urls[0]

	if url_base == href:
		href = ''
	elif 'http://tibet.people.com.cn/index.html' == href:
		href = ''
	elif 'https://' in href:
		href = ''
	elif 'http://' in href:
		if url_base not in href:
			href = ''
	elif '.html' in href:
		if '/index.html' == href:
			href = ''
		else:
			href = '{}{}'.format(url_base, href[1:len(href)])
	elif '#' in href:
		href = ''
	elif '.html' not in href and href != '':
		href =  '{}reold/{}/'.format(url_base, href)

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

	while True:
		try:
			get_href(urls[0])
		except Exception as e:
			print(e)
		time.sleep(10)

	print(set_urls)

	# href = clean_href('http://tibet.people.com.cn/index.html')
	# print(href)
	# for x in range(10000):
	# 	print(x + 1)
	# 	response = requests.get('http://tibet.people.com.cn/15778943.html')
	# 	print(response.text)


