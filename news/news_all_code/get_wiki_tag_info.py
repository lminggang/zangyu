from DBSReptileTools import DBSReptileTools
from lxml import etree
import time
import glob
import os


# TODO 基础变量定义
read_folder_path = 'tb.xzxw.com'
save_folder_path = 'tb.xzxw.com_result'
url_base = 'https://baike.yongzin.com'

# TODO 计数变量定义
step = 0
num_one = step
num_two = 0

dbs_tools = DBSReptileTools(timeout=2)
# TODO 获取search页面所有文件路径
file_path_list = dbs_tools.search_file_list(read_folder_path, search_file_rule='*.webloc')
# TODO 循环所有的路径并拿到所有页面中的URL
for file_path in file_path_list:
    num_one += 1
    page_addr = dbs_tools.get_webloc_page_addr(read_file_path=file_path)
    print(num_one, page_addr)
    task_id = dbs_tools.get_task_id(page_addr=page_addr)
    html_path = '{}/{}.html'.format(save_folder_path, task_id)
    webloc_path = '{}/{}.webloc'.format(save_folder_path, task_id)
    html = dbs_tools.get_html(page_addr=page_addr)
    if html == '':
            continue
    dbs_tools.save_file_w(html_path, html)
    dbs_tools.save_file_w(webloc_path, dbs_tools.get_webloc_content(page_addr=page_addr))
