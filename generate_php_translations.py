import json
import re

with open('i18n_messages.json', 'r', encoding='utf-8') as f:
    messages = json.load(f)

def update_php(filepath, lang_code, is_new=False, class_name="Oromiffa", lang_name="Afaan Oromoo", icon="et.png"):
    new_trans = messages.get(lang_code, {})
    if not new_trans:
        return
        
    if is_new:
        php_content = f"""<?php

namespace App\Translation\Front;

use App\Common\Lang\Lang;

class {class_name} extends Lang {{
    protected static string $key = '{lang_code}';
    protected static string $name = '{lang_name}';
    protected static string $icon = '{icon}';

    public static function translations(): array {{
        return [
"""
        for k, v in new_trans.items():
            v_esc = str(v).replace("'", "\\'")
            php_content += f"            '{k}' => '{v_esc}',\n"
        php_content += """        ];
    }
}
"""
        with open(filepath, 'w', encoding='utf-8') as f:
            f.write(php_content)
    else:
        with open(filepath, 'r', encoding='utf-8') as f:
            content = f.read()
        
        match = re.search(r'(return\s*\[\s*)(.*?)(        \];)', content, re.DOTALL)
        if match:
            existing_array = match.group(2)
            existing_keys = set(re.findall(r"'([^']+)'\s*=>", existing_array))
            
            append_str = ""
            for k, v in new_trans.items():
                if k not in existing_keys:
                    v_esc = str(v).replace("'", "\\'")
                    append_str += f"            '{k}' => '{v_esc}',\n"
            
            new_content = content[:match.start(2)] + existing_array + append_str + content[match.end(2):]
            with open(filepath, 'w', encoding='utf-8') as f:
                f.write(new_content)

update_php('app/Translation/Front/English.php', 'en')
update_php('app/Translation/Front/Amharic.php', 'am')
update_php('app/Translation/Front/Oromiffa.php', 'om', True, 'Oromiffa', 'Afaan Oromoo', 'et.png')

# Fix the Back namespace manually
php_back_content = f"""<?php

namespace App\Translation\Back;

use App\Common\Lang\Lang;

class Oromiffa extends Lang {{
    protected static string $key = 'om';
    protected static string $name = 'Afaan Oromoo';
    protected static string $icon = 'et.png';

    public static function translations(): array {{
        return [
"""
for k, v in messages.get('om', {}).items():
    v_esc = str(v).replace("'", "\\'")
    php_back_content += f"            '{k}' => '{v_esc}',\n"
php_back_content += """        ];
    }
}
"""
with open('app/Translation/Back/Oromiffa.php', 'w', encoding='utf-8') as f:
    f.write(php_back_content)
