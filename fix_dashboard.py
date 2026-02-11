
import os

file_path = 'c:/xampp/htdocs/autotimetable/admin_dashboard.php'

clean_js = r"""    // --- Reports Logic ---
    function loadWorkloadReport() {
        const container = document.getElementById('workload-container');
        if (!container) return; // Guard clause

        container.innerHTML = '<div class="text-slate-400 text-sm italic">Loading workload data...</div>';

        fetch('actions/fetch_workload.php')
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    renderWorkload(res.data);
                } else {
                    container.innerHTML = `<div class="text-red-500 text-sm font-bold">${res.message}</div>`;
                }
            })
            .catch(err => {
                container.innerHTML = `<div class="text-red-500 text-sm">Error: ${err.message}</div>`;
            });
    }

    function renderWorkload(data) {
        const container = document.getElementById('workload-container');
        if (!data || data.length === 0) {
            container.innerHTML = '<div class="text-slate-400">No data found.</div>';
            return;
        }

        let html = '';
        const maxLoad = 20; // Assumption for bar scaling

        data.forEach(fac => {
            const hours = parseInt(fac.total_hours);
            const percent = Math.min((hours / maxLoad) * 100, 100);

            let colorClass = 'bg-emerald-500';
            if (hours > 18) colorClass = 'bg-red-500';
            else if (hours < 12) colorClass = 'bg-amber-400';

            html += `
                <div class="flex items-center gap-4 p-4 bg-slate-50 rounded-2xl border border-slate-100 animate-fade-in">
                    <div class="w-10 h-10 rounded-full bg-white flex items-center justify-center font-bold text-slate-600 text-xs shadow-sm">
                        ${fac.name.substring(0, 2).toUpperCase()}
                    </div>
                    <div class="flex-1">
                        <div class="flex justify-between mb-1">
                            <h4 class="font-bold text-slate-700 text-sm">${fac.name} <span class="text-slate-400 font-normal text-xs">(${fac.dept_name || 'N/A'})</span></h4>
                            <span class="font-bold text-slate-800 text-sm">${hours} Hrs</span>
                        </div>
                        <div class="h-2 w-full bg-slate-200 rounded-full overflow-hidden">
                            <div class="h-full ${colorClass}" style="width: ${percent}%"></div>
                        </div>
                    </div>
                </div>
            `;
        });
        container.innerHTML = html;
    }

    // Lazy load trigger
    window.addEventListener('hashchange', () => {
        if (window.location.hash === '#reports') loadWorkloadReport();
    });
"""

try:
    with open(file_path, 'r', encoding='utf-8') as f:
        content = f.read()

    start_marker = "// --- Reports Logic ---"
    end_marker = "</script>"

    start_idx = content.rfind(start_marker) # Use rfind to get the last occurrence if any
    
    if start_idx == -1:
        print("Start marker not found")
        exit(1)

    # Find the script close tag AFTER the start marker
    end_idx = content.find(end_marker, start_idx)
    
    if end_idx == -1:
        print("End marker not found")
        exit(1)

    new_content = content[:start_idx] + clean_js + "\n    " + content[end_idx:]

    with open(file_path, 'w', encoding='utf-8') as f:
        f.write(new_content)
    
    print("Successfully repaired admin_dashboard.php")

except Exception as e:
    print(f"Error: {e}")
