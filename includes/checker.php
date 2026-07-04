<?php
/**
 * Device Probing Engine (Ping, TCP Socket, HTTP/HTTPS cURL)
 */

/**
 * Performs a network check/probe on a device.
 * 
 * @param string $ip The IP address or Hostname to check.
 * @param int|null $port The port number (required for TCP, optional for others).
 * @param string $type The check type ('ping', 'tcp', 'http').
 * @return array ['status' => 'UP'|'DOWN', 'latency' => float|null]
 */
function probeDevice(string $ip, ?int $port, string $type): array {
    $type = strtolower($type);
    
    switch ($type) {
        case 'tcp':
            return probeTcp($ip, $port);
        case 'http':
            return probeHttp($ip, $port);
        case 'ping':
        default:
            return probePing($ip);
    }
}

/**
 * Probes via ICMP Ping with dynamic OS detection.
 */
function probePing(string $ip): array {
    // Whitelist check: Must be a valid IP address or domain/hostname format
    $isValidIp = filter_var($ip, FILTER_VALIDATE_IP);
    $isValidDomain = preg_match('/^[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $ip) || $ip === 'localhost';
    
    if (!$isValidIp && !$isValidDomain) {
        return [
            'status' => 'DOWN',
            'latency' => null
        ];
    }

    $escapedIp = escapeshellarg($ip);
    $isWin = stripos(PHP_OS, 'WIN') !== false;
    
    $startTime = microtime(true);
    
    if ($isWin) {
        // Windows: -n 1 (1 packet), -w 2000 (2000ms timeout)
        $cmd = "ping -n 1 -w 2000 $escapedIp";
    } else {
        // Linux/macOS: -c 1 (1 packet), -W 2 (2s timeout)
        $cmd = "ping -c 1 -W 2 $escapedIp";
    }
    
    $output = [];
    $resultCode = 1;
    exec($cmd, $output, $resultCode);
    
    $endTime = microtime(true);
    $latency = round(($endTime - $startTime) * 1000, 2);
    
    // Status is UP if command returned exit code 0
    if ($resultCode === 0) {
        $outputStr = implode("\n", $output);
        // Detect Windows fake success (Destination host unreachable / Request timed out / General failure)
        if (stripos($outputStr, 'unreachable') !== false || 
            stripos($outputStr, 'tidak dapat dijangkau') !== false || 
            stripos($outputStr, 'timed out') !== false || 
            stripos($outputStr, 'failure') !== false) {
            return [
                'status' => 'DOWN',
                'latency' => null
            ];
        }
        
        // Attempt to parse actual latency from output for better precision
        $parsedLatency = parsePingLatency($output, $isWin);
        return [
            'status' => 'UP',
            'latency' => $parsedLatency !== null ? $parsedLatency : $latency
        ];
    }
    
    return [
        'status' => 'DOWN',
        'latency' => null
    ];
}

/**
 * Helper to parse latency from Ping command output.
 */
function parsePingLatency(array $output, bool $isWin): ?float {
    $outputStr = implode("\n", $output);
    
    if ($isWin) {
        // Look for: time=12ms or Average = 12ms or Average = 12 ms
        if (preg_match('/time[=<]([0-9\.]+)ms/i', $outputStr, $matches)) {
            return (float)$matches[1];
        }
        if (preg_match('/Average\s*=\s*([0-9\.]+)ms/i', $outputStr, $matches)) {
            return (float)$matches[1];
        }
    } else {
        // Linux/macOS look for: time=12.4 ms or time=12.4ms
        if (preg_match('/time=([0-9\.]+)\s*ms/i', $outputStr, $matches)) {
            return (float)$matches[1];
        }
    }
    
    return null;
}

/**
 * Probes via TCP socket connection.
 */
function probeTcp(string $ip, int $port): array {
    $startTime = microtime(true);
    $timeout = 3.0; // 3 seconds timeout
    
    $fp = @fsockopen($ip, $port, $errno, $errstr, $timeout);
    
    $endTime = microtime(true);
    $latency = round(($endTime - $startTime) * 1000, 2);
    
    if ($fp) {
        fclose($fp);
        return [
            'status' => 'UP',
            'latency' => $latency
        ];
    }
    
    return [
        'status' => 'DOWN',
        'latency' => null
    ];
}

/**
 * Probes via HTTP/HTTPS cURL.
 */
function probeHttp(string $ip, ?int $port): array {
    // Normalize URL
    $url = $ip;
    if (!preg_match('/^https?:\/\//i', $url)) {
        if ($port === 443) {
            $url = 'https://' . $url;
        } else {
            $url = 'http://' . $url;
        }
    }
    
    if ($port) {
        $parsed = parse_url($url);
        if (!isset($parsed['port'])) {
            $host = $parsed['host'] ?? '';
            $scheme = $parsed['scheme'] ?? 'http';
            $path = $parsed['path'] ?? '';
            $query = isset($parsed['query']) ? '?' . $parsed['query'] : '';
            $url = $scheme . '://' . $host . ':' . $port . $path . $query;
        }
    }
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 2);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'UMS Uptime Monitoring Engine/1.0');
    
    $startTime = microtime(true);
    curl_exec($ch);
    $endTime = microtime(true);
    
    $latency = round(($endTime - $startTime) * 1000, 2);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_errno($ch);
    curl_close($ch);
    
    // Status is UP if no curl error and HTTP code is between 200 and 399
    if ($curlError === 0 && $httpCode >= 200 && $httpCode < 400) {
        return [
            'status' => 'UP',
            'latency' => $latency
        ];
    }
    
    return [
        'status' => 'DOWN',
        'latency' => null
    ];
}
