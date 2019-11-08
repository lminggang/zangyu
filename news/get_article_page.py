import requests
from bs4 import BeautifulSoup
import re
import time
import hashlib
import os
from urllib.parse import urlparse




class GetArticlePage:
    
    def start(self):
        article_list = self.init_article_list()
        
        article_len = len(article_list)
        for i, article in enumerate(article_list):
            print("{}/{}".format(i, article_len), article)
            self.save_article_page(page_addr=article)
        return True
    
    @staticmethod
    def init_article_list():
        home_article = './home_article.txt'
        cate_article = './article_list.txt'
        
        res = {}
        
        with open(home_article, 'r') as f:
            article_list = f.readlines()
            
            for article in article_list:
                res[article.strip()] = 1

        with open(cate_article, 'r') as f:
            article_list = f.readlines()
    
            for article in article_list:
                res[article.strip()] = 1
        
        return res.keys()
    
    @staticmethod
    def get_page_content(req_addr=''):
        ua = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/71.0.3578.98 Safari/537.36'
        response = requests.get(url=req_addr, headers={'User-Agent': ua})
        
        page_encode = requests.utils.get_encodings_from_content(response.text)
        if page_encode:
            response.encoding = page_encode[0]
        
        return response.text
    
    def save_article_page(self, page_addr=''):
        task_id = self.create_task_id(page_addr=page_addr)
        
        self.create_page_file(task_id=task_id, page_addr=page_addr)
        
        self.create_content_file(task_id=task_id, page_addr=page_addr)
        return task_id
    
    def create_content_file(self, task_id='', page_addr=''):
        host = urlparse(page_addr).netloc
    
        article_dir = os.path.join("./article", host)
    
        if not os.path.exists(article_dir):
            os.makedirs(article_dir)
    
    
        content_file = os.path.join(article_dir, "{}.html").format(task_id)
        
        if os.path.exists(content_file):
            return True
        
        page_content = self.get_page_content(req_addr=page_addr)
        
        with open(content_file, 'w', encoding="utf-8") as f:
            f.write(page_content)
        
        return True
    
    @staticmethod
    def create_page_file(task_id='', page_addr=''):
        host = urlparse(page_addr).netloc
        
        article_dir = os.path.join("./article", host)
        
        if not os.path.exists(article_dir):
            os.makedirs(article_dir)
        
        page_file = os.path.join(article_dir, "{}.webloc").format(task_id)
        if os.path.exists(page_file):
            return True
        
        content = '''
            <?xml version="1.0" encoding="UTF-8"?>
            <!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
            <plist version="1.0">
                <dict>
                    <key>URL</key>
                    <string>{page_addr}</string>
                </dict>
            </plist>
        '''.format(page_addr=page_addr)
        
        with open(page_file, 'w', encoding="utf-8") as f:
            f.write(content)
        
        return True
        
    def create_task_id(self, page_addr=''):
        m = hashlib.md5()
        b = page_addr.encode(encoding='utf-8')
        m.update(b)
        task_id = m.hexdigest().upper()
        return task_id


GAP = GetArticlePage()

GAP.start()


