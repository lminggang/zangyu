import requests
from lxml import etree
import glob
import os

def recursion(doms):
	for dom in doms:
		now_doms = dom.xpath('*')
		recursion(now_doms)
		contents.append(''.join(dom.xpath('text()')))
	return

if __name__ == '__main__':
	num = 0
	contents = list()
	save_path_base = 'result'
	chars = [chr(ind) for ind in range(0xf00, 0x0fff + 1)]
	# html_paths = glob.glob('article/*/*.html')
	html_paths = glob.glob('people/*.html')
	for html_path in html_paths:
		num += 1
		# if num > 5:
		# 	break
		
		with open(html_path, 'r') as file:
			print('{} {}'.format(num, html_path))
			html = ''.join(file.readlines())
			if not html.strip():
				continue
			dom = etree.HTML(html)
			recursion(dom.xpath('//body/*'))

			res = [content.replace(' ', '').replace('\n', '').replace('\n', '').replace('\t', '') for content in contents if content.replace(' ', '').replace('\n', '').replace('\n', '').replace('\t', '') != '' and len(content.replace(' ', '').replace('\n', '').replace('\n', '').replace('\t', '')) >= 100]
		
		contents = list()

		source_rows = list()
		target_rows = list()
		for i in range(0, len(res)):
			row = res[i]
			content = str()
			for content_char in row:
				if content_char in chars:
					content += content_char
			if len(content) < 100:
				continue

			target_rows.append(content)
			source_rows.append(row)

		# save_path_folder = '{}/{}/{}'.format(save_path_base, html_path.split('/')[1], html_path.split('/')[2].replace('.html', ''))
		# if not os.path.exists(save_path_folder):
		# 	os.makedirs(save_path_folder)
		# source_path = '{}/{}'.format(save_path_folder, 'source')
		# target_path = '{}/{}'.format(save_path_folder, 'target')

		with open('target', 'a+') as file:
				# rows.append('{}   {}'.format(content, len(content)))
			if not target_rows:
				continue
			content = '\n'.join(target_rows)
			file.write(content)

		# print(content)
		with open('source', 'a+') as file:
			content = '\n'.join(source_rows)
			file.write(content)
		# print(content)
		# print('*'*111)

		# save
		# print(res)
		# print(len(res))



	# url = 'https://www.qhtibetan.com/content/5d5b6608e138231adfac877a.html'
	# html = requests.get(url)
	# b = etree.HTML(html.text)
	# recursion(b.xpath('//body/*'))
	# res = [content.strip() for content in contents if content.strip() != '' and len(content.strip()) >= 100]
	# contents = list()
	# print(res)


