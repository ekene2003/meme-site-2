<?php
/**
 * EthVerify.php — Verify Ethereum personal_sign signatures
 *
 * Dependencies: kornrunner/keccak (for Keccak-256 hash)
 * NO ext-gmp required — uses bcmath (enabled by default on most PHP installs)
 *
 * Install: composer require kornrunner/keccak
 */

namespace MyApp\Auth;

use Kornrunner\Keccak;

class EthVerify
{
    // secp256k1 curve parameters
    private const P  = '115792089237316195423570985008687907853269984665640564039457584007908834671663';
    private const A  = '0';
    private const B  = '7';
    private const GX = '55066263022277343669578718895168534326250603453777594175500187360389116729240';
    private const GY = '32670510020758816978083085130507043184471273380659243275938904335757337482424';
    private const N  = '115792089237316195423570985008687907852837564279074904382605163141518161494337';

    /**
     * Recover the Ethereum address that signed a personal_sign message.
     *
     * @param string $message   The original plaintext message
     * @param string $signature Hex signature with 0x prefix (130 chars)
     * @return string           Lowercase 0x-prefixed Ethereum address
     */
    public static function personalRecover(string $message, string $signature): string
    {
        // Build the Ethereum "personal_sign" prefixed message
        $prefixed = "\x19Ethereum Signed Message:\n" . strlen($message) . $message;
        $msgHash  = Keccak::hash($prefixed, 256);

        return self::ecRecover($msgHash, $signature);
    }

    /**
     * Recover address from a raw 32-byte message hash (hex, no 0x prefix).
     */
    public static function ecRecover(string $msgHashHex, string $signature): string
    {
        $sig = ltrim($signature, '0x');
        if (strlen($sig) !== 130) {
            throw new \InvalidArgumentException('Signature must be 65 bytes (130 hex chars)');
        }

        $r = self::hexToBc(substr($sig, 0, 64));
        $s = self::hexToBc(substr($sig, 64, 64));
        $v = hexdec(substr($sig, 128, 2));

        // Normalise v: MetaMask uses 27/28; some wallets use 0/1
        if ($v >= 27) $v -= 27;
        if ($v !== 0 && $v !== 1) {
            throw new \InvalidArgumentException('Invalid v value in signature');
        }

        $z = self::hexToBc($msgHashHex);

        // Recover public key from (r, s, v, z)
        $pubKey = self::recoverPublicKey($r, $s, $v, $z);

        // Derive address: keccak256(pubkey)[12..31]
        $pubKeyHex = str_pad(self::bcToHex($pubKey['x']), 64, '0', STR_PAD_LEFT)
                   . str_pad(self::bcToHex($pubKey['y']), 64, '0', STR_PAD_LEFT);

        $addrHash = Keccak::hash(hex2bin($pubKeyHex), 256);
        return '0x' . substr($addrHash, 24); // last 20 bytes = 40 hex chars
    }

    // -----------------------------------------------------------------------
    // secp256k1 point arithmetic (bcmath, no gmp)
    // -----------------------------------------------------------------------

    private static function recoverPublicKey(string $r, string $s, int $recid, string $z): array
    {
        $p  = self::P;
        $n  = self::N;

        // Candidate x = r (we only support even x — recid bit 1 = compressed alt x)
        $x = $r;

        // y² = x³ + 7 mod p
        $y2 = bcmod(bcadd(bcpow($x, '3', 0), self::B), $p);
        $y  = self::modSqrt($y2, $p);

        // Choose correct y parity
        $yParity = bcmod($y, '2');
        if ((int)$yParity !== $recid) {
            $y = bcsub($p, $y);
        }

        // R = (x, y)
        $R = ['x' => $x, 'y' => $y];

        // Q = r⁻¹ * (s*R - z*G)
        $rInv = self::modInverse($r, $n);
        $u1   = bcmod(bcmul(self::negMod($z, $n), $rInv), $n);
        $u2   = bcmod(bcmul($s, $rInv), $n);

        $G  = ['x' => self::GX, 'y' => self::GY];
        $Q  = self::pointAdd(self::scalarMul($G, $u1), self::scalarMul($R, $u2));

        return $Q;
    }

