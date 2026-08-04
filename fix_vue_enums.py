import glob
import re

vue_files = glob.glob('resources/js/views/bot/*.vue')

for filepath in vue_files:
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()

    # If it uses categoryOptions or other enums, import useEnumI18n
    if 'categoryOptions' in content or 'priorityOptions' in content or 'statusOptions' in content:
        if 'useEnumI18n' not in content:
            # inject import
            content = re.sub(r'(<script setup.*?>)', r'\1\nimport { useEnumI18n } from "@/bot_enums";', content, 1)
            # inject initialization
            init_str = "const { tCategory, tPriority, tStatus, tLang, categoryOptions, priorityOptions, statusOptions, languageOptions } = useEnumI18n();"
            content = re.sub(r'(const languageStore = useLanguageStore\(\);)', r'\1\n' + init_str, content, 1)

    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(content)
