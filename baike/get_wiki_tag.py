from DBSReptileTools import DBSReptileTools
from lxml import etree
import os
import glob

url_base = 'https://baike.yongzin.com/'
save_path_base = 'wiki_tag'
# TODO: 获取result中所有.html文件路径
dbs_tools = DBSReptileTools()
file_path_list = dbs_tools.search_file_list('result', '*.html')
# TODO: 遍历所有file_path_list将tag获取到并保存到文件夹
step = 0
num = 0
num_one = step
for file_path in file_path_list[step:]:
    num_one += 1
    html = dbs_tools.read_file_content(file_path)
    dom = etree.HTML(html)
    dom_a_href_list = dom.xpath('//a[@class="btn btn-white mr10 mt10"]/@href')
    for a_href in dom_a_href_list:
        num += 1
        print(num_one, num, len(glob.glob('wiki_tag/*.webloc')), file_path)
        page_addr = a_href.replace('../', url_base)
        task_id = dbs_tools.get_task_id(page_addr=page_addr)
        file_name = '{}.webloc'.format(task_id)
        save_path = os.path.join(save_path_base, file_name)
        if os.path.exists(save_path):
            continue
        webloc_xml = dbs_tools.get_webloc_content(page_addr=page_addr)
        dbs_tools.save_file_w(save_path, webloc_xml)
