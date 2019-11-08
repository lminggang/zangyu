import requests
from bs4 import BeautifulSoup
import re
import time


class GetCategoryAddr:
    
    def start(self):
        category_addr_list = self.get_category_addr_list()
        
        article_addr_list = []
        for addr in category_addr_list:
            article_addr_list += self.get_article_addr(category_addr=addr)
        
        with open('article_list.txt', 'a+') as f:
            f.write("\n".join(article_addr_list))
        
        return True

    @staticmethod
    def get_category_addr_list():
        with open('./category_addr.txt', 'r') as f:
            cat_addr = f.read().split("\n")
            
            return cat_addr
    
    def get_article_addr(self, category_addr=''):
        
        page_size = 10
        
        res = []
        for page_num in range(0, page_size + 1):
            if page_num < 1:
                req_addr = category_addr
            else:
                req_addr = category_addr + 'index_{}.html'.format(page_num)
            
            print(time.time(), req_addr)
            res += self.get_aritcle_addr_by_page(page_addr=req_addr)

        return res

    @staticmethod
    def get_page_content(req_addr=''):
        response = requests.get(url=req_addr)
        return response.text
    
    
    def get_aritcle_addr_by_page(self, page_addr=''):
        page_content = self.get_page_content(req_addr=page_addr)
        document = BeautifulSoup(page_content, "html.parser")
        res = []
        article_list = document.find_all('li', class_="feed-item")
        for article in article_list:
            res.append(article.a['href'])
            
        return res






GTA = GetCategoryAddr()

GTA.start()


