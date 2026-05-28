import sys

js_path = r"c:\Users\anasa\Desktop\النوع\public\celebration.js"

with open(js_path, 'r', encoding='utf-8') as f:
    content = f.read()

# 1. Replace letterRendering: true with letterRendering: false
old_flag = "letterRendering: true"
new_flag = "letterRendering: false"

if old_flag in content:
    content = content.replace(old_flag, new_flag)
    print("Replaced letterRendering: true with false.")
else:
    print("Warning: letterRendering: true not found in code, checking variants...")
    content = content.replace("letterRendering:true", "letterRendering:false")

with open(js_path, 'w', encoding='utf-8') as f:
    f.write(content)

print("Arabic text shaping fix completed in celebration.js!")
