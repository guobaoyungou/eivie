#!/usr/bin/env python3
"""Fix titleNView in pages.json - move into app-plus wrapper"""
import re
import json

filepath = '/home/www/ai.eivie.cn/uniapp/pages.json'

with open(filepath, 'r', encoding='utf-8') as f:
    content = f.read()

lines = content.split('\n')
new_lines = []
changes = 0

for i, line in enumerate(lines):
    # Skip if no titleNView, or already inside app-plus/h5
    if '"titleNView"' not in line or '"app-plus"' in line or '"h5"' in line:
        new_lines.append(line)
        continue
    
    # Special case: line 1142 pattern where titleNView is OUTSIDE style object
    # {"path":"...","style":{...},"titleNView":{...}}
    # Check if titleNView appears after style closing
    match_outside = re.search(r'("style"\s*:\s*\{[^}]*\})\s*,\s*"titleNView"\s*:', line)
    if match_outside:
        # Find the titleNView value and move it inside style's app-plus
        # This is the pattern: ..."style": {...},"titleNView":{...}}
        # We need: ..."style": {...,"app-plus":{"titleNView":{...}}}}
        
        # Extract titleNView portion from after style
        style_end_pos = line.find('},"titleNView"')
        if style_end_pos == -1:
            style_end_pos = line.find('}, "titleNView"')
        
        if style_end_pos >= 0:
            # Find the titleNView key-value
            tnv_start = line.index('"titleNView"', style_end_pos)
            # titleNView:VALUE where VALUE ends before the page-closing }}
            # We need to extract the value carefully
            colon_pos = line.index(':', tnv_start)
            # Extract from colon+1 to the last }} of the page entry
            rest = line[colon_pos+1:].rstrip().rstrip(',')
            # rest should be like: {"searchInput":...}}  (the last } closes the page entry)
            # We need to remove the last } (page entry close)
            if rest.endswith('}'):
                tnv_value = rest[:-1].strip()  # Remove page-closing }
                # Now reconstruct: put titleNView inside style as app-plus
                before_style_close = line[:style_end_pos]
                after_page = line[line.rindex('}'):]  # Get trailing } and comma if any
                
                new_line = before_style_close + ',"app-plus":{"titleNView":' + tnv_value + '}}' + after_page
                new_lines.append(new_line)
                changes += 1
                print(f"Line {i+1}: Fixed outside-style titleNView")
                continue
    
    # Standard case: titleNView is inside style object
    # Pattern: ,"titleNView":VALUE}} where VALUE can be false or object
    
    # Strategy: find "titleNView" and its value, wrap in "app-plus"
    # Replace ,"titleNView": with ,"app-plus":{"titleNView":
    # Then add closing } for app-plus before the style closing }
    
    # Find the titleNView key position
    tnv_pos = line.index('"titleNView"')
    
    # Find the comma before titleNView (it should be ,"titleNView")
    comma_before = line.rfind(',', 0, tnv_pos)
    if comma_before == -1:
        # titleNView might be the first property in style
        new_lines.append(line)
        continue
    
    # Extract the part before the comma
    before = line[:comma_before]
    # Extract from comma onwards
    after_comma = line[comma_before:]
    
    # The after_comma looks like: ,"titleNView":VALUE}}  or  ,"titleNView":VALUE}},
    # We need to find where the titleNView value ends
    
    # Count the remaining braces to figure out structure
    # The last }} close style and page entry
    # After wrapping, we need }}} (app-plus close + style close + page entry close)
    
    # Simple approach: replace ,"titleNView": with ,"app-plus":{"titleNView":
    # and add one more } before the last }}
    
    # Find the end of the line (before trailing whitespace/comma)
    stripped = after_comma.rstrip()
    trailing = after_comma[len(stripped):]
    
    # Check if it ends with }}, }}), etc.
    # Replace ,"titleNView": with ,"app-plus":{"titleNView":
    new_after = stripped.replace(',"titleNView":', ',"app-plus":{"titleNView":', 1)
    
    # Now we need to add closing } for app-plus
    # Find the position where style closes - it should be the second-to-last }
    # The line ends with either }} or }}) or }},
    
    # Count how the line ends
    end_match = re.search(r'(\}+)\s*([,\s]*)$', new_after)
    if end_match:
        closing_braces = end_match.group(1)
        trailing_chars = end_match.group(2)
        # Add one more } inside the closing braces (for app-plus close)
        # Insert } before the last 2 closing braces
        insert_pos = new_after.rfind(closing_braces)
        new_after = new_after[:insert_pos] + closing_braces[:-2] + '}' + closing_braces[-2:] + trailing_chars
        # Wait, this is getting complex. Let me use a simpler approach.
        pass
    
    # Simpler approach: just add } before the last }}
    # Find last occurrence of }}
    last_double_brace = new_after.rfind('}}')
    if last_double_brace >= 0:
        new_after = new_after[:last_double_brace] + '}}}' + new_after[last_double_brace+2:]
    
    new_line = before + new_after + trailing
    new_lines.append(new_line)
    changes += 1
    print(f"Line {i+1}: Fixed titleNView -> app-plus wrapper")

result = '\n'.join(new_lines)

with open(filepath, 'w', encoding='utf-8') as f:
    f.write(result)

print(f"\nTotal changes: {changes}")
