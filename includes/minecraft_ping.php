<?php
/**
 * CraftRadar — Minecraft Server List Ping
 * 
 * Реализация протокола SLP (Server List Ping) для Minecraft Java Edition.
 * Отправляет Handshake + Status Request, получает JSON с данными сервера.
 */

class MinecraftPing
{
    private string $host;
    private int $port;
    private int $timeout;
    private $socket = null;

    public function __construct(string $host, int $port = 25565, int $timeout = 5)
    {
        $this->host = $host;
        $this->port = $port;
        $this->timeout = $timeout;
    }

    /**
     * Выполнить пинг сервера
     * @return array|false Данные сервера или false при ошибке
     */
    public function ping(): array|false
    {
        $startTime = microtime(true);

        try {
            $this->connect();
            $this->sendHandshake();
            $this->sendStatusRequest();
            $response = $this->readStatusResponse();
            $this->disconnect();

            $pingMs = (int)round((microtime(true) - $startTime) * 1000);

            if (!$response) {
                return false;
            }

            $data = json_decode($response, true);
            if (!$data) {
                return false;
            }

            return [
                'online'      => true,
                'players'     => $data['players']['online'] ?? 0,
                'max_players' => $data['players']['max'] ?? 0,
                'version'     => $data['version']['name'] ?? 'Unknown',
                'protocol'    => $data['version']['protocol'] ?? 0,
                'motd'        => $this->parseMotd($data['description'] ?? ''),
                'favicon'     => $data['favicon'] ?? null,
                'ping_ms'     => $pingMs,
            ];
        } catch (\Exception $e) {
            $this->disconnect();
            return false;
        }
    }

    /**
     * Подключение к серверу
     */
    private function connect(): void
    {
        $this->socket = @fsockopen($this->host, $this->port, $errno, $errstr, $this->timeout);

        if (!$this->socket) {
            throw new \Exception("Connection failed: {$errstr} ({$errno})");
        }

        stream_set_timeout($this->socket, $this->timeout);
    }

    /**
     * Отключение
     */
    private function disconnect(): void
    {
        if ($this->socket) {
            @fclose($this->socket);
            $this->socket = null;
        }
    }

    /**
     * Отправка Handshake пакета
     */
    private function sendHandshake(): void
    {
        $data = '';
        $data .= $this->packVarInt(0x00);           // Packet ID
        $data .= $this->packVarInt(-1);              // Protocol version (-1 = любая)
        $data .= $this->packString($this->host);     // Server address
        $data .= pack('n', $this->port);             // Server port (unsigned short, big-endian)
        $data .= $this->packVarInt(1);               // Next state (1 = status)

        $this->sendPacket($data);
    }

    /**
     * Отправка Status Request
     */
    private function sendStatusRequest(): void
    {
        $data = $this->packVarInt(0x00); // Packet ID
        $this->sendPacket($data);
    }

    /**
     * Чтение ответа Status Response
     */
    private function readStatusResponse(): string|false
    {
        // Читаем длину пакета
        $length = $this->readVarInt();
        if ($length < 1) return false;

        // Читаем Packet ID
        $packetId = $this->readVarInt();
        if ($packetId !== 0x00) return false;

        // Читаем длину JSON строки
        $jsonLength = $this->readVarInt();
        if ($jsonLength < 1) return false;

        // Читаем JSON
        $json = '';
        $remaining = $jsonLength;
        while ($remaining > 0) {
            $chunk = @fread($this->socket, $remaining);
            if ($chunk === false || $chunk === '') {
                return false;
            }
            $json .= $chunk;
            $remaining -= strlen($chunk);
        }

        return $json;
    }

    /**
     * Отправка пакета (с префиксом длины)
     */
    private function sendPacket(string $data): void
    {
        $packet = $this->packVarInt(strlen($data)) . $data;
        @fwrite($this->socket, $packet);
    }

    /**
     * Упаковка VarInt
     */
    private function packVarInt(int $value): string
    {
        $result = '';
        // Обработка отрицательных чисел
        if ($value < 0) {
            $value = $value & 0xFFFFFFFF;
        }
        do {
            $byte = $value & 0x7F;
            $value >>= 7;
            if ($value !== 0) {
                $byte |= 0x80;
            }
            $result .= chr($byte);
        } while ($value !== 0);

        return $result;
    }

    /**
     * Чтение VarInt из сокета
     */
    private function readVarInt(): int
    {
        $result = 0;
        $shift = 0;

        for ($i = 0; $i < 5; $i++) {
            $byte = @fread($this->socket, 1);
            if ($byte === false || $byte === '') {
                return -1;
            }
            $byte = ord($byte);
            $result |= ($byte & 0x7F) << $shift;
            $shift += 7;

            if (($byte & 0x80) === 0) {
                break;
            }
        }

        return $result;
    }

    /**
     * Упаковка строки (VarInt длина + UTF-8 строка)
     */
    private function packString(string $str): string
    {
        return $this->packVarInt(strlen($str)) . $str;
    }

    /**
     * Парсинг MOTD (может быть строкой или объектом с компонентами)
     */
    private function parseMotd(mixed $description): string
    {
        if (is_string($description)) {
            return $this->stripMinecraftColors($description);
        }

        if (is_array($description)) {
            $text = $description['text'] ?? '';

            if (isset($description['extra']) && is_array($description['extra'])) {
                foreach ($description['extra'] as $extra) {
                    if (is_string($extra)) {
                        $text .= $extra;
                    } elseif (is_array($extra)) {
                        $text .= $extra['text'] ?? '';
                    }
                }
            }

            return $this->stripMinecraftColors($text);
        }

        return '';
    }

    /**
     * Удаление цветовых кодов Minecraft (§x)
     */
    private function stripMinecraftColors(string $text): string
    {
        return preg_replace('/§[0-9a-fk-or]/i', '', $text);
    }
}

/**
 * Быстрая функция пинга сервера
 * Сначала пробует прямой TCP (SLP), при неудаче — внешний API mcsrvstat.us
 */
function pingMinecraftServer(string $host, int $port = 25565, int $timeout = 5): array|false
{
    // Попытка 1: прямой TCP пинг (работает если порт открыт)
    $ping = new MinecraftPing($host, $port, $timeout);
    $result = $ping->ping();
    if ($result) return $result;

    // Попытка 2: через внешний API (обходит блокировку портов на shared-хостингах)
    return pingViaApi($host, $port);
}

/**
 * Пинг через внешний API mcsrvstat.us (fallback)
 */
function pingViaApi(string $host, int $port): array|false
{
    if (!function_exists('curl_init')) return false;

    $url = "https://api.mcsrvstat.us/2/{$host}:{$port}";

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_USERAGENT      => 'CraftRadar/1.0',
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200 || !$response) return false;

    $data = json_decode($response, true);
    if (!$data || empty($data['online'])) return false;

    // Парсим MOTD
    $motd = '';
    if (!empty($data['motd']['clean'])) {
        $motd = implode(' ', $data['motd']['clean']);
    }

    return [
        'online'      => true,
        'players'     => (int)($data['players']['online'] ?? 0),
        'max_players' => (int)($data['players']['max'] ?? 0),
        'version'     => $data['version'] ?? 'Unknown',
        'protocol'    => (int)($data['protocol']['version'] ?? 0),
        'motd'        => $motd,
        'favicon'     => !empty($data['icon']) ? $data['icon'] : null,
        'ping_ms'     => 0, // API не возвращает пинг
    ];
}
