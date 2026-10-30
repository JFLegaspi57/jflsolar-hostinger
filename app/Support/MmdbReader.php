<?php

namespace App\Support;

use RuntimeException;

/**
 * Minimal reader for MaxMind DB (.mmdb) files, used for the DB-IP "IP to City Lite" database.
 * Reads from disk with seeks, so the ~130 MB file is never loaded into memory.
 * Format: https://maxmind.github.io/MaxMind-DB/
 */
final class MmdbReader
{
    private const META_MARKER = "\xAB\xCD\xEFMaxMind.com";

    /** @var resource */
    private $fh;
    private int $nodeCount;
    private int $recordSize;
    private int $nodeBytes;
    private int $treeSize;
    private int $dataStart;
    private int $ipVersion;
    private ?int $ipv4Start = null;
    public array $metadata;

    public function __construct(string $path)
    {
        $fh = @fopen($path, 'rb');
        if (! $fh) {
            throw new RuntimeException('Cannot open location database.');
        }
        $this->fh = $fh;
        $size = fstat($fh)['size'];
        $tail = min($size, 128 * 1024);
        fseek($fh, $size - $tail);
        $buf = fread($fh, $tail);
        $pos = strrpos($buf, self::META_MARKER);
        if ($pos === false) {
            throw new RuntimeException('Not a MaxMind DB file.');
        }
        $metaStart = $size - $tail + $pos + strlen(self::META_MARKER);
        [$meta] = $this->decode($metaStart, $metaStart);
        $this->metadata = $meta;
        $this->nodeCount = (int) $meta['node_count'];
        $this->recordSize = (int) $meta['record_size'];
        $this->ipVersion = (int) $meta['ip_version'];
        if (! in_array($this->recordSize, [24, 28, 32], true)) {
            throw new RuntimeException('Unsupported record size.');
        }
        $this->nodeBytes = $this->recordSize * 2 / 8;
        $this->treeSize = $this->nodeBytes * $this->nodeCount;
        $this->dataStart = $this->treeSize + 16;
    }

    public function __destruct()
    {
        if (is_resource($this->fh)) {
            fclose($this->fh);
        }
    }

    /** Record for an IP address, or null if the database has none. */
    public function get(string $ip): ?array
    {
        $packed = @inet_pton($ip);
        if ($packed === false) {
            return null;
        }
        $bits = strlen($packed) * 8;
        $node = 0;
        if ($bits === 32 && $this->ipVersion === 6) {
            $node = $this->ipv4StartNode();
        }
        for ($i = 0; $i < $bits && $node < $this->nodeCount; $i++) {
            $bit = (ord($packed[$i >> 3]) >> (7 - ($i & 7))) & 1;
            $node = $this->readNode($node, $bit);
        }
        if ($node <= $this->nodeCount) {
            return null;   // == nodeCount means "no data"
        }
        $offset = $node - $this->nodeCount - 16;
        [$value] = $this->decode($this->dataStart + $offset, $this->dataStart);

        return is_array($value) ? $value : null;
    }

    private function ipv4StartNode(): int
    {
        if ($this->ipv4Start === null) {
            $node = 0;
            for ($i = 0; $i < 96 && $node < $this->nodeCount; $i++) {
                $node = $this->readNode($node, 0);
            }
            $this->ipv4Start = $node;
        }

        return $this->ipv4Start;
    }

    private function readNode(int $node, int $index): int
    {
        fseek($this->fh, $node * $this->nodeBytes);
        $b = fread($this->fh, (int) $this->nodeBytes);
        switch ($this->recordSize) {
            case 24:
                $s = substr($b, $index * 3, 3);

                return (ord($s[0]) << 16) | (ord($s[1]) << 8) | ord($s[2]);
            case 28:
                if ($index === 0) {
                    return ((ord($b[3]) & 0xF0) << 20) | (ord($b[0]) << 16) | (ord($b[1]) << 8) | ord($b[2]);
                }

                return ((ord($b[3]) & 0x0F) << 24) | (ord($b[4]) << 16) | (ord($b[5]) << 8) | ord($b[6]);
            default:
                return unpack('N', substr($b, $index * 4, 4))[1];
        }
    }

    private function read(int $offset, int $length): string
    {
        if ($length === 0) {
            return '';
        }
        fseek($this->fh, $offset);

        return (string) fread($this->fh, $length);
    }

    /** @return array{0: mixed, 1: int} value and the offset just after it */
    private function decode(int $offset, int $base): array
    {
        $ctrl = ord($this->read($offset, 1));
        $offset++;
        $type = $ctrl >> 5;

        if ($type === 1) {   // pointer
            $ss = ($ctrl >> 3) & 3;
            $v = $ctrl & 7;
            $b = $this->read($offset, $ss + 1);
            $p = match ($ss) {
                0 => ($v << 8) | ord($b[0]),
                1 => (($v << 16) | (ord($b[0]) << 8) | ord($b[1])) + 2048,
                2 => (($v << 24) | (ord($b[0]) << 16) | (ord($b[1]) << 8) | ord($b[2])) + 526336,
                default => unpack('N', $b)[1],
            };
            [$value] = $this->decode($base + $p, $base);

            return [$value, $offset + $ss + 1];
        }
        if ($type === 0) {   // extended type
            $type = 7 + ord($this->read($offset, 1));
            $offset++;
        }
        $size = $ctrl & 0x1F;
        if ($size >= 29) {
            $n = $size - 28;
            $b = $this->read($offset, $n);
            $offset += $n;
            $size = match ($n) {
                1 => 29 + ord($b[0]),
                2 => 285 + ((ord($b[0]) << 8) | ord($b[1])),
                default => 65821 + ((ord($b[0]) << 16) | (ord($b[1]) << 8) | ord($b[2])),
            };
        }

        switch ($type) {
            case 2:  // UTF-8 string
            case 4:  // bytes
                return [$this->read($offset, $size), $offset + $size];
            case 3:  // double
                return [unpack('E', $this->read($offset, 8))[1], $offset + 8];
            case 15: // float
                return [unpack('G', $this->read($offset, 4))[1], $offset + 4];
            case 5:  // uint16
            case 6:  // uint32
            case 8:  // int32
            case 9:  // uint64
            case 10: // uint128
                $n = 0;
                foreach (str_split($this->read($offset, $size)) as $ch) {
                    if ($size > 0) {
                        $n = ($n << 8) | ord($ch);
                    }
                }
                if ($type === 8 && $size === 4 && $n >= 0x80000000) {
                    $n -= 0x100000000;
                }

                return [$n, $offset + $size];
            case 7:  // map
                $map = [];
                for ($i = 0; $i < $size; $i++) {
                    [$k, $offset] = $this->decode($offset, $base);
                    [$v, $offset] = $this->decode($offset, $base);
                    $map[(string) $k] = $v;
                }

                return [$map, $offset];
            case 11: // array
                $arr = [];
                for ($i = 0; $i < $size; $i++) {
                    [$arr[], $offset] = $this->decode($offset, $base);
                }

                return [$arr, $offset];
            case 14: // boolean
                return [$size !== 0, $offset];
            default: // data cache container / end marker: nothing to return
                return [null, $offset];
        }
    }
}
