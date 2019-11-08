import requests
import hashlib
import glob
import time

class ReptileBaike:
    def __init__(self):
        self.path = 'word/*/*.txt'
        self.save_path = 'result'
        self.not_save_url = list()


    def get_page_addr_all(self):
        page_addr_path_all = glob.glob(self.path)
        page_addrs = list()
        for page_addr_path in page_addr_path_all:
            with open(page_addr_path, 'r') as file:
                page_addrs.extend(file.readlines())
        return page_addrs

    def get_html(self, page_addr):
        time.sleep(1)
        result = requests.get(page_addr)
        html = result.text
        if len(html) < 10000:
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

    def save(self, file_name, content):
        with open('{}/{}'.format(self.save_path, file_name), 'w') as file:
            file.write(content)

    def run(self):
        page_addrs = self.get_page_addr_all()
        num = 0
        for page_addr in page_addrs:
            page_addr = page_addr.strip()
            task_id = self.create_task_id(page_addr)
            num += 1
            print('{} {} {}'.format(num, page_addr, task_id))
            if glob.glob('{}/{}*'.format(self.save_path, task_id)):
                continue
            html_file_name = '{}{}'.format(task_id, '.html')
            webloc_file_name = '{}{}'.format(task_id, '.webloc')
            html = self.get_html(page_addr)
            webloc = self.get_webloc_xml(page_addr)
            self.save(html_file_name, html)
            self.save(webloc_file_name, webloc)

if __name__ == "__main__":
    baike = ReptileBaike()
    baike.run()
    # req = requests.get('https://baike.yongzin.com/word/viewWord.do?id=40288c95559a48f70155ba0c11f758a6')
    # print(req.text)
