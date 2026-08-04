import os
import glob
import re

vue_files = glob.glob('resources/js/views/bot/*.vue')

for filepath in vue_files:
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()

    # 1. Update relative imports to absolute
    content = content.replace("from '../../components", "from '@/components")
    content = content.replace("from '../../stores", "from '@/stores")
    content = content.replace("from '../components", "from '@/components")
    content = content.replace("from '../stores", "from '@/stores")
    
    # 2. Remove i18n imports
    content = re.sub(r"import\s+\{\s*useI18n(?:,\s*useEnumI18n)?\s*\}\s*from\s*['\"](?:\.\./)*i18n['\"];?", "", content)
    
    # 3. Import languageStore
    if "useLanguageStore" not in content and "<script setup" in content:
        content = re.sub(r'(<script setup.*?>)', r'\1\nimport { useLanguageStore } from "@/stores/languageStore";', content, 1)
    
    # 4. Replace useI18n usage
    content = re.sub(r'const\s+\{\s*t\s*(?:,\s*currentLocaleInfo)?\s*\}\s*=\s*useI18n\(\);?', 'const languageStore = useLanguageStore();\nconst t = (k, p) => languageStore.translate(k, p);', content)
    
    # Also handle useEnumI18n if used
    content = re.sub(r'const\s+\{\s*tCategory,\s*tPriority,\s*tStatus,\s*tLang,\s*categoryOptions,\s*priorityOptions,\s*statusOptions,\s*languageOptions\s*\}\s*=\s*useEnumI18n\(\);?', '', content)

    # Note: t('...') works both in template and script because we define t in script!
    # Let's make sure `t` is defined.
    if 'const languageStore = useLanguageStore();' not in content and 't(' in content and '<script setup' in content:
        content = re.sub(r'(<script setup.*?>)', r'\1\nimport { useLanguageStore } from "@/stores/languageStore";\nconst languageStore = useLanguageStore();\nconst t = (k, p) => languageStore.translate(k, p);', content, 1)

    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(content)

print("Refactored Vue files successfully!")
