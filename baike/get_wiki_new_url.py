from DBSReptileTools import DBSReptileTools
from lxml import etree
import os

read_path_base = 'search_tag'
result_path_base = 'result'

save_path_base = 'new_url.txt'

url_base = 'https://baike.yongzin.com'


dbs_tools = DBSReptileTools()

file_path_list = dbs_tools.search_file_list(read_path_base, '*.html')
num_one = 0
num_two = 0
for file_path in file_path_list:
    num_one += 1
    html = dbs_tools.read_file_content(file_path)
    dom = etree.HTML(html)
    hrefs = dom.xpath('//h4[@class="c-title"]/a/@href')
    for href in hrefs:
        num_two += 1
        page_addr = '{}{}'.format(url_base, href)
        task_id = dbs_tools.get_task_id(page_addr)
        file_name = '{}.html'.format(task_id)
        check_path = os.path.join(result_path_base, file_name)
        # print(check_path)
        # break
        if os.path.exists(check_path):
            print(num_one, num_two, page_addr, '已下载')
            continue
        else:
            file_name = '{}.html'.format(page_addr.split('id=')[1])
            check_path = os.path.join(result_path_base, file_name)
            if os.path.exists(check_path):
                print(num_one, num_two, page_addr, '已下载')
                continue

        print(num_one, num_two, page_addr, check_path, '未下载')
        with open(save_path_base, 'a+') as file:
            content = '{}\n'.format(page_addr)
            file.write(content)




