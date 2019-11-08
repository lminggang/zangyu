import requests
from bs4 import BeautifulSoup
import re
import time
from urllib.parse import urlparse
import os
import hashlib
import subprocess


class GetUserPage:
    def start(self):
        for root, dirs, files in os.walk("./result", topdown=False):
            for file_name in files:
                if file_name.endswith(".html"):
                    tmplate_path = os.path.join(root, file_name)
                    with open(tmplate_path, 'r') as f:
                        self.get_addr_by_page(page_content=f.read())
                
        return True
    
    def get_addr_by_page(self, page_content=''):
        document = BeautifulSoup(page_content, "html.parser")
        res = []
        article_list = document.find_all('a', attrs={"href": True})
        for article in article_list:
    
            if article['href'] and article['href'].startswith('/baikeUserController/personnel'):
                req_addr = "https://baike.yongzin.com{}".format(article['href'])
                task_id = self.create_task_id(page_addr=req_addr)

                task_file = os.path.join('./user_page', "{}.html".format(task_id))

                if not os.path.exists(task_file):
                    time.sleep(1)
                    p = subprocess.Popen(['python ./input_url.py "{}" user_page'.format(req_addr)], shell=True)
                    print(req_addr)
        return res
    
    def create_task_id(self, page_addr=''):
        m = hashlib.md5()
        b = page_addr.encode(encoding='utf-8')
        m.update(b)
        task_id = m.hexdigest().upper()
        return task_id



GUP = GetUserPage()
GUP.start()


