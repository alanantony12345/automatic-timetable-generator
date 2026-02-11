<?php
$file = 'c:/xampp/htdocs/autotimetable/admin_dashboard.php';
$content = file_get_contents($file);

// 1. Fix broken open tags like "< div" -> "<div"
// We look for "< " followed by a letter, often at start of line or after backtick
$content = preg_replace('/< ([a-z]+)/i', '<$1', $content);

// 2. Fix broken close tags like "</div >" -> "</div>" and "</td >"
$content = preg_replace('/<\/([a-z]+)\s*>/i', '</$1>', $content);

// 3. Fix broken "animate - fade -in"
$content = str_replace('animate - fade -in', 'animate-fade-in', $content);

// 4. Fix broken "animate - fade -out" (guessing)
$content = str_replace('animate - fade -out', 'animate-fade-out', $content);

// 5. Fix broken class attributes spanning lines? 
// e.g. class="... \n ..." -> class="... ..."
// This is risky globally. Let's start with specific known issues.

// 6. Fix tbody.innerHTML = '<tr>... broken lines
// "tbody.innerHTML = '<tr>" followed by newline and spaces
$content = preg_replace("/tbody\.innerHTML = '<tr>\s+/", "tbody.innerHTML = '<tr>", $content);

// 7. Fix broken closing of JS strings with HTML
// "No ... found.</td>\s+</tr> ';" -> "No ... found.</td></tr>';"
$content = preg_replace("/found\.<\/td>\s+<\/tr>\s*';/s", "found.</td></tr>';", $content);

// 8. Fix specific breakage in "deleteDept" / "deleteSection"
// < td colspan = "3" -> <td colspan="3"
$content = preg_replace('/< td colspan = "(\d+)"/', '<td colspan="$1"', $content);

// 9. Fix spaced attributes like "type === 'error' ? 'text-red-500' : 'text-blue-500'}" ></i >
// repaired by rule 2 (</i > -> </i>) but what about " >" at end of open tag?
$content = preg_replace('/"\s+>/', '">', $content); // e.g. class="..." >

// 10. Fix variable interpolation broken spaces "${ a.subject_code" -> "${a.subject_code"
$content = preg_replace('/\${\s+/', '${', $content); // start
$content = preg_replace('/\s+}/', '}', $content);     // end

// 11. Fix "toast ${ type } " -> "toast ${type}"
// Covered by 10 partially? "${ type } " -> "${type} "
// Let's rely on 10.

// 12. Fix "class="... overflow-hidden \n animate..."
// This is inside backticks or single quotes.
// We can try to join lines inside class="..."? No, too hard.
// Just target the specific broken lines.
$content = str_replace("group overflow-hidden\n                        animate-fade-in", "group overflow-hidden animate-fade-in", $content);
$content = str_replace("gap-4\n                        hover:shadow-md", "gap-4 hover:shadow-md", $content);


file_put_contents($file, $content);
echo "Fixed syntax errors in admin_dashboard.php\n";
?>