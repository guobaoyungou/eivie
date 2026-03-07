#!/usr/bin/env python3
"""Fix brace count issues in pages.json after titleNView migration"""
import re

filepath = '/home/www/ai.eivie.cn/uniapp/pages.json'

with open(filepath, 'r', encoding='utf-8') as f:
    content = f.read()

lines = content.split('\n')
new_lines = []
fixed = 0

for i, line in enumerate(lines):
    # Only check lines that have app-plus titleNView (the ones we modified)
    if '"app-plus":{"titleNView"' not in line:
        new_lines.append(line)
        continue
    
    # Count braces to verify balance
    stripped = line.strip().rstrip(',')
    
    opens = stripped.count('{') + stripped.count('[')
    closes = stripped.count('}') + stripped.count(']')
    
    if opens == closes:
        new_lines.append(line)
        continue
    
    extra = closes - opens
    if extra > 0:
        # Remove extra closing braces from the end
        # Find the trailing }}} part
        trailing_comma = line.rstrip().endswith(',')
        work = line.rstrip().rstrip(',')
        
        # Remove 'extra' closing braces from the end
        for _ in range(extra):
            if work.endswith('}'):
                work = work[:-1]
            else:
                break
        
        if trailing_comma:
            work += ','
        
        # Restore original trailing whitespace
        new_lines.append(work)
        fixed += 1
        print(f"Line {i+1}: Removed {extra} extra closing brace(s)")
    else:
        # Missing closing braces (shouldn't happen but just in case)
        new_lines.append(line)
        print(f"Line {i+1}: WARNING - missing {-extra} closing brace(s)")

result = '\n'.join(new_lines)

with open(filepath, 'w', encoding='utf-8') as f:
    f.write(result)

print(f"\nFixed {fixed} lines")
