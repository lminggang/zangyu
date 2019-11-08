from DBSReptileTools import DBSReptileTools
from lxml import etree
from urllib import parse
from queue import Queue
import requests
import sys
import os
import re


hostname = sys.argv[0]

if not hostname:
    print('not fond hostname')
    exit()

hostname = hostname[0:-3]

q = Queue(maxsize=0)

# url = 'http://{}'.format(hostname)
url_base = 'http://{}/bo'.format(hostname)
used_urls = set()

num = 0

dbs_tools = DBSReptileTools(2)
def download_html(url):
    global num
    q.put(url)
    while True:
        url = q.get()
        num += 1
        print(num, url)
        # 判断当前url是否已经使用过，如果使用过则直接返回
        if url in used_urls:
            # num -= 1
            continue
        else:
            if url_base != url:
                used_urls.add(url)
        # 获取页面内容
        try:
            html = dbs_tools.get_html(page_addr=url)
            if check_is_save(url):
                save_content(url, html)
                continue
            if html == '':
                # num -= 1
                continue
            dom = etree.HTML(html)
        except:
            continue
        # 解析页面内容中所有的a标签
        # dom = etree.HTML(html)
        hrefs = dom.xpath('//a/@href')
        pattern = r'createPageHTML\(([\d]*?),[\s\S]*?"html"\)'
        page_max = re.findall(pattern, html)
        
        if page_max and page_max[0] != '':
            page_list = list(range(1, int(page_max[0])))
            page_list = ['index_{}.html'.format(page) for page in page_list]
            hrefs.extend(page_list)
            # print(hrefs)

        # 遍历a标签如果
        for href in hrefs:
            new_full_url = parse.urljoin(url, href)
            new_full_url = check_url(new_full_url)
            # print(new_full_url)
            if new_full_url != '':
                q.put(new_full_url)
                # task = threading.Thread(target=download_html, args=(new_full_url))
                # task.start()
                # download_html(new_full_url)
        # num -= 1

def check_url(new_full_url):
    # print(url_base)
    # print(new_full_url)
    if url_base not in new_full_url:
        return ''
    return new_full_url

def check_is_save(page_addr):
    if '.html' == page_addr[-5:] and 'index_' not in page_addr and 'index.html' not in page_addr:
        return True
    return False

def save_content(page_addr, content):
    # 获取保存文件路径
    file_path = page_addr.split('://')[1]
    # 获取保存文件的文件夹路径
    folder_path = '/'.join(file_path.split('/')[:-1])
    # 判断当前文件夹是否存在
    if not os.path.exists(folder_path):
        os.makedirs(folder_path)
    # 判断当前文件是否存在
    if not os.path.exists(file_path):
        dbs_tools.save_file_w(file_path, content)

if __name__ == "__main__":
    print(url_base)
    download_html(url_base)
    print(used_urls)
    print(len(used_urls))