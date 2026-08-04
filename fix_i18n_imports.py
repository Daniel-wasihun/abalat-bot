import glob
import re

vue_files = glob.glob('resources/js/views/bot/*.vue')

for filepath in vue_files:
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()

    # Match import { useI18n } from '../i18n.js';
    content = re.sub(r"import\s+\{.*i18n.*\}\s*from\s*['\"].*i18n(\.js)?['\"];?", "", content)

    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(content)
