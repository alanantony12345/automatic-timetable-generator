
import difflib
import sys

try:
    with open('admin_dashboard.php', 'r', encoding='utf-8') as f1, open('admin_dashboard_repaired.php', 'r', encoding='utf-8') as f2:
        lines1 = f1.readlines()
        lines2 = f2.readlines()
        
    diff = difflib.unified_diff(lines1, lines2, fromfile='admin_dashboard.php', tofile='admin_dashboard_repaired.php', n=3)
    
    diff_content = ''.join(diff)
    
    if not diff_content:
        print("Files are identical.")
    else:
        print(diff_content[:5000]) # Print first 5000 chars of diff
        if len(diff_content) > 5000:
            print("\n... (Diff truncated)")

except Exception as e:
    print(f"Error: {e}")
