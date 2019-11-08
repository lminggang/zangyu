from DBSReptileTools import DBSReptileTools
from lxml import etree
import math
import time
import glob
import os

webloc_path = 'wiki_tag'
save_path_base = 'search_tag'


dbs_tools = DBSReptileTools()

step = 125
num_one = step
num_two = 0

# TODO 获取所有webloc文件路径
webloc_file_list = dbs_tools.search_file_list(webloc_path, search_file_rule='*.webloc')
# TODO 遍历所有文件获取文件中的 url 并将搜索页面保存起来 
for webloc_file in webloc_file_list[step:]:
    num_one += 1
    # 获取基础 url
    url_base = dbs_tools.get_webloc_page_addr(read_file_path=webloc_file)
    # 获取当前搜索标签的总页数
    html = dbs_tools.get_html(page_addr=url_base)
    dom = etree.HTML(html)
    total_size_str = dom.xpath('/html/body/section/div/div/div/div[1]/em/text()')
    if not total_size_str:
        raise Exception('search page not total_size')
    total_size = int(total_size_str[0].replace(',', ''))
    page_count = math.ceil(total_size / 10)
    # 遍历所有页面拿到数据并保存
    for i in range(1, page_count + 1):
        num_two += 1
        # 获得完整的 url
        page_addr = '{}&pageNo={}'.format(url_base, i)
        print(num_one, num_two, len(glob.glob('search_tag/*.html')), page_addr)
        # 获取task id
        task_id = dbs_tools.get_task_id(page_addr=page_addr)
        # 设置html保存路径
        html_path = '{}/{}.html'.format(save_path_base, task_id)
        # 设置webloc保存路径
        webloc_path = '{}/{}.webloc'.format(save_path_base, task_id)
        if os.path.exists(html_path):
            continue
        time.sleep(1)
        # 获取页面内容
        html = dbs_tools.get_html(page_addr=page_addr)
        if html == '':
            continue
        # save
        dbs_tools.save_file_w(html_path, html)
        dbs_tools.save_file_w(webloc_path, dbs_tools.get_webloc_content(page_addr=page_addr))

