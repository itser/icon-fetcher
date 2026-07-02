<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>App Icon Fetcher</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 720px; margin: 2rem auto; padding: 0 1rem; }
        label { display: block; margin-bottom: 0.25rem; font-weight: 600; }
        input[type="text"] { width: 100%; padding: 0.5rem; margin-bottom: 1rem; box-sizing: border-box; }
        .actions { display: flex; gap: 0.5rem; margin-bottom: 1.5rem; }
        button { padding: 0.5rem 1rem; cursor: pointer; }
        .alert { padding: 0.75rem; border-radius: 4px; margin-bottom: 1rem; }
        .alert-error { background: #fee; color: #900; border: 1px solid #fcc; }
        .icons { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 1rem; }
        .icon-card { border: 1px solid #ddd; border-radius: 8px; padding: 1rem; text-align: center; }
        .icon-card img { max-width: 128px; max-height: 128px; display: block; margin: 0.5rem auto; }
        .icon-card .missing { color: #999; font-style: italic; }
        .task-meta { font-size: 0.875rem; color: #555; margin-bottom: 0.5rem; }
        .task-list { display: flex; flex-direction: column; gap: 1rem; }
        .task-item { border: 1px solid #ddd; border-radius: 8px; padding: 1rem; }
        .store-errors { color: #b45309; font-size: 0.875rem; margin-top: 0.5rem; }
    </style>
</head>
<body>
    <h1>App Icon Fetcher</h1>

    <label for="bundle_id">Bundle ID</label>
    <input id="bundle_id" type="text" value="com.zhiliaoapp.musically" placeholder="com.example.app">

    <div class="actions">
        <button type="button" id="fetchBtn">Fetch icons</button>
        <button type="button" id="listBtn">List tasks</button>
    </div>

    <div id="alert" hidden></div>
    <div id="result"></div>

    <script>
        const bundleInput = document.getElementById('bundle_id');
        const alertEl = document.getElementById('alert');
        const resultEl = document.getElementById('result');
        const apiBase = '/api/v1/app-icons/tasks';

        document.getElementById('fetchBtn').addEventListener('click', fetchIcons);
        document.getElementById('listBtn').addEventListener('click', listTasks);

        function showAlert(message) {
            alertEl.hidden = false;
            alertEl.className = 'alert alert-error';
            alertEl.textContent = message;
        }

        function clearAlert() {
            alertEl.hidden = true;
            alertEl.textContent = '';
        }

        async function fetchIcons() {
            clearAlert();
            resultEl.innerHTML = '<p>Loading…</p>';

            try {
                const response = await fetch(apiBase, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                    },
                    body: JSON.stringify({ bundle_id: bundleInput.value.trim() }),
                });

                const json = await response.json();

                if (!response.ok) {
                    const message = json.message
                        || Object.values(json.errors || {}).flat().join(' ')
                        || 'Request failed';
                    showAlert(message);
                    resultEl.innerHTML = '';
                    return;
                }

                resultEl.innerHTML = renderTask(json.data);
            } catch (error) {
                showAlert(error.message);
                resultEl.innerHTML = '';
            }
        }

        async function listTasks() {
            clearAlert();
            resultEl.innerHTML = '<p>Loading…</p>';

            try {
                const response = await fetch(apiBase, {
                    headers: { Accept: 'application/json' },
                });

                const json = await response.json();

                if (!response.ok) {
                    showAlert(json.message || 'Request failed');
                    resultEl.innerHTML = '';
                    return;
                }

                const tasks = json.data || [];

                if (tasks.length === 0) {
                    resultEl.innerHTML = '<p>No tasks yet.</p>';
                    return;
                }

                resultEl.innerHTML = '<div class="task-list">' + tasks.map(renderTask).join('') + '</div>';
            } catch (error) {
                showAlert(error.message);
                resultEl.innerHTML = '';
            }
        }

        function renderTask(task) {
            const errors = task.errors || {};
            const errorLines = Object.entries(errors)
                .map(([store, message]) => `<div class="store-errors">${store}: ${message}</div>`)
                .join('');

            return `
                <div class="task-item">
                    <div class="task-meta">#${task.id} · ${task.bundle_id} · ${task.status}</div>
                    <div class="icons">
                        <div class="icon-card">
                            <strong>Apple</strong>
                            ${task.apple_icon_url
                                ? `<img src="${task.apple_icon_url}" alt="Apple icon">`
                                : '<div class="missing">Icon not found</div>'}
                        </div>
                        <div class="icon-card">
                            <strong>Google Play</strong>
                            ${task.google_icon_url
                                ? `<img src="${task.google_icon_url}" alt="Google Play icon">`
                                : '<div class="missing">Icon not found</div>'}
                        </div>
                    </div>
                    ${errorLines}
                </div>
            `;
        }
    </script>
</body>
</html>
