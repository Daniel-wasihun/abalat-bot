import json
import os
import re

def update_php_file(filepath, json_path, is_new=False, class_name="Oromiffa", lang_code="om", lang_name="Afaan Oromoo", icon="et.png"):
    with open(json_path, 'r', encoding='utf-8') as f:
        new_trans = json.load(f)

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
            # escape single quotes
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
        
        # We need to insert the new keys into the return array
        # Find the return [ statement
        match = re.search(r'(return\s*\[\s*)(.*?)(        \];)', content, re.DOTALL)
        if match:
            existing_array = match.group(2)
            # Find existing keys to avoid duplicates
            existing_keys = set(re.findall(r"'([^']+)'\s*=>", existing_array))
            
            append_str = ""
            for k, v in new_trans.items():
                if k not in existing_keys:
                    v_esc = str(v).replace("'", "\\'")
                    append_str += f"            '{k}' => '{v_esc}',\n"
            
            new_content = content[:match.start(2)] + existing_array + append_str + content[match.end(2):]
            with open(filepath, 'w', encoding='utf-8') as f:
                f.write(new_content)

update_php_file('app/Translation/Front/English.php', 'en_dump.json')
update_php_file('app/Translation/Front/Amharic.php', 'am_dump.json')
update_php_file('app/Translation/Front/Oromiffa.php', 'om_dump.json', True, 'Oromiffa', 'om', 'Afaan Oromoo', 'et.png')
update_php_file('app/Translation/Back/Oromiffa.php', 'om_dump.json', True, 'Oromiffa', 'om', 'Afaan Oromoo', 'et.png')
