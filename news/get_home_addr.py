import requests
from bs4 import BeautifulSoup
import re
import time


class GetHomeAddr:
    
    def start(self):
        page_addr = 'https://www.yongzin.com/news/'
        page_content = self.get_page_content(req_addr=page_addr)
        document = BeautifulSoup(page_content, "html.parser")

        article_icon = document.find_all('i', class_="brown_low")
        
        article_addr = []
        for icon in article_icon:
            try:
                article_addr.append(icon.next_sibling['href'])
            except:
                pass
        
        with open('home_article.txt', 'a+') as f:
            f.write("\n".join(article_addr))
        return True

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






GHA = GetHomeAddr()

GHA.start()


