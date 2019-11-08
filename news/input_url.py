import requests
import hashlib
import glob
import time
import sys

class ReptileBaike:
    def __init__(self):
        self.save_path = 'result'


    def get_html(self, page_addr):
        # time.sleep(1)
        result = requests.get(page_addr)
        html = result.text
        if len(html) < 5000:
            with open('error.log', 'a+') as file:
                content = '{}\n'.format(page_addr)
                file.write(content)
            raise Exception("输入验证码...")
        return html

    def get_webloc_xml(self, page_addr=''):
        webloc_xml = '''
            <?xml version="1.0" encoding="UTF-8"?>
            <!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
            <plist version="1.0">
                <dict>
                    <key>URL</key>
                    <string>{page_addr}</string>
                </dict>
            </plist>
        '''.format(page_addr=page_addr)
        return webloc_xml

    def create_task_id(self, page_addr=''):
        m = hashlib.md5()
        b = page_addr.encode(encoding='utf-8')
        m.update(b)
        task_id = m.hexdigest().upper()
        return task_id

    def save(self, save_path='', file_name='', content=''):
        with open('{}/{}'.format(save_path, file_name), 'w') as file:
            file.write(content)

    def url_run(self, page_addr, save_path='result'):
        page_addr = page_addr.strip()
        task_id = self.create_task_id(page_addr)
        if glob.glob('{}/{}*'.format(save_path, task_id)):
            return
        print('{}'.format(task_id))
        html_file_name = '{}{}'.format(task_id, '.html')
        webloc_file_name = '{}{}'.format(task_id, '.webloc')
        html = self.get_html(page_addr)
        webloc = self.get_webloc_xml(page_addr)
        self.save(save_path, html_file_name, html)
        self.save(save_path, webloc_file_name, webloc)

    def get_page_addr(self):
        try:
            page_addr = sys.argv[1]
        except Exception as e:
            page_addr = ''
        return page_addr
    
    def get_save_path(self):
        try:
            page_addr = sys.argv[2]
        except Exception as e:
            page_addr = 'result'
        return page_addr


if __name__ == "__main__":
    baike = ReptileBaike()
    page_addr = baike.get_page_addr()
    save_path = baike.get_save_path()
    baike.url_run(page_addr, save_path)

    # req = requests.get('https://baike.yongzin.com/word/viewWord.do?id=40288c95559a48f70155ba0c11f758a6')
    # print(req.text)
