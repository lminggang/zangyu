import os 
class RewriteTopic:
    
    def start(self):
        with open('./tuijian.txt', 'r') as f:
            for row in f:
                row = row.strip()
                row_arr = row.split('=')
                
                self.create_page_file(word_id=row_arr[1], page_addr=row)
        return True

    @staticmethod
    def create_page_file(word_id='', page_addr=''):
    
        article_dir = os.path.join("./tuijian")
    
        if not os.path.exists(article_dir):
            os.makedirs(article_dir)
    
        page_file = os.path.join(article_dir, "{}.webloc").format(word_id)
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
            print(page_file)
            f.write(content)
    
        return True
    
    
RT = RewriteTopic()

RT.start()