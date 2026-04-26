<?php
/**
 * Supabase Configuration & API Helper
 * نظام ادارة الشواهد الذكي
 */

// ─── Supabase credentials ────────────────────────────────────────────────────
// Copy .env.example to .env and fill in your values, or define them here.
// NEVER commit real credentials to version control.
define('SUPABASE_URL',    getenv('SUPABASE_URL')    ?: 'https://your-project.supabase.co');
define('SUPABASE_ANON_KEY', getenv('SUPABASE_ANON_KEY') ?: 'your-anon-key');
define('SUPABASE_SERVICE_ROLE_KEY', getenv('SUPABASE_SERVICE_ROLE_KEY') ?: '');

// ─── Supabase REST API helper ─────────────────────────────────────────────────
class Supabase
{
    private string $url;
    private string $anonKey;
    private ?string $accessToken;

    public function __construct(?string $accessToken = null)
    {
        $this->url         = rtrim(SUPABASE_URL, '/');
        $this->anonKey     = SUPABASE_ANON_KEY;
        $this->accessToken = $accessToken;
    }

    // ── Auth ──────────────────────────────────────────────────────────────────

    /** Register a new user */
    public function signUp(string $email, string $password, array $metadata = []): array
    {
        $body = ['email' => $email, 'password' => $password];
        if (!empty($metadata)) {
            $body['data'] = $metadata;
        }
        return $this->request('POST', '/auth/v1/signup', $body, false);
    }

    /** Sign in an existing user */
    public function signIn(string $email, string $password): array
    {
        $body = ['email' => $email, 'password' => $password];
        return $this->request('POST', '/auth/v1/token?grant_type=password', $body, false);
    }

    /** Get current user info from a JWT */
    public function getUser(): array
    {
        return $this->request('GET', '/auth/v1/user', null, true);
    }

    // ── Database ──────────────────────────────────────────────────────────────

    /** Select rows from a table */
    public function select(string $table, string $query = '*', array $filters = []): array
    {
        $params = ['select' => $query];
        foreach ($filters as $col => $val) {
            $params[$col] = $val;
        }
        $qs = http_build_query($params);
        return $this->request('GET', "/rest/v1/{$table}?{$qs}", null, true);
    }

    /** Insert a row */
    public function insert(string $table, array $data): array
    {
        return $this->request('POST', "/rest/v1/{$table}", $data, true, [
            'Prefer: return=representation',
        ]);
    }

    /** Update rows matching a filter */
    public function update(string $table, array $data, array $filters): array
    {
        $params = [];
        foreach ($filters as $col => $val) {
            $params[$col] = $val;
        }
        $qs = http_build_query($params);
        return $this->request('PATCH', "/rest/v1/{$table}?{$qs}", $data, true, [
            'Prefer: return=representation',
        ]);
    }

    /** Delete rows matching a filter */
    public function delete(string $table, array $filters): array
    {
        $params = [];
        foreach ($filters as $col => $val) {
            $params[$col] = $val;
        }
        $qs = http_build_query($params);
        return $this->request('DELETE', "/rest/v1/{$table}?{$qs}", null, true);
    }

    // ── Storage ───────────────────────────────────────────────────────────────

    /** Upload a file to a Supabase Storage bucket */
    public function uploadFile(string $bucket, string $path, string $filePath, string $mimeType): array
    {
        $endpoint = "{$this->url}/storage/v1/object/{$bucket}/{$path}";
        $headers  = [
            "Authorization: Bearer " . ($this->accessToken ?? $this->anonKey),
            "Content-Type: {$mimeType}",
        ];

        $fileData = file_get_contents($filePath);
        if ($fileData === false) {
            return ['error' => 'Failed to read file: ' . $filePath, '_http_code' => 0];
        }
        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => $fileData,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_RETURNTRANSFER => true,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = json_decode($response, true) ?? [];
        $decoded['_http_code'] = $httpCode;
        return $decoded;
    }

    /** Get a public URL for a file */
    public function getPublicUrl(string $bucket, string $path): string
    {
        return "{$this->url}/storage/v1/object/public/{$bucket}/{$path}";
    }

    // ── HTTP helper ───────────────────────────────────────────────────────────

    private function request(
        string $method,
        string $path,
        ?array $body,
        bool $withAuth,
        array $extraHeaders = []
    ): array {
        $endpoint = $this->url . $path;
        $token    = $withAuth && $this->accessToken
            ? $this->accessToken
            : $this->anonKey;

        $headers = array_merge([
            'apikey: '       . $this->anonKey,
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
            'Accept: application/json',
        ], $extraHeaders);

        $ch = curl_init($endpoint);
        $options = [
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
        ];

        if ($body !== null) {
            $options[CURLOPT_POSTFIELDS] = json_encode($body);
        }

        curl_setopt_array($ch, $options);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['error' => $error, '_http_code' => 0];
        }

        $decoded = json_decode($response, true);
        if (!is_array($decoded)) {
            $decoded = ['_raw' => $response];
        }
        $decoded['_http_code'] = $httpCode;
        return $decoded;
    }
}

/**
 * Return a Supabase instance using the JWT stored in the session.
 */
function supabase(): Supabase
{
    $token = $_SESSION['access_token'] ?? null;
    return new Supabase($token);
}

/**
 * Require an authenticated session; redirect to login otherwise.
 */
function requireAuth(): void
{
    if (empty($_SESSION['access_token'])) {
        header('Location: /login.php');
        exit;
    }
}
