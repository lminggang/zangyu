import requests
import math
import os
import glob
import lxml


class DBSReptileTools:
    '''
        DBS 爬虫工具包
    '''
    def __init__(self, page_addr='', timeout=0):
        '''
            page_addr: 初始化抓取页面地址
            timeout:   请求等待时间
        '''
        self.page_addr = page_addr
        self.timeout = timeout
    
    def __get_page_addr(self, page_addr):
        '''
            内部方法，用于处理url
        '''
        if self.page_addr == '' and page_addr == '':
            raise Exception('url is empty')
        
        if page_addr != '':
            self.page_addr == page_addr
    
    def get_html(self, page_addr=''):
        '''
            获取html
            params:
                page_addr str [需要打开的网页地址，默认可为空]
        '''
        self.__get_page_addr(page_addr)

        response = requests.get(self.page_addr, self.timeout)
        page_encode = requests.utils.get_encodings_from_content(response.text)
        if page_encode:
            response.encoding = page_encode[0]
        
        return response.text
    
    @staticmethod
    def get_webloc_content(self, page_addr=''):
        '''
            获取webloc内容方法
            params:
                page_addr str [需要打开的网页地址，默认可为空]
        '''
        self.__get_page_addr(page_addr)
        webloc_xml = '''
            <?xml version="1.0" encoding="UTF-8"?>
            <!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
            <plist version="1.0">
                <dict>
                    <key>URL</key>
                    <string>{page_addr}</string>
                </dict>
            </plist>
        '''.format(page_addr=self.page_addr)
        return webloc_xml
    
    def save_file_w(self, save_path, save_content, mode='w'):
        '''
            保存文件方法
            默认写入模式为覆盖
            如果该文件已存在则不做任何操作
            params:
                save_path str [保存文件的路径]
                save_content str [保存到文件的内容]
                mode str [打开文件模式]
        '''
        if os.path.exists(save_path):
            return
        with open(save_path, mode) as file:
            file.write(save_content)
    
    def save_file_a(self, save_path, save_content, mode='a+'):
        '''
            保存文件方法
            默认写入模式为追加
            如果该文件已存在则不做任何操作
            params:
                save_path str [保存文件的路径]
                save_content str [保存到文件的内容]
                mode str [打开文件模式]
        '''
        if os.path.exists(save_path):
            return
        with open(save_path, mode) as file:
            file.write(save_content)
    
    def search_file_list(self, search_path, search_file_rule='*.html'):
        '''
            搜索文件列表
            params:
                search_path str [搜索的文件夹路径]
                search_file_rule str [搜索文件的匹配规则，默认为 *.html。该参数可以为任意值与linux命令一致]
        '''
        path = os.path.join(search_path, search_file_rule)
        file_list = glob.glob(path)
        return file_list
    
    def read_file_content(self, read_file_path):
        '''
            读取文件内容
            params:
                read_file_path str [要读取的文件路径]
        '''
        content = ''
        with open(read_file_path, 'r') as file:
            content = file.read()
        return content

    
    def get_webloc_page_addr(self, read_file_path= '', webloc_xml=''):
        '''
            获取webloc内容中的page_addr
            params:
                read_file_path str [webloc路径]
                webloc_xml str [webloc内容]
        '''
        if webloc_xml == '':
            webloc_xml = self.read_file_content(read_file_path)
        dom = lxml.etree.HTML(webloc_xml)
        page_addr = dom.xpath('//string/text()')
        return page_addr



if __name__ == '__main__':
    url = 'https://baike.yongzin.com/search/search.do?word=%E0%BD%96%E0%BD%99%E0%BD%93%E0%BC%8B%E0%BD%94%E0%BD%BC%E0%BD%A0%E0%BD%B2%E0%BC%8B%E0%BD%A3%E0%BD%BC%E0%BC%8B%E0%BD%A2%E0%BE%92%E0%BE%B1%E0%BD%B4%E0%BD%A6%E0%BC%8D'
    dbs_reptile = DBSReptileTools(url)
    html = dbs_reptile.get_html()
    print(html)