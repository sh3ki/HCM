<?php
/**
 * Application URL helpers for dynamic local/live deployment.
 */

if (!function_exists('app_env')) {
    function app_env($key, $default = null) {
        $value = getenv($key);

        if ($value === false && isset($_ENV[$key])) {
            $value = $_ENV[$key];
        }

        if ($value === false && isset($_SERVER[$key])) {
            $value = $_SERVER[$key];
        }

        if ($value === false || $value === null || $value === '') {
            return $default;
        }

        return $value;
    }
}

if (!function_exists('app_is_https')) {
    function app_is_https() {
        if (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
            return true;
        }

        if (!empty($_SERVER['REQUEST_SCHEME']) && strtolower((string) $_SERVER['REQUEST_SCHEME']) === 'https') {
            return true;
        }

        if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') {
            return true;
        }

        if (!empty($_SERVER['SERVER_PORT']) && (string) $_SERVER['SERVER_PORT'] === '443') {
            return true;
        }

        if (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && strtolower((string) $_SERVER['HTTP_X_FORWARDED_SSL']) === 'on') {
            return true;
        }

        if (!empty($_SERVER['HTTP_FRONT_END_HTTPS']) && strtolower((string) $_SERVER['HTTP_FRONT_END_HTTPS']) !== 'off') {
            return true;
        }

        if (!empty($_SERVER['HTTP_CF_VISITOR']) && stripos((string) $_SERVER['HTTP_CF_VISITOR'], 'https') !== false) {
            return true;
        }

        return false;
    }
}

if (!function_exists('app_base_path')) {
    function app_base_path() {
        if (defined('APP_BASE_PATH')) {
            return APP_BASE_PATH;
        }

        $scriptName = (string) ($_SERVER['SCRIPT_NAME'] ?? '');
        $basePath = '';

        foreach (['/views/', '/api/', '/includes/', '/database/', '/config/', '/uploads/'] as $marker) {
            $markerPos = strpos($scriptName, $marker);
            if ($markerPos !== false) {
                $basePath = substr($scriptName, 0, $markerPos);
                break;
            }
        }

        if ($basePath === '') {
            $dir = str_replace('\\', '/', dirname($scriptName));
            $basePath = ($dir === '/' || $dir === '.') ? '' : rtrim($dir, '/');
        }

        define('APP_BASE_PATH', $basePath);
        return APP_BASE_PATH;
    }
}

if (!function_exists('app_url')) {
    function app_url($path = '') {
        $configuredBaseUrl = rtrim((string) app_env('APP_URL', ''), '/');

        if ($configuredBaseUrl !== '') {
            $base = $configuredBaseUrl;
        } else {
            $scheme = app_is_https() ? 'https' : 'http';
            $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
            $basePath = app_base_path();
            $base = rtrim($scheme . '://' . $host . $basePath, '/');
        }

        $path = ltrim((string) $path, '/');

        return $path === '' ? $base : ($base . '/' . $path);
    }
}

if (!function_exists('app_path')) {
    function app_path($path = '') {
        $basePath = app_base_path();
        $path = ltrim((string) $path, '/');

        if ($path === '') {
            return $basePath === '' ? '/' : $basePath;
        }

        $fullPath = ($basePath === '' ? '' : $basePath) . '/' . $path;
        return preg_replace('#/+#', '/', $fullPath);
    }
}

if (!function_exists('api_url')) {
    function api_url($endpoint = '') {
        return app_url('api/' . ltrim((string) $endpoint, '/'));
    }
}

if (!function_exists('api_url_candidates')) {
    function api_url_candidates($primaryEndpoint, $alternativeEndpoints = []) {
        $endpoints = array_merge([(string) $primaryEndpoint], (array) $alternativeEndpoints);
        $candidates = [];

        foreach ($endpoints as $endpoint) {
            $url = api_url($endpoint);
            $candidates[] = $url;

            if (stripos($url, 'http://') === 0) {
                $candidates[] = 'https://' . substr($url, 7);
            } elseif (stripos($url, 'https://') === 0) {
                $candidates[] = 'http://' . substr($url, 8);
            }
        }

        return array_values(array_unique($candidates));
    }
}
