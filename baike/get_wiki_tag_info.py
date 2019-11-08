from DBSReptileTools import DBSReptileTools
from lxml import etree
import time
import glob
import os


# TODO 基础变量定义
read_folder_path = 'search_tag'
save_folder_path = 'result'
url_base = 'https://baike.yongzin.com'

# TODO 计数变量定义
step = 0
num_one = step
num_two = 0

dbs_tools = DBSReptileTools()
# TODO 获取search页面所有文件路径
file_path_list = dbs_tools.search_file_list(read_folder_path, search_file_rule='*.html')
# TODO 循环所有的路径并拿到所有页面中的URL
for file_path in file_path_list:
    num_one += 1
    # TODO 获取已下载好的html内容
    html_search = dbs_tools.read_file_content(file_path)
    # TODO 分析并获取url
    dom = etree.HTML(html_search)
    page_addr_list = dom.xpath('//h4[@class="c-title"]/a/@href')
    # TODO 循环下载wiki info 页面并保存到result中
    for page_addr in page_addr_list[step:]:
        # TODO 要抓取的url，要保存的文件路径。整理
        page_addr = '{}{}'.format(url_base, page_addr)
        # task_id = dbs_tools.get_task_id(page_addr=page_addr)
        task_id = page_addr.split('id=')[1]
        html_path = '{}/{}.html'.format(save_folder_path, task_id)
        webloc_path = '{}/{}.webloc'.format(save_folder_path, task_id)
        # TODO 根据html保存路径判断当前文件是否已保存，如果保存则退出本次循环继续执行下次循环
        num_two += 1
        print(num_one, num_two, len(glob.glob('result2/*.html')), page_addr)
        if os.path.exists(html_path):
            continue
        time.sleep(1)
        # TODO 获取html并保存文件
        html_info = dbs_tools.get_html(page_addr=page_addr)
        if html_info == '':
            continue
        dbs_tools.save_file_w(html_path, html_info)
        dbs_tools.save_file_w(webloc_path, dbs_tools.get_webloc_content(page_addr=page_addr))