    private static function negMod(string $a, string $m): string
    {
        return bcmod(bcsub($m, bcmod($a, $m)), $m);
    }

    private static function modSqrt(string $a, string $p): string
    {
        // p ≡ 3 mod 4 — Tonelli–Shanks shortcut
        $exp = bcdiv(bcadd($p, '1'), '4', 0);
        return self::modPow($a, $exp, $p);
    }

    private static function modPow(string $base, string $exp, string $mod): string
    {
        $result = '1';
        $base   = bcmod($base, $mod);
        while (bccomp($exp, '0') > 0) {
            if (bcmod($exp, '2') === '1') {
                $result = bcmod(bcmul($result, $base), $mod);
            }
            $exp  = bcdiv($exp, '2', 0);
            $base = bcmod(bcmul($base, $base), $mod);
        }
        return $result;
    }

    private static function modInverse(string $a, string $m): string
    {
        // Extended Euclidean
        [$g, $x] = self::extGcd($a, $m);
        if ($g !== '1') throw new \RuntimeException('No modular inverse');
        return bcmod(bcadd(bcmod($x, $m), $m), $m);
    }

    private static function extGcd(string $a, string $b): array
    {
        if ($b === '0') return [$a, '1', '0'];
        [$g, $x1, $y1] = self::extGcd($b, bcmod($a, $b));
        return [$g, $y1, bcsub($x1, bcmul(bcdiv($a, $b, 0), $y1))];
    }

    private static function pointDouble(array $P): array
    {
        $p  = self::P;
        $lam = bcmod(
            bcmul(
                bcmul('3', bcmod(bcmul($P['x'], $P['x']), $p)),
                self::modInverse(bcmod(bcmul('2', $P['y']), $p), $p)
            ),
            $p
        );
        $x3 = bcmod(bcsub(bcsub(bcmul($lam, $lam), $P['x']), $P['x']), $p);
        $y3 = bcmod(bcsub(bcmul($lam, bcsub($P['x'], $x3)), $P['y']), $p);
        return [
            'x' => ($x3[0] === '-') ? bcadd($x3, $p) : $x3,
            'y' => ($y3[0] === '-') ? bcadd($y3, $p) : $y3,
        ];
    }

    private static function pointAdd(array $P, array $Q): array
    {
        if ($P === $Q) return self::pointDouble($P);
        $p   = self::P;
        $lam = bcmod(
            bcmul(
                bcsub($Q['y'], $P['y']),
                self::modInverse(bcmod(bcsub($Q['x'], $P['x']), $p), $p)
            ),
            $p
        );
        $x3 = bcmod(bcsub(bcsub(bcmul($lam, $lam), $P['x']), $Q['x']), $p);
        $y3 = bcmod(bcsub(bcmul($lam, bcsub($P['x'], $x3)), $P['y']), $p);
        return [
            'x' => ($x3[0] === '-') ? bcadd($x3, $p) : $x3,
            'y' => ($y3[0] === '-') ? bcadd($y3, $p) : $y3,
        ];
    }

    private static function scalarMul(array $P, string $k): array
    {
        $k = bcmod($k, self::N);
        $R = null;
        $Q = $P;
        while (bccomp($k, '0') > 0) {
            if (bcmod($k, '2') === '1') {
                $R = ($R === null) ? $Q : self::pointAdd($R, $Q);
            }
            $Q = self::pointDouble($Q);
            $k = bcdiv($k, '2', 0);
        }
        return $R;
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private static function hexToBc(string $hex): string
    {
        $hex = ltrim($hex, '0') ?: '0';
        $dec = '0';
        $len = strlen($hex);
        for ($i = 0; $i < $len; $i++) {
            $dec = bcadd(bcmul($dec, '16'), (string)hexdec($hex[$i]));
        }
        return $dec;
    }

    private static function bcToHex(string $dec): string
    {
        if ($dec === '0') return '0';
        $hex = '';
        while (bccomp($dec, '0') > 0) {
            $rem = (int)bcmod($dec, '16');
            $hex = dechex($rem) . $hex;
            $dec = bcdiv($dec, '16', 0);
        }
        return $hex;
    }
}
