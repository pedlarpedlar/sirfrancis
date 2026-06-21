<?php
include '../session_logins.php';

if (empty($_SESSION['admin_id'])) {
    header('Location: admin_login?redirect=' . urlencode('manage_gallery'));
    exit;
}

include 'header.php';
?>

<title>Image Gallery - Sir Francis Admin</title>

<style>
    .gallery-page {
        background: var(--sf-cream);
        min-height: 100vh;
        padding: 32px 0 56px;
    }
    .gallery-shell {
        max-width: 1320px;
        margin: 0 auto;
        padding: 0 18px;
    }
    .gallery-hero,
    .gallery-panel {
        background: #fff;
        border: 1px solid var(--sf-border);
        border-radius: 8px;
        box-shadow: 0 12px 30px rgba(23, 34, 53, .08);
    }
    .gallery-hero {
        padding: 24px;
        margin-bottom: 18px;
        display: flex;
        justify-content: space-between;
        gap: 18px;
        align-items: center;
    }
    .gallery-hero h1 {
        margin: 0 0 8px;
        color: var(--sf-navy);
        font-size: 30px;
        font-weight: 800;
    }
    .gallery-hero p {
        margin: 0;
        color: var(--sf-muted);
        max-width: 720px;
    }
    .gallery-panel {
        padding: 18px;
        margin-bottom: 18px;
    }
    .gallery-upload-options {
        display: grid;
        grid-template-columns: minmax(240px, 1fr) minmax(240px, 1fr);
        gap: 16px;
        margin-bottom: 18px;
    }
    .gallery-browse-layout {
        display: grid;
        grid-template-columns: minmax(240px, 340px) 1fr;
        gap: 16px;
        margin-bottom: 16px;
    }
    .gallery-dropzone {
        border: 2px dashed var(--sf-gold);
        border-radius: 8px;
        min-height: 240px;
        padding: 64px 24px;
        text-align: center;
        background: #fbfaf5;
        color: var(--sf-ink);
        cursor: pointer;
        transition: .18s ease;
    }
    .gallery-dropzone.is-dragging,
    .gallery-folder-drop.is-dragging {
        border-color: var(--sf-navy);
        background: #f4f1e7;
    }
    .gallery-dropzone strong {
        display: block;
        font-size: 24px;
        margin-bottom: 6px;
    }
    .gallery-form-row {
        display: grid;
        gap: 10px;
        margin-top: 14px;
    }
    .gallery-upload-options .gallery-form-row {
        margin-top: 0;
    }
    .gallery-form-row label {
        margin: 0;
        font-weight: 700;
        color: var(--sf-navy);
    }
    .gallery-form-row select,
    .gallery-form-row input {
        width: 100%;
        border: 1px solid var(--sf-border);
        border-radius: 8px;
        padding: 10px 12px;
        min-height: 42px;
    }
    .gallery-actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        margin-top: 14px;
    }
    .gallery-btn {
        border: 0;
        border-radius: 8px;
        padding: 10px 14px;
        font-weight: 800;
        cursor: pointer;
        background: var(--sf-navy);
        color: #fff;
    }
    .gallery-btn.secondary {
        background: var(--sf-gold-soft);
        color: var(--sf-navy);
    }
    .gallery-btn.danger {
        background: #9f1f1f;
    }
    .gallery-toolbar {
        display: grid;
        grid-template-columns: minmax(180px, 300px) 1fr;
        gap: 12px;
        margin-bottom: 16px;
    }
    .gallery-folder-drop {
        border: 1px dashed var(--sf-border);
        border-radius: 8px;
        padding: 12px;
        margin-top: 14px;
        color: var(--sf-muted);
        background: #fbfaf5;
        font-size: 13px;
    }
    .gallery-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(210px, 1fr));
        gap: 14px;
    }
    .gallery-card {
        border: 1px solid var(--sf-border);
        border-radius: 8px;
        background: #fff;
        overflow: hidden;
    }
    .gallery-card[draggable="true"] {
        cursor: grab;
    }
    .gallery-thumb {
        aspect-ratio: 1 / 1;
        width: 100%;
        background: #f4f1e7;
        object-fit: cover;
        display: block;
    }
    .gallery-card-body {
        padding: 12px;
    }
    .gallery-file-name {
        font-weight: 800;
        color: var(--sf-navy);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        margin-bottom: 4px;
    }
    .gallery-meta {
        color: var(--sf-muted);
        font-size: 12px;
        min-height: 18px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .gallery-card input,
    .gallery-card select {
        width: 100%;
        border: 1px solid var(--sf-border);
        border-radius: 6px;
        padding: 8px 9px;
        margin-top: 8px;
    }
    .gallery-mini-actions {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
        margin-top: 8px;
    }
    .gallery-mini-actions .gallery-btn {
        padding: 8px 10px;
        font-size: 12px;
    }
    .gallery-status {
        margin-top: 10px;
        color: var(--sf-ink);
        font-weight: 700;
        min-height: 20px;
    }
    .gallery-status.warning {
        background: #fbfaf5;
        border: 1px solid var(--sf-gold);
        border-radius: 8px;
        color: var(--sf-navy);
        padding: 9px 11px;
    }
    .gallery-upload-list {
        display: grid;
        gap: 10px;
        margin-top: 14px;
    }
    .gallery-upload-item {
        align-items: end;
        background: #fbfaf5;
        border: 1px solid var(--sf-border);
        border-radius: 8px;
        display: grid;
        gap: 10px;
        grid-template-columns: minmax(160px, 1fr) minmax(220px, 1.3fr);
        padding: 12px;
    }
    .gallery-upload-original {
        color: var(--sf-muted);
        font-size: 13px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .gallery-upload-preview-row {
        align-items: center;
        display: grid;
        gap: 10px;
        grid-template-columns: 56px minmax(0, 1fr);
    }
    .gallery-upload-preview {
        aspect-ratio: 1 / 1;
        background: #f4f1e7;
        border: 1px solid var(--sf-border);
        border-radius: 8px;
        object-fit: cover;
        width: 56px;
    }
    .gallery-upload-item label {
        color: var(--sf-navy);
        display: block;
        font-size: 12px;
        font-weight: 800;
        margin-bottom: 5px;
    }
    .gallery-upload-item input {
        border: 1px solid var(--sf-border);
        border-radius: 8px;
        min-height: 40px;
        padding: 8px 10px;
        width: 100%;
    }
    .gallery-compress-box {
        background: #f8f4ee;
        border: 1px solid var(--sf-border);
        border-radius: 8px;
        display: grid;
        gap: 12px;
        grid-template-columns: minmax(180px, 1fr) minmax(190px, 260px);
        margin: 12px 0;
        padding: 12px;
    }
    .gallery-compress-box label {
        color: var(--sf-navy);
        display: block;
        font-size: 12px;
        font-weight: 800;
        margin-bottom: 5px;
        text-transform: uppercase;
    }
    .gallery-compress-box select {
        border: 1px solid var(--sf-border);
        border-radius: 8px;
        min-height: 40px;
        padding: 8px 10px;
        width: 100%;
    }
    .gallery-compress-check {
        align-items: center;
        display: flex !important;
        gap: 8px;
        margin: 24px 0 0 !important;
        text-transform: none !important;
    }
    .gallery-compress-check input {
        height: 18px;
        width: 18px;
    }
    .gallery-empty {
        padding: 34px 18px;
        text-align: center;
        color: var(--sf-muted);
        background: #fbfaf5;
        border: 1px dashed var(--sf-border);
        border-radius: 8px;
    }
    .gallery-cleanup-head {
        align-items: center;
        display: flex;
        gap: 14px;
        justify-content: space-between;
        flex-wrap: wrap;
    }
    .gallery-cleanup-head p {
        color: var(--sf-muted);
        margin: 6px 0 0;
        max-width: 780px;
    }
    .gallery-cleanup-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
        gap: 12px;
        margin-top: 14px;
    }
    .gallery-cleanup-card {
        background: #fbfaf5;
        border: 1px solid var(--sf-border);
        border-radius: 8px;
        overflow: hidden;
    }
    .gallery-cleanup-card img {
        aspect-ratio: 1 / 1;
        display: block;
        object-fit: cover;
        width: 100%;
    }
    .gallery-cleanup-card div {
        color: var(--sf-muted);
        font-size: 12px;
        overflow: hidden;
        padding: 9px;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    @media (max-width: 900px) {
        .gallery-upload-options,
        .gallery-browse-layout,
        .gallery-upload-item,
        .gallery-compress-box,
        .gallery-toolbar {
            grid-template-columns: 1fr;
        }
        .gallery-hero {
            display: block;
        }
    }
</style>

<?php include 'page_menues.php'; ?>

<main class="gallery-page">
    <div class="gallery-shell">
        <section class="gallery-hero">
            <div>
                <h1>Image Gallery</h1>
                <p>Upload product-page images into <strong>assets/img/product</strong> by default, then copy the live URL into the product sheet <strong>img_url</strong> column. You can still choose another image folder when needed.</p>
            </div>
        </section>

        <section class="gallery-panel">
            <h2 class="h5 mb-3">Upload Images</h2>
            <div class="gallery-upload-options">
                <div>
                    <div class="gallery-form-row" style="margin-top:0;">
                        <label for="galleryUploadFolder">Upload to folder</label>
                        <select id="galleryUploadFolder"></select>
                    </div>
                </div>
                <div>
                    <div class="gallery-form-row">
                        <label for="galleryNewFolder">Or create a new folder inside assets/img</label>
                        <input id="galleryNewFolder" type="text" placeholder="Example: assets/img/campaigns/june-specials">
                    </div>
                </div>
            </div>
            <div id="galleryDropzone" class="gallery-dropzone" tabindex="0">
                <strong>Drop images here</strong>
                <span>or click to choose JPG, PNG, WebP or GIF files.</span>
                <input id="galleryFiles" type="file" accept="image/jpeg,image/png,image/webp,image/gif" multiple hidden>
            </div>
            <div class="gallery-compress-box">
                <label class="gallery-compress-check" for="galleryCompressImages">
                    <input id="galleryCompressImages" type="checkbox" checked>
                    Compress images before saving
                </label>
                <div>
                    <label for="galleryCompressTarget">How small?</label>
                    <select id="galleryCompressTarget">
                        <option value="400" selected>Good web size, about 400 KB</option>
                        <option value="300">Smaller, about 300 KB</option>
                        <option value="200">Tiny, about 200 KB</option>
                    </select>
                    <div class="gallery-meta">We will try to get close to this size without damaging the quality.</div>
                </div>
            </div>
            <div id="galleryUploadList" class="gallery-upload-list"></div>
            <div class="gallery-actions">
                <button id="galleryUploadBtn" class="gallery-btn" type="button">Upload</button>
                <button id="galleryClearBtn" class="gallery-btn secondary" type="button">Clear</button>
            </div>
            <div id="galleryUploadStatus" class="gallery-status"></div>
        </section>

        <section class="gallery-panel">
            <div class="gallery-cleanup-head">
                <div>
                    <h2 class="h5 mb-0">Clean Up Unused Images</h2>
                    <p>This scans website files and settings for image links, then lists unused images outside product folders. Product folders are protected and will not be cleaned here.</p>
                </div>
                <div class="gallery-actions" style="margin-top:0;">
                    <button id="galleryCleanupScanBtn" class="gallery-btn secondary" type="button">Scan unused</button>
                    <button id="galleryCleanupDeleteBtn" class="gallery-btn danger" type="button" style="display:none;">Delete listed unused</button>
                </div>
            </div>
            <div id="galleryCleanupStatus" class="gallery-status"></div>
            <div id="galleryCleanupGrid" class="gallery-cleanup-grid"></div>
        </section>

        <section class="gallery-panel">
            <h2 class="h5 mb-3">Gallery Images</h2>
            <div class="gallery-browse-layout">
                <div>
                    <div class="gallery-form-row">
                        <label for="galleryBrowseFolder">Current folder</label>
                        <select id="galleryBrowseFolder"></select>
                    </div>
                    <div class="gallery-form-row">
                        <label for="gallerySearch">Filter loaded images</label>
                        <input id="gallerySearch" type="search" placeholder="Search file name or folder">
                    </div>
                    <div id="galleryFolderDrop" class="gallery-folder-drop">
                        Drag an image card here after choosing a folder to move it quickly.
                    </div>
                    <div class="gallery-actions">
                        <button id="galleryRefreshBtn" class="gallery-btn secondary" type="button">Refresh</button>
                    </div>
                    <div id="galleryBrowseStatus" class="gallery-status"></div>
                </div>
                <div>
                <div class="gallery-toolbar">
                    <div>
                        <strong id="galleryCount">Loading images...</strong>
                        <div class="gallery-meta" id="galleryCurrentFolder"></div>
                    </div>
                    <div class="text-lg-right">
                        <button id="galleryLoadMoreBtn" class="gallery-btn secondary" type="button" style="display:none;">Load more</button>
                    </div>
                </div>
                <div id="galleryGrid" class="gallery-grid"></div>
                <div id="gallerySentinel" style="height:1px;"></div>
                </div>
            </div>
        </section>
    </div>
</main>

<script>
(function () {
    var apiUrl = 'upload_gallery_images.php';
    var defaultUploadFolder = 'product';
    var folders = [];
    var currentFolder = defaultUploadFolder;
    var offset = 0;
    var hasMore = false;
    var loading = false;
    var loadedImages = [];
    var draggedPath = '';
    var searchTimer = null;
    var uploadPreviewUrls = [];

    var dropzone = document.getElementById('galleryDropzone');
    var fileInput = document.getElementById('galleryFiles');
    var uploadFolder = document.getElementById('galleryUploadFolder');
    var browseFolder = document.getElementById('galleryBrowseFolder');
    var newFolder = document.getElementById('galleryNewFolder');
    var grid = document.getElementById('galleryGrid');
    var search = document.getElementById('gallerySearch');
    var uploadStatus = document.getElementById('galleryUploadStatus');
    var uploadList = document.getElementById('galleryUploadList');
    var browseStatus = document.getElementById('galleryBrowseStatus');
    var countLabel = document.getElementById('galleryCount');
    var currentFolderLabel = document.getElementById('galleryCurrentFolder');
    var loadMoreBtn = document.getElementById('galleryLoadMoreBtn');
    var folderDrop = document.getElementById('galleryFolderDrop');
    var compressImages = document.getElementById('galleryCompressImages');
    var compressTarget = document.getElementById('galleryCompressTarget');
    var cleanupScanBtn = document.getElementById('galleryCleanupScanBtn');
    var cleanupDeleteBtn = document.getElementById('galleryCleanupDeleteBtn');
    var cleanupStatus = document.getElementById('galleryCleanupStatus');
    var cleanupGrid = document.getElementById('galleryCleanupGrid');
    var canCompress = true;
    var cleanupImages = [];

    function folderLabel(folder) {
        if (folder === '__all__') return 'All assets/img folders';
        return folder ? 'assets/img/' + folder : 'assets/img';
    }

    function cleanFolderInput(folder) {
        return String(folder || '').replace(/\\/g, '/').replace(/^\/+|\/+$/g, '').replace(/^(assets\/)?img\/?/i, '').replace(/^\/+|\/+$/g, '');
    }

    function escapeHtml(value) {
        return String(value || '').replace(/[&<>"']/g, function (char) {
            return ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'}[char]);
        });
    }

    function formatBytes(bytes) {
        if (!bytes) return '0 KB';
        var units = ['B', 'KB', 'MB'];
        var size = bytes;
        var unit = 0;
        while (size >= 1024 && unit < units.length - 1) {
            size = size / 1024;
            unit++;
        }
        return size.toFixed(unit === 0 ? 0 : 1) + ' ' + units[unit];
    }

    function baseName(name) {
        return String(name || '').replace(/\.[^.]+$/, '');
    }

    function renderUploadList() {
        uploadPreviewUrls.forEach(function (url) {
            URL.revokeObjectURL(url);
        });
        uploadPreviewUrls = [];
        if (!fileInput.files.length) {
            uploadList.innerHTML = '';
            return;
        }
        uploadList.innerHTML = Array.prototype.map.call(fileInput.files, function (file, index) {
            var previewUrl = URL.createObjectURL(file);
            uploadPreviewUrls.push(previewUrl);
            return [
                '<div class="gallery-upload-item">',
                    '<div>',
                        '<label>Selected image</label>',
                        '<div class="gallery-upload-preview-row">',
                            '<img class="gallery-upload-preview" src="' + escapeHtml(previewUrl) + '" alt="' + escapeHtml(file.name) + ' preview">',
                            '<div class="gallery-upload-original" title="' + escapeHtml(file.name) + '">' + escapeHtml(file.name) + ' - ' + formatBytes(file.size) + '</div>',
                        '</div>',
                    '</div>',
                    '<div>',
                        '<label for="galleryUploadName' + index + '">Save as</label>',
                        '<input id="galleryUploadName' + index + '" data-upload-name="' + index + '" type="text" value="' + escapeHtml(baseName(file.name)) + '">',
                    '</div>',
                '</div>'
            ].join('');
        }).join('');
    }

    function postAction(action, data) {
        data.append('action', action);
        return fetch(apiUrl, { method: 'POST', body: data }).then(function (response) {
            return response.json().then(function (json) {
                if (!response.ok || !json.success) {
                    throw new Error(json.message || 'Gallery request failed.');
                }
                return json;
            });
        });
    }

    function renderFolderOptions() {
        uploadFolder.innerHTML = folders.map(function (folder) {
            return '<option value="' + escapeHtml(folder) + '">' + escapeHtml(folderLabel(folder)) + '</option>';
        }).join('');
        uploadFolder.value = folders.indexOf(defaultUploadFolder) !== -1 ? defaultUploadFolder : '';

        browseFolder.innerHTML = ['__all__'].concat(folders).map(function (folder) {
            return '<option value="' + escapeHtml(folder) + '">' + escapeHtml(folderLabel(folder)) + '</option>';
        }).join('');
        browseFolder.value = currentFolder;
    }

    function loadFolders() {
        return fetch(apiUrl + '?action=folders')
            .then(function (response) { return response.json(); })
            .then(function (json) {
                if (!json.success) throw new Error(json.message || 'Could not load folders.');
                folders = json.folders || [''];
                canCompress = json.can_compress !== false;
                if (!canCompress) {
                    uploadStatus.classList.add('warning');
                    uploadStatus.textContent = 'Compression is not available on this server yet. Uploads will save at their original size until PHP GD is enabled.';
                } else if (uploadStatus.textContent.indexOf('Compression is not available') !== -1) {
                    uploadStatus.classList.remove('warning');
                    uploadStatus.textContent = '';
                }
                if (folders.indexOf(defaultUploadFolder) === -1) {
                    folders.push(defaultUploadFolder);
                    folders.sort();
                }
                renderFolderOptions();
            });
    }

    function renderImages() {
        var images = loadedImages;

        countLabel.textContent = loadedImages.length + (hasMore ? '+ loaded' : ' image(s)');
        currentFolderLabel.textContent = folderLabel(currentFolder);
        loadMoreBtn.style.display = hasMore ? 'inline-block' : 'none';

        if (images.length === 0) {
            grid.innerHTML = '<div class="gallery-empty">No images found for this search.</div>';
            return;
        }

        grid.innerHTML = images.map(function (image) {
            var folderOptions = folders.map(function (folder) {
                var selected = folder === image.folder ? ' selected' : '';
                return '<option value="' + escapeHtml(folder) + '"' + selected + '>' + escapeHtml(folderLabel(folder)) + '</option>';
            }).join('');
            return [
                '<article class="gallery-card" draggable="true" data-path="' + escapeHtml(image.relative_path) + '">',
                    '<img class="gallery-thumb" src="' + escapeHtml(image.url) + '" alt="' + escapeHtml(image.name) + '" loading="lazy">',
                    '<div class="gallery-card-body">',
                        '<div class="gallery-file-name" title="' + escapeHtml(image.name) + '">' + escapeHtml(image.name) + '</div>',
                        '<div class="gallery-meta" title="' + escapeHtml(image.relative_path) + '">' + escapeHtml(image.relative_path) + '</div>',
                        '<div class="gallery-meta">' + formatBytes(image.size) + '</div>',
                        '<input type="text" value="' + escapeHtml(image.url) + '" readonly>',
                        '<div class="gallery-mini-actions">',
                            '<button class="gallery-btn secondary" type="button" data-copy="' + escapeHtml(image.url) + '">Copy URL</button>',
                            '<button class="gallery-btn secondary" type="button" data-compress-copy="' + escapeHtml(image.relative_path) + '">Compress copy</button>',
                            '<button class="gallery-btn danger" type="button" data-delete="' + escapeHtml(image.relative_path) + '">Delete</button>',
                        '</div>',
                        '<input type="text" value="' + escapeHtml(image.name.replace(/\.[^.]+$/, '')) + '" data-rename-input="' + escapeHtml(image.relative_path) + '">',
                        '<button class="gallery-btn secondary" type="button" data-rename="' + escapeHtml(image.relative_path) + '" style="width:100%;margin-top:8px;">Rename</button>',
                        '<select data-move-select="' + escapeHtml(image.relative_path) + '">' + folderOptions + '</select>',
                        '<button class="gallery-btn secondary" type="button" data-move="' + escapeHtml(image.relative_path) + '" style="width:100%;margin-top:8px;">Move</button>',
                    '</div>',
                '</article>'
            ].join('');
        }).join('');
    }

    function renderCleanupImages(images) {
        cleanupImages = images || [];
        cleanupDeleteBtn.style.display = cleanupImages.length ? 'inline-block' : 'none';
        if (!cleanupImages.length) {
            cleanupGrid.innerHTML = '';
            return;
        }
        cleanupGrid.innerHTML = cleanupImages.map(function (image) {
            return [
                '<article class="gallery-cleanup-card">',
                    '<img src="' + escapeHtml(image.url) + '" alt="' + escapeHtml(image.name) + '" loading="lazy">',
                    '<div title="' + escapeHtml(image.relative_path) + '">' + escapeHtml(image.relative_path) + '</div>',
                '</article>'
            ].join('');
        }).join('');
    }

    function scanUnusedImages() {
        cleanupStatus.classList.remove('warning');
        cleanupStatus.textContent = 'Scanning website references...';
        cleanupGrid.innerHTML = '';
        cleanupDeleteBtn.style.display = 'none';
        cleanupImages = [];
        return fetch(apiUrl + '?action=cleanup_preview')
            .then(function (response) { return response.json(); })
            .then(function (json) {
                if (!json.success) throw new Error(json.message || 'Cleanup scan failed.');
                renderCleanupImages(json.images || []);
                cleanupStatus.textContent = (json.images || []).length
                    + ' unused image(s) found. Scanned ' + (json.scanned || 0)
                    + ', protected ' + (json.protected || 0)
                    + ' product-folder image(s), found ' + (json.referenced || 0) + ' linked image(s).';
                if (!(json.images || []).length) {
                    cleanupStatus.textContent += ' Nothing to delete.';
                }
            })
            .catch(function (error) {
                cleanupStatus.classList.add('warning');
                cleanupStatus.textContent = error.message;
            });
    }

    function deleteUnusedImages() {
        if (!cleanupImages.length) {
            cleanupStatus.textContent = 'Run a scan first.';
            return;
        }
        if (!confirm('Delete the ' + cleanupImages.length + ' listed unused image(s)? Product folders are protected.')) {
            return;
        }
        var data = new FormData();
        cleanupImages.forEach(function (image) {
            data.append('paths[]', image.relative_path);
        });
        cleanupStatus.classList.remove('warning');
        cleanupStatus.textContent = 'Deleting unused images...';
        postAction('cleanup_delete', data)
            .then(function (json) {
                cleanupStatus.textContent = json.message || 'Cleanup complete.';
                renderCleanupImages([]);
                return loadFolders();
            })
            .then(function () {
                return loadImages(true);
            })
            .catch(function (error) {
                cleanupStatus.classList.add('warning');
                cleanupStatus.textContent = error.message;
            });
    }

    function loadImages(reset) {
        if (loading) return Promise.resolve();
        loading = true;
        if (reset) {
            offset = 0;
            loadedImages = [];
            grid.innerHTML = '<div class="gallery-empty">Loading images...</div>';
        }
        browseStatus.textContent = 'Loading...';
        return fetch(apiUrl + '?action=list&folder=' + encodeURIComponent(currentFolder) + '&offset=' + offset + '&limit=30&q=' + encodeURIComponent(search.value.trim()))
            .then(function (response) { return response.json(); })
            .then(function (json) {
                if (!json.success) throw new Error(json.message || 'Could not load images.');
                loadedImages = loadedImages.concat(json.images || []);
                offset = json.next_offset || loadedImages.length;
                hasMore = !!json.has_more;
                browseStatus.textContent = '';
                renderImages();
            })
            .catch(function (error) {
                browseStatus.textContent = error.message;
            })
            .finally(function () {
                loading = false;
            });
    }

    function uploadImages() {
        if (!fileInput.files.length) {
            uploadStatus.textContent = 'Choose or drop at least one image first.';
            return;
        }
        var destinationFolder = cleanFolderInput(newFolder.value.trim() || uploadFolder.value || defaultUploadFolder);
        var data = new FormData();
        Array.prototype.forEach.call(fileInput.files, function (file) {
            data.append('images[]', file);
        });
        Array.prototype.forEach.call(uploadList.querySelectorAll('[data-upload-name]'), function (input) {
            data.append('image_names[]', input.value);
        });
        data.append('folder', cleanFolderInput(uploadFolder.value || defaultUploadFolder));
        data.append('new_folder', cleanFolderInput(newFolder.value.trim()));
        data.append('compress_images', compressImages.checked ? '1' : '');
        data.append('compress_target_kb', compressTarget.value || '400');
        uploadStatus.textContent = 'Uploading...';
        uploadStatus.classList.remove('warning');
        postAction('upload', data)
            .then(function (json) {
                var notes = [];
                if (json.errors && json.errors.length) notes.push('Some files were skipped.');
                if (json.compression_notes && json.compression_notes.length) {
                    notes.push(json.compression_notes.join(' | '));
                } else if (compressImages.checked) {
                    notes.push('Compression did not run. The saved file may still be the original size.');
                }
                uploadStatus.textContent = json.message + (notes.length ? ' ' + notes.join(' ') : '');
                if (uploadStatus.textContent.indexOf('not available') !== -1 || uploadStatus.textContent.indexOf('did not run') !== -1) {
                    uploadStatus.classList.add('warning');
                }
                fileInput.value = '';
                uploadList.innerHTML = '';
                newFolder.value = '';
                currentFolder = destinationFolder || currentFolder;
                return loadFolders();
            })
            .then(function () {
                browseFolder.value = currentFolder;
                return loadImages(true);
            })
            .catch(function (error) {
                uploadStatus.textContent = error.message;
            });
    }

    function moveImage(path, folder) {
        var data = new FormData();
        data.append('path', path);
        data.append('folder', folder);
        browseStatus.textContent = 'Moving image...';
        return postAction('move', data)
            .then(function () {
                return loadFolders();
            })
            .then(function () {
                return loadImages(true);
            })
            .catch(function (error) {
                browseStatus.textContent = error.message;
            });
    }

    dropzone.addEventListener('click', function () { fileInput.click(); });
    dropzone.addEventListener('keydown', function (event) {
        if (event.key === 'Enter' || event.key === ' ') fileInput.click();
    });
    ['dragenter', 'dragover'].forEach(function (name) {
        dropzone.addEventListener(name, function (event) {
            event.preventDefault();
            dropzone.classList.add('is-dragging');
        });
    });
    ['dragleave', 'drop'].forEach(function (name) {
        dropzone.addEventListener(name, function (event) {
            event.preventDefault();
            dropzone.classList.remove('is-dragging');
        });
    });
    dropzone.addEventListener('drop', function (event) {
        fileInput.files = event.dataTransfer.files;
        uploadStatus.textContent = fileInput.files.length + ' image(s) ready to upload.';
        renderUploadList();
    });
    fileInput.addEventListener('change', function () {
        uploadStatus.textContent = fileInput.files.length ? fileInput.files.length + ' image(s) ready to upload.' : '';
        renderUploadList();
    });

    document.getElementById('galleryUploadBtn').addEventListener('click', uploadImages);
    document.getElementById('galleryClearBtn').addEventListener('click', function () {
        fileInput.value = '';
        uploadList.innerHTML = '';
        newFolder.value = '';
        uploadStatus.textContent = '';
    });
    document.getElementById('galleryRefreshBtn').addEventListener('click', function () {
        loadFolders().then(function () { loadImages(true); });
    });
    cleanupScanBtn.addEventListener('click', scanUnusedImages);
    cleanupDeleteBtn.addEventListener('click', deleteUnusedImages);
    browseFolder.addEventListener('change', function () {
        currentFolder = browseFolder.value;
        loadImages(true);
    });
    function searchImages() {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function () {
            loadImages(true);
        }, 250);
    }
    if (window.jQuery) {
        window.jQuery(search).on('input', searchImages);
    } else {
        search.addEventListener('input', searchImages);
    }
    loadMoreBtn.addEventListener('click', function () { loadImages(false); });

    grid.addEventListener('click', function (event) {
        var copy = event.target.getAttribute('data-copy');
        var remove = event.target.getAttribute('data-delete');
        var rename = event.target.getAttribute('data-rename');
        var move = event.target.getAttribute('data-move');
        var compressCopy = event.target.getAttribute('data-compress-copy');

        if (copy) {
            navigator.clipboard.writeText(copy);
            browseStatus.textContent = 'Image URL copied.';
        }
        if (compressCopy) {
            if (!canCompress) {
                browseStatus.classList.add('warning');
                browseStatus.textContent = 'Compression is not available on this server yet. PHP GD needs to be enabled first.';
                return;
            }
            var compressData = new FormData();
            compressData.append('path', compressCopy);
            compressData.append('compress_target_kb', compressTarget.value || '400');
            browseStatus.classList.remove('warning');
            browseStatus.textContent = 'Creating compressed copy...';
            postAction('compress_copy', compressData).then(function (json) {
                browseStatus.textContent = 'Compressed copy created.' + (json.compression_note ? ' ' + json.compression_note : '');
                return loadImages(true);
            }).catch(function (error) {
                browseStatus.textContent = error.message;
            });
        }
        if (remove && confirm('Delete this image from assets/img?')) {
            var deleteData = new FormData();
            deleteData.append('path', remove);
            postAction('delete', deleteData).then(function () {
                return loadImages(true);
            }).catch(function (error) {
                browseStatus.textContent = error.message;
            });
        }
        if (rename) {
            var input = grid.querySelector('[data-rename-input="' + CSS.escape(rename) + '"]');
            var renameData = new FormData();
            renameData.append('path', rename);
            renameData.append('name', input ? input.value : '');
            postAction('rename', renameData).then(function () {
                return loadImages(true);
            }).catch(function (error) {
                browseStatus.textContent = error.message;
            });
        }
        if (move) {
            var select = grid.querySelector('[data-move-select="' + CSS.escape(move) + '"]');
            moveImage(move, select ? select.value : '');
        }
    });

    grid.addEventListener('dragstart', function (event) {
        var card = event.target.closest('.gallery-card');
        if (!card) return;
        draggedPath = card.getAttribute('data-path');
        event.dataTransfer.setData('text/plain', draggedPath);
    });
    ['dragenter', 'dragover'].forEach(function (name) {
        folderDrop.addEventListener(name, function (event) {
            event.preventDefault();
            folderDrop.classList.add('is-dragging');
        });
    });
    ['dragleave', 'drop'].forEach(function (name) {
        folderDrop.addEventListener(name, function (event) {
            event.preventDefault();
            folderDrop.classList.remove('is-dragging');
        });
    });
    folderDrop.addEventListener('drop', function (event) {
        var path = event.dataTransfer.getData('text/plain') || draggedPath;
        if (!path) return;
        var targetFolder = browseFolder.value === '__all__' ? uploadFolder.value : browseFolder.value;
        moveImage(path, targetFolder || '');
    });

    if ('IntersectionObserver' in window) {
        var observer = new IntersectionObserver(function (entries) {
            if (entries[0].isIntersecting && hasMore && !loading) {
                loadImages(false);
            }
        });
        observer.observe(document.getElementById('gallerySentinel'));
    }

    loadFolders().then(function () {
        browseFolder.value = currentFolder;
        return loadImages(true);
    }).catch(function (error) {
        browseStatus.textContent = error.message;
    });
})();
</script>

<?php include '../footer.php'; ?>
