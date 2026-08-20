<?php
/**
 * http.php — Helper i vogël HTTP (cURL) për thirrjet drejt SAP CI.
 * Kthen [status, body, error]. Asnjë sekret i hardkoduar; token-i kalohet nga thirrësi.
 */
declare(strict_types=1);

if (!function_exists('http_send')) {
    /**
     * @param string $method GET|POST
     * @param string $url
     * @param string|null $body
     * @param array $headers  p.sh. ['Content-Type: application/xml', 'Authorization: Bearer x']
     * @param int $timeout
     * @return array{0:int,1:string,2:string}  [httpCode, responseBody, curlError]
     */
    function http_send(string $method, string $url, ?string $body, array $headers, int $timeout = 15): array
    {
        $ch = curl_init($url);
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_CONNECTTIMEOUT => min(10, $timeout),
        ];
        if ($body !== null && $method !== 'GET') {
            $opts[CURLOPT_POSTFIELDS] = $body;
        }
        curl_setopt_array($ch, $opts);
        $resp = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);
        return [$code, $resp === false ? '' : (string) $resp, $err];
    }
}
