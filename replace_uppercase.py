import os
import re

directory = 'resources'
pattern = re.compile(r'\buppercase\b')
count = 0

for root, dirs, files in os.walk(directory):
    for file in files:
        if file.endswith('.vue') or file.endswith('.blade.php'):
            filepath = os.path.join(root, file)
            with open(filepath, 'r', encoding='utf-8') as f:
                content = f.read()
            
            new_content = pattern.sub('capitalize', content)
            
            if new_content != content:
                with open(filepath, 'w', encoding='utf-8') as f:
                    f.write(new_content)
                print(f"Updated {filepath}")
                count += 1

print(f"Total files updated: {count}")
