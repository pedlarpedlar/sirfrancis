<?php
if (!function_exists('sfSiteImageOverridesFile')) {
    function sfSiteImageOverridesFile() {
        return __DIR__ . '/sheet_cache/site_image_overrides.json';
    }
}

if (!function_exists('sfSiteImageOverrides')) {
    function sfSiteImageOverrides() {
        $file = sfSiteImageOverridesFile();
        if (!is_file($file)) {
            return [];
        }

        $decoded = json_decode((string) @file_get_contents($file), true);
        return is_array($decoded) ? $decoded : [];
    }
}

if (!function_exists('sfSiteImagePath')) {
    function sfSiteImagePath($key, $fallback) {
        $key = preg_replace('/[^a-zA-Z0-9_.-]/', '_', (string) $key);
        $fallback = (string) $fallback;
        $overrides = sfSiteImageOverrides();
        $path = trim((string) ($overrides[$key]['path'] ?? ''));
        return $path !== '' ? $path : $fallback;
    }
}

if (!function_exists('sfSiteImageRecommendedSizeAttr')) {
    function sfSiteImageRecommendedSizeAttr($recommendedSize = '') {
        $recommendedSize = trim((string) $recommendedSize);
        if ($recommendedSize === '') {
            return '';
        }
        return ' data-sf-image-size="' . htmlspecialchars($recommendedSize, ENT_QUOTES, 'UTF-8') . '"';
    }
}

if (!function_exists('sfSiteEditableImageAttrs')) {
    function sfSiteEditableImageAttrs($key, $recommendedSize = '') {
        if (empty($_SESSION['admin_id'])) {
            return '';
        }

        return ' data-sf-editable-image="' . htmlspecialchars((string) $key, ENT_QUOTES, 'UTF-8') . '"' . sfSiteImageRecommendedSizeAttr($recommendedSize);
    }
}

if (!function_exists('sfSiteEditableBackgroundAttrs')) {
    function sfSiteEditableBackgroundAttrs($key, $imagePath = '', $recommendedSize = '') {
        if (empty($_SESSION['admin_id'])) {
            return '';
        }

        $attrs = ' data-sf-editable-image="' . htmlspecialchars((string) $key, ENT_QUOTES, 'UTF-8') . '" data-sf-editable-bg="1"' . sfSiteImageRecommendedSizeAttr($recommendedSize);
        if ($imagePath !== '') {
            $attrs .= ' data-sf-current-image="' . htmlspecialchars((string) $imagePath, ENT_QUOTES, 'UTF-8') . '"';
        }
        return $attrs;
    }
}

if (!function_exists('sfSiteBackgroundStyle')) {
    function sfSiteBackgroundStyle($imagePath) {
        $imagePath = trim((string) $imagePath);
        if ($imagePath === '') {
            return '';
        }
        return ' style="background-image:url(\'' . htmlspecialchars($imagePath, ENT_QUOTES, 'UTF-8') . '\');"';
    }
}
