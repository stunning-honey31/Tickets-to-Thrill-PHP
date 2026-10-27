<?php
/**
 * pdf_generator.php
 *
 * A tiny, dependency-free PDF writer. There's no Composer/network
 * access available to pull in a real library (FPDF/TCPDF/mPDF), so
 * this hand-builds a valid single-page PDF using only the standard
 * Helvetica base font (no font embedding needed — it's one of the 14
 * fonts every PDF reader supports natively), basic text/line/box
 * drawing operators, and RGB fill/stroke colors. The "logo" is drawn
 * as vector shapes (circles/lines) rather than an embedded image,
 * since embedding a real image would need an actual logo file — if
 * you upload one (a JPG works best), it can be embedded directly
 * instead of the drawn mark.
 *
 * Layout: full A4 ticket — branded header, order + attendee card,
 * itemized passes table, total band, a perforated tear line, and a
 * gate-facing admission stub with a decorative order-code graphic
 * (not a scannable barcode — gate staff verify by the printed order
 * number). This intentionally mirrors the site's fairground-poster
 * palette and pill/eyebrow visual language from assets/css/style.css
 * so the download feels like part of the same product, not a plain
 * text dump.
 *
 * This intentionally does NOT support Unicode glyphs (no ₹, no
 * emoji) — PDF's built-in WinAnsiEncoding is a single-byte Latin-1
 * style encoding, so anything outside that range is transliterated
 * or stripped by pdf_safe_text() before being written. The web site
 * and the confirmation email still show ₹ normally; only the raw PDF
 * content spells out "Rs." instead, to guarantee it never renders as
 * garbled boxes on any PDF viewer.
 */

class SimplePdf
{
    /** @var array<int,string> One string per line of body text already placed. */
    private array $ops = [];
    private int $width;
    private int $height;

    // Brand palette (matches assets/css/style.css), as 0–1 RGB triples.
    public const PURPLE  = [0.482, 0.247, 0.894];
    public const INDIGO  = [0.141, 0.078, 0.329];
    public const INDIGO_LIGHT = [0.200, 0.125, 0.431];
    public const TEAL    = [0.000, 0.761, 0.796];
    public const YELLOW  = [1.000, 0.788, 0.235];
    public const CORAL   = [1.000, 0.302, 0.369];
    public const CREAM   = [1.000, 0.969, 0.910];
    public const PAPER   = [1.000, 0.992, 0.969];
    public const INK     = [0.122, 0.078, 0.208];
    public const INK_SOFT = [0.290, 0.243, 0.408];
    public const WHITE   = [1.000, 1.000, 1.000];
    public const LINE    = [0.906, 0.863, 0.769];
    public const ROW_TINT_A = [0.965, 0.953, 0.992]; // light purple
    public const ROW_TINT_B = [0.941, 0.988, 0.988]; // light teal
    public const GOLD_TINT  = [1.000, 0.941, 0.780]; // light gold

    public function __construct(int $width = 595, int $height = 842)
    {
        $this->width = $width;
        $this->height = $height;
    }

    /** Converts UTF-8 to a PDF-safe Latin-1-ish string and escapes ( ) \ */
    public static function safeText(string $text): string
    {
        $text = str_replace('₹', 'Rs. ', $text);
        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'CP1252//TRANSLIT//IGNORE', $text);
            if ($converted !== false) {
                $text = $converted;
            }
        }
        $text = preg_replace('/[^\x20-\x7E]/', '', $text) ?? $text;
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }

    // Standard Adobe AFM glyph widths (per 1000 em) for the base-14 Helvetica
    // fonts — real widths, not a flat per-character average, so right-aligned
    // columns, centered pills, and hand-placed multi-color text (like the
    // header wordmark) line up correctly even with narrow letters like "I".
    private const WIDTHS_REGULAR = [
        ' ' => 278, '!' => 278, '"' => 355, '#' => 556, '$' => 556, '%' => 889, '&' => 667, "'" => 191,
        '(' => 333, ')' => 333, '*' => 389, '+' => 584, ',' => 278, '-' => 333, '.' => 278, '/' => 278,
        '0' => 556, '1' => 556, '2' => 556, '3' => 556, '4' => 556, '5' => 556, '6' => 556, '7' => 556, '8' => 556, '9' => 556,
        ':' => 278, ';' => 278, '<' => 584, '=' => 584, '>' => 584, '?' => 556, '@' => 1015,
        'A' => 667, 'B' => 667, 'C' => 722, 'D' => 722, 'E' => 667, 'F' => 611, 'G' => 778, 'H' => 722, 'I' => 278,
        'J' => 500, 'K' => 667, 'L' => 556, 'M' => 833, 'N' => 722, 'O' => 778, 'P' => 667, 'Q' => 778, 'R' => 722,
        'S' => 667, 'T' => 611, 'U' => 722, 'V' => 667, 'W' => 944, 'X' => 667, 'Y' => 667, 'Z' => 611,
        '[' => 278, '\\' => 278, ']' => 278, '^' => 469, '_' => 556, '`' => 333,
        'a' => 556, 'b' => 556, 'c' => 500, 'd' => 556, 'e' => 556, 'f' => 278, 'g' => 556, 'h' => 556, 'i' => 222,
        'j' => 222, 'k' => 500, 'l' => 222, 'm' => 833, 'n' => 556, 'o' => 556, 'p' => 556, 'q' => 556, 'r' => 333,
        's' => 500, 't' => 278, 'u' => 556, 'v' => 500, 'w' => 722, 'x' => 500, 'y' => 500, 'z' => 500,
        '{' => 334, '|' => 260, '}' => 334, '~' => 584,
    ];
    private const WIDTHS_BOLD = [
        ' ' => 278, '!' => 333, '"' => 474, '#' => 556, '$' => 556, '%' => 889, '&' => 722, "'" => 238,
        '(' => 333, ')' => 333, '*' => 389, '+' => 584, ',' => 278, '-' => 333, '.' => 278, '/' => 278,
        '0' => 556, '1' => 556, '2' => 556, '3' => 556, '4' => 556, '5' => 556, '6' => 556, '7' => 556, '8' => 556, '9' => 556,
        ':' => 333, ';' => 333, '<' => 584, '=' => 584, '>' => 584, '?' => 611, '@' => 975,
        'A' => 722, 'B' => 722, 'C' => 722, 'D' => 722, 'E' => 667, 'F' => 611, 'G' => 778, 'H' => 722, 'I' => 278,
        'J' => 556, 'K' => 722, 'L' => 611, 'M' => 833, 'N' => 722, 'O' => 778, 'P' => 667, 'Q' => 778, 'R' => 722,
        'S' => 667, 'T' => 611, 'U' => 722, 'V' => 667, 'W' => 944, 'X' => 667, 'Y' => 667, 'Z' => 611,
        '[' => 333, '\\' => 278, ']' => 333, '^' => 584, '_' => 556, '`' => 333,
        'a' => 556, 'b' => 611, 'c' => 556, 'd' => 611, 'e' => 556, 'f' => 333, 'g' => 611, 'h' => 611, 'i' => 278,
        'j' => 278, 'k' => 556, 'l' => 278, 'm' => 889, 'n' => 611, 'o' => 611, 'p' => 611, 'q' => 611, 'r' => 389,
        's' => 556, 't' => 333, 'u' => 611, 'v' => 556, 'w' => 778, 'x' => 556, 'y' => 556, 'z' => 500,
        '{' => 389, '|' => 280, '}' => 389, '~' => 584,
    ];

    /** Real per-character Helvetica(-Bold) width — accurate enough to hand-place chained, multi-color text runs. */
    private static function estimateWidth(string $text, float $size, bool $bold): float
    {
        $table = $bold ? self::WIDTHS_BOLD : self::WIDTHS_REGULAR;
        $units = 0;
        foreach (str_split($text) as $ch) {
            $units += $table[$ch] ?? 556; // 556 is a reasonable default for any char outside the table
        }
        return $units * $size / 1000;
    }

    /** Public wrapper for estimateWidth — used to hand-place multi-color text runs. */
    public static function textWidth(string $text, float $size, bool $bold = false): float
    {
        return self::estimateWidth($text, $size, $bold);
    }

    /** Truncates with an ellipsis if $text would render wider than $maxWidth — keeps long names/emails on one line. */
    public static function truncateToWidth(string $text, float $maxWidth, float $size, bool $bold = false): string
    {
        if (self::estimateWidth($text, $size, $bold) <= $maxWidth) {
            return $text;
        }
        $ellipsisW = self::estimateWidth('...', $size, $bold);
        $budget = $maxWidth - $ellipsisW;
        $kept = '';
        foreach (str_split($text) as $ch) {
            $next = $kept . $ch;
            if (self::estimateWidth($next, $size, $bold) > $budget) {
                break;
            }
            $kept = $next;
        }
        return rtrim($kept) . '...';
    }

    public function text(float $x, float $y, string $text, float $size = 11, bool $bold = false, array $color = self::INK): void
    {
        $font = $bold ? 'F2' : 'F1';
        [$r, $g, $b] = $color;
        $this->ops[] = sprintf(
            "%.3F %.3F %.3F rg BT /%s %.2F Tf %.2F %.2F Td (%s) Tj ET",
            $r, $g, $b, $font, $size, $x, $y, self::safeText($text)
        );
    }

    /** Text horizontally centered on $cx (vertical position still $y, baseline). */
    public function centeredText(float $cx, float $y, string $text, float $size = 11, bool $bold = false, array $color = self::INK): void
    {
        $width = self::estimateWidth($text, $size, $bold);
        $this->text($cx - $width / 2, $y, $text, $size, $bold, $color);
    }

    /** Text right-aligned so it ends exactly at $rx (baseline still $y). */
    public function rightText(float $rx, float $y, string $text, float $size = 11, bool $bold = false, array $color = self::INK): void
    {
        $width = self::estimateWidth($text, $size, $bold);
        $this->text($rx - $width, $y, $text, $size, $bold, $color);
    }

    public function line(float $x1, float $y1, float $x2, float $y2, float $width = 1, array $color = self::INK): void
    {
        [$r, $g, $b] = $color;
        $this->ops[] = sprintf("%.3F %.3F %.3F RG %.2F w %.2F %.2F m %.2F %.2F l S", $r, $g, $b, $width, $x1, $y1, $x2, $y2);
    }

    /** A dashed horizontal/diagonal rule — used for the tear-off perforation and eyebrow underlines. */
    public function dashedLine(float $x1, float $y1, float $x2, float $y2, float $width, array $color, float $dash = 4, float $gap = 3): void
    {
        [$r, $g, $b] = $color;
        $this->ops[] = sprintf(
            "%.3F %.3F %.3F RG %.2F w [%.1F %.1F] 0 d %.2F %.2F m %.2F %.2F l S [] 0 d",
            $r, $g, $b, $width, $dash, $gap, $x1, $y1, $x2, $y2
        );
    }

    public function rect(float $x, float $y, float $w, float $h, float $lineWidth = 1, array $color = self::INK): void
    {
        [$r, $g, $b] = $color;
        $this->ops[] = sprintf("%.3F %.3F %.3F RG %.2F w %.2F %.2F %.2F %.2F re S", $r, $g, $b, $lineWidth, $x, $y, $w, $h);
    }

    public function filledRect(float $x, float $y, float $w, float $h, array $color): void
    {
        [$r, $g, $b] = $color;
        $this->ops[] = sprintf("%.3F %.3F %.3F rg %.2F %.2F %.2F %.2F re f", $r, $g, $b, $x, $y, $w, $h);
    }

    /** Filled or stroked circle, built from 4 cubic Bezier curves. */
    public function circle(float $cx, float $cy, float $r, array $color, string $mode = 'fill', float $lineWidth = 2): void
    {
        $k = 0.5522847498 * $r;
        [$cr, $cg, $cb] = $color;
        $parts = [];
        $parts[] = $mode === 'stroke'
            ? sprintf('%.3F %.3F %.3F RG %.2F w', $cr, $cg, $cb, $lineWidth)
            : sprintf('%.3F %.3F %.3F rg', $cr, $cg, $cb);
        $parts[] = sprintf('%.2F %.2F m', $cx + $r, $cy);
        $parts[] = sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c', $cx + $r, $cy + $k, $cx + $k, $cy + $r, $cx, $cy + $r);
        $parts[] = sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c', $cx - $k, $cy + $r, $cx - $r, $cy + $k, $cx - $r, $cy);
        $parts[] = sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c', $cx - $r, $cy - $k, $cx - $k, $cy - $r, $cx, $cy - $r);
        $parts[] = sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c', $cx + $k, $cy - $r, $cx + $r, $cy - $k, $cx + $r, $cy);
        $parts[] = $mode === 'stroke' ? 'S' : 'f';
        $this->ops[] = implode(' ', $parts);
    }

    /** A filled "pill" (stadium shape): two half-round ends plus a rectangle, all one color. */
    public function pill(float $x, float $y, float $w, float $h, array $color): void
    {
        $r = $h / 2;
        $this->circle($x + $r, $y + $r, $r, $color, 'fill');
        $this->circle($x + $w - $r, $y + $r, $r, $color, 'fill');
        if ($w > $h) {
            $this->filledRect($x + $r, $y, $w - 2 * $r, $h, $color);
        }
    }

    /** Pill with centered label — the site's .btn / badge look. */
    public function pillLabel(float $x, float $y, float $w, float $h, string $label, array $bg, array $fg, float $size = 10, bool $bold = true): void
    {
        $this->pill($x, $y, $w, $h, $bg);
        $this->centeredText($x + $w / 2, $y + $h / 2 - $size * 0.34, $label, $size, $bold, $fg);
    }

    /**
     * Decorative order-code graphic: a row of vertical bars whose widths
     * are derived deterministically from the order id, so every order
     * gets a distinct-looking but reproducible pattern. This is a visual
     * motif, not a scannable barcode — gate staff look the order up by
     * the printed number underneath it.
     */
    public function codeBars(float $x, float $y, float $w, float $h, int $seed, array $color): void
    {
        $rng = $seed <= 0 ? 1 : $seed;
        $cursor = $x;
        $endX = $x + $w;
        while ($cursor < $endX - 1) {
            $rng = ($rng * 1103515245 + 12345) & 0x7FFFFFFF;
            $bar = 1.4 + ($rng % 100) / 100 * 2.6; // bar width 1.4–4.0
            $rng = ($rng * 1103515245 + 12345) & 0x7FFFFFFF;
            $skip = ($rng % 100) < 30; // occasional gap for a barcode-like rhythm
            if (!$skip) {
                $this->filledRect($cursor, $y, min($bar, $endX - $cursor), $h, $color);
            }
            $cursor += $bar + 1.6;
        }
    }

    /**
     * Draws the same roller-coaster mark used on the website's header/favicon
     * (assets/img/logo-mark.svg): a badge with a white hill/loop/hill track,
     * a coral cart, and a yellow spark. SVG coordinates (that icon's 64x64
     * viewBox, centered on 32,32) are mapped into PDF space around ($cx,$cy)
     * with radius $r, flipping the y-axis since PDF's origin is bottom-left.
     */
    public function coasterIcon(float $cx, float $cy, float $r = 22): void
    {
        $scale = ($r * 2) / 62;
        $map = function (float $x, float $y) use ($cx, $cy, $scale): array {
            return [$cx + ($x - 32) * $scale, $cy - ($y - 32) * $scale];
        };
        $rotate = function (float $x, float $y, float $angleDeg): array {
            $rad = deg2rad($angleDeg);
            return [$x * cos($rad) - $y * sin($rad), $x * sin($rad) + $y * cos($rad)];
        };

        // Badge background (a solid blend standing in for the site's indigo->purple gradient).
        $this->circle($cx, $cy, $r, [0.184, 0.106, 0.373], 'fill');

        // Track: hill / loop / hill, as one stroked bezier path (mirrors the SVG "C" commands 1:1).
        [$x0, $y0] = $map(8, 38);
        [$c1x, $c1y] = $map(13, 21); [$c2x, $c2y] = $map(20, 21); [$e1x, $e1y] = $map(24, 32);
        [$c3x, $c3y] = $map(27, 40); [$c4x, $c4y] = $map(30, 40); [$e2x, $e2y] = $map(33, 32);
        [$c5x, $c5y] = $map(37, 21); [$c6x, $c6y] = $map(44, 21); [$e3x, $e3y] = $map(49, 30);
        [$c7x, $c7y] = $map(52, 35); [$c8x, $c8y] = $map(54, 37); [$e4x, $e4y] = $map(57, 38);
        $strokeW = max(1.3, 2.6 * $scale);
        $this->ops[] = sprintf(
            "1.000 1.000 1.000 RG %.2F w %.2F %.2F m %.2F %.2F %.2F %.2F %.2F %.2F c %.2F %.2F %.2F %.2F %.2F %.2F c %.2F %.2F %.2F %.2F %.2F %.2F c %.2F %.2F %.2F %.2F %.2F %.2F c S",
            $strokeW,
            $x0, $y0,
            $c1x, $c1y, $c2x, $c2y, $e1x, $e1y,
            $c3x, $c3y, $c4x, $c4y, $e2x, $e2y,
            $c5x, $c5y, $c6x, $c6y, $e3x, $e3y,
            $c7x, $c7y, $c8x, $c8y, $e4x, $e4y
        );

        // Loop-the-loop.
        [$lx, $ly] = $map(24.5, 31.5);
        $this->circle($lx, $ly, 6.4 * $scale, self::WHITE, 'stroke', max(1.1, 2.1 * $scale));

        // Cart: a small rotated rectangle, rotation done in PHP so it can be filled as a plain polygon.
        $cartCorners = [[-5, -3.6], [5, -3.6], [5, 3.6], [-5, 3.6]];
        $polyOps = [];
        foreach ($cartCorners as $i => [$lx2, $ly2]) {
            [$rx, $ry] = $rotate($lx2, $ly2, -24);
            [$px, $py] = $map(38 + $rx, 23 + $ry);
            $polyOps[] = sprintf('%.2F %.2F %s', $px, $py, $i === 0 ? 'm' : 'l');
        }
        [$cr, $cg, $cb] = self::CORAL;
        $this->ops[] = sprintf('%.3F %.3F %.3F rg ', $cr, $cg, $cb) . implode(' ', $polyOps) . ' h f';

        // Wheels.
        foreach ([[-2.8, 4], [2.8, 4]] as [$wx, $wy]) {
            [$rx, $ry] = $rotate($wx, $wy, -24);
            [$px, $py] = $map(38 + $rx, 23 + $ry);
            $this->circle($px, $py, max(0.9, 1.5 * $scale), self::INK, 'fill');
        }

        // Spark (4-point star), translated only — no rotation in the source mark.
        $starLocal = [[0, -6.5], [1.7, -1.7], [6.5, 0], [1.7, 1.7], [0, 6.5], [-1.7, 1.7], [-6.5, 0], [-1.7, -1.7]];
        $starOps = [];
        foreach ($starLocal as $i => [$sx, $sy]) {
            [$px, $py] = $map(50 + $sx, 14 + $sy);
            $starOps[] = sprintf('%.2F %.2F %s', $px, $py, $i === 0 ? 'm' : 'l');
        }
        [$yr, $yg, $yb] = self::YELLOW;
        $this->ops[] = sprintf('%.3F %.3F %.3F rg ', $yr, $yg, $yb) . implode(' ', $starOps) . ' h f';
    }

    /** A small square "eyebrow" marker + uppercase label, echoing the site's section headers. */
    public function eyebrow(float $x, float $y, string $label, array $accent, float $size = 10): void
    {
        $this->filledRect($x, $y - 1, 7, 7, $accent);
        $this->text($x + 13, $y, $label, $size, true, self::INK_SOFT);
    }

    /** Builds and returns the raw PDF file bytes as a string. */
    public function output(): string
    {
        $content = implode("\n", $this->ops);

        $objects = [];
        $objects[1] = "<< /Type /Catalog /Pages 2 0 R >>";
        $objects[2] = "<< /Type /Pages /Kids [3 0 R] /Count 1 >>";
        $objects[3] = sprintf(
            "<< /Type /Page /Parent 2 0 R /Resources << /Font << /F1 4 0 R /F2 5 0 R >> >> " .
            "/MediaBox [0 0 %d %d] /Contents 6 0 R >>",
            $this->width,
            $this->height
        );
        $objects[4] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>";
        $objects[5] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>";
        $objects[6] = sprintf("<< /Length %d >>\nstream\n%s\nendstream", strlen($content), $content);

        $pdf = "%PDF-1.4\n";
        $offsets = [];

        foreach ($objects as $num => $body) {
            $offsets[$num] = strlen($pdf);
            $pdf .= "{$num} 0 obj\n{$body}\nendobj\n";
        }

        $xrefStart = strlen($pdf);
        $count = count($objects) + 1;

        $pdf .= "xref\n0 {$count}\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i < $count; $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }

        $pdf .= "trailer\n<< /Size {$count} /Root 1 0 R >>\n";
        $pdf .= "startxref\n{$xrefStart}\n%%EOF";

        return $pdf;
    }
}

/**
 * Builds a single-page, full-color ticket PDF for one order:
 *   1. Branded header band with logo mark and a PAID / order-number pill
 *   2. An order + attendee details card
 *   3. An itemized, zebra-striped passes table with a gold total band
 *   4. A perforated tear line
 *   5. A gate-facing admission stub with a decorative order-code graphic
 *   6. A small-print footer
 * Returns the raw PDF bytes (same output used both for download and as
 * the email attachment, so the two are always identical).
 */
function build_ticket_pdf(array $order, array $items, array $user): string
{
    $pdf = new SimplePdf(595, 842);
    $pageW = 595;
    $pageH = 842;
    $left = 50;
    $right = 545;
    $contentW = $right - $left;

    // Full-page paper backdrop so the white cards read as cards, like the site body.
    $pdf->filledRect(0, 0, $pageW, $pageH, SimplePdf::PAPER);

    // ---- Header band ----
    $headerH = 104;
    $headerTop = $pageH;
    $pdf->filledRect(0, $headerTop - $headerH, $pageW, $headerH, SimplePdf::INDIGO);
    $pdf->filledRect(0, $headerTop - $headerH - 4, $pageW, 4, SimplePdf::YELLOW);

    $pdf->coasterIcon($left + 24, $headerTop - $headerH / 2 + 6, 24);
    // "TICKETS TO THRILL" with the same yellow-accented "i" as the site wordmark (assets/img/logo-horizontal-*.svg).
    $titleX = $left + 66;
    $titleY = $headerTop - 42;
    $titleSize = 22;
    $pdf->text($titleX, $titleY, 'TICKETS TO THR', $titleSize, true, SimplePdf::WHITE);
    $titleX += SimplePdf::textWidth('TICKETS TO THR', $titleSize, true);
    $pdf->text($titleX, $titleY, 'I', $titleSize, true, SimplePdf::YELLOW);
    $titleX += SimplePdf::textWidth('I', $titleSize, true);
    $pdf->text($titleX, $titleY, 'LL', $titleSize, true, SimplePdf::WHITE);
    $pdf->text($left + 66, $headerTop - 62, 'E-Ticket / Order Confirmation', 11, false, [0.851, 0.824, 0.941]);

    // Right-aligned PAID pill + order number.
    $pdf->pillLabel($right - 96, $headerTop - 38, 96, 24, 'PAID', SimplePdf::TEAL, SimplePdf::WHITE, 11);
    $pdf->rightText($right, $headerTop - 74, 'Order #' . $order['id'], 15, true, SimplePdf::WHITE);
    $pdf->rightText($right, $headerTop - 88, 'Booked ' . date('d M Y', strtotime($order['created_at'])), 9, false, [0.851, 0.824, 0.941]);

    $y = $headerTop - $headerH - 32;

    // ---- Card 1: order + attendee details ----
    $cardH = 128;
    $cardTop = $y;
    $cardBottom = $cardTop - $cardH;
    $pdf->filledRect($left, $cardBottom, $contentW, $cardH, SimplePdf::WHITE);
    $pdf->rect($left, $cardBottom, $contentW, $cardH, 1, SimplePdf::LINE);

    $ey = $cardTop - 22;
    $pdf->eyebrow($left + 18, $ey, 'ORDER DETAILS', SimplePdf::PURPLE);

    $col1 = $left + 18;
    $col2 = $left + 300;
    $labelSize = 9;
    $valueSize = 12;

    $col2MaxW = $right - 18 - $col2;

    $rowY = $ey - 28;
    $pdf->text($col1, $rowY, 'VISIT DATE', $labelSize, true, SimplePdf::INK_SOFT);
    $pdf->text($col2, $rowY, 'NAME', $labelSize, true, SimplePdf::INK_SOFT);
    $rowY -= 16;
    $visitDateText = !empty($order['visit_date']) ? date('D, d M Y', strtotime($order['visit_date'])) : 'Not specified';
    $pdf->text($col1, $rowY, $visitDateText, $valueSize, true, SimplePdf::PURPLE);
    $pdf->text($col2, $rowY, SimplePdf::truncateToWidth($user['name'], $col2MaxW, $valueSize), $valueSize, false, SimplePdf::INK);

    $rowY -= 24;
    $pdf->text($col1, $rowY, 'PAYMENT REF', $labelSize, true, SimplePdf::INK_SOFT);
    $pdf->text($col2, $rowY, 'EMAIL', $labelSize, true, SimplePdf::INK_SOFT);
    $rowY -= 16;
    $pdf->text($col1, $rowY, $order['payment_ref'] ?? '-', $valueSize, false, SimplePdf::INK);
    $pdf->text($col2, $rowY, SimplePdf::truncateToWidth($user['email'], $col2MaxW, $valueSize), $valueSize, false, SimplePdf::INK);

    // ---- Section: passes table ----
    $y = $cardBottom - 30;
    $pdf->eyebrow($left + 4, $y, 'YOUR PASSES', SimplePdf::TEAL);
    $y -= 26;

    $colQtyRight = $left + 300;
    $colPriceRight = $left + 390;
    $colTotalRight = $right - 16;

    $pdf->filledRect($left, $y - 6, $contentW, 28, SimplePdf::INDIGO);
    $pdf->text($left + 16, $y + 2, 'TICKET', 10, true, SimplePdf::WHITE);
    $pdf->rightText($colQtyRight, $y + 2, 'QTY', 10, true, SimplePdf::WHITE);
    $pdf->rightText($colPriceRight, $y + 2, 'PRICE', 10, true, SimplePdf::WHITE);
    $pdf->rightText($colTotalRight, $y + 2, 'LINE TOTAL', 10, true, SimplePdf::WHITE);
    $y -= 28;

    $rowH = 27;
    $rowIndex = 0;
    // Reserve room below the table for the total band, entry-info notes,
    // tear line, admission stub and footer.
    $bottomReserve = 330;
    foreach ($items as $item) {
        if ($y - $rowH < $bottomReserve) {
            $tint = $rowIndex % 2 === 0 ? SimplePdf::ROW_TINT_A : SimplePdf::ROW_TINT_B;
            $pdf->filledRect($left, $y - 6, $contentW, $rowH, $tint);
            $pdf->text($left + 16, $y + 3, '+ more passes - see My Account for the full breakdown', 10, false, SimplePdf::INK_SOFT);
            $y -= $rowH;
            break;
        }
        $tint = $rowIndex % 2 === 0 ? SimplePdf::ROW_TINT_A : SimplePdf::ROW_TINT_B;
        $pdf->filledRect($left, $y - 6, $contentW, $rowH, $tint);

        $lineTotal = (float) $item['price'] * (int) $item['qty'];
        $ticketNameMaxW = $colQtyRight - ($left + 16) - 10;
        $pdf->text($left + 16, $y + 3, SimplePdf::truncateToWidth($item['ticket_name'], $ticketNameMaxW, 11), 11, false, SimplePdf::INK);
        $pdf->rightText($colQtyRight, $y + 3, (string) (int) $item['qty'], 11, false, SimplePdf::INK);
        $pdf->rightText($colPriceRight, $y + 3, 'Rs. ' . number_format((float) $item['price'], 2), 11, false, SimplePdf::INK);
        $pdf->rightText($colTotalRight, $y + 3, 'Rs. ' . number_format($lineTotal, 2), 11, true, SimplePdf::INDIGO);

        $y -= $rowH;
        $rowIndex++;
    }
    $tableBottom = $y + $rowH - 6;
    $pdf->line($left, $tableBottom, $right, $tableBottom, 0.75, SimplePdf::LINE);

    // ---- Total band ----
    $y -= 8;
    $pdf->filledRect($left, $y - 10, $contentW, 36, SimplePdf::GOLD_TINT);
    $pdf->text($left + 16, $y + 2, 'TOTAL PAID', 11, true, SimplePdf::INDIGO);
    $pdf->rightText($colTotalRight, $y + 2, 'Rs. ' . number_format((float) $order['total'], 2), 15, true, SimplePdf::INDIGO);

    // ---- Entry info card ----
    $y -= 34;
    $pdf->eyebrow($left + 4, $y, 'ENTRY INFO', SimplePdf::CORAL);
    $infoY = $y - 22;
    $infoLines = [
        'Gates open one hour before the park\'s posted opening time.',
        'Carry a valid photo ID that matches the name on this ticket.',
        'This ticket is valid for single-day entry on the visit date above.',
    ];
    foreach ($infoLines as $line) {
        $pdf->filledRect($left + 2, $infoY - 3, 4, 4, SimplePdf::CORAL);
        $pdf->text($left + 14, $infoY, $line, 10, false, SimplePdf::INK_SOFT);
        $infoY -= 17;
    }

    // ---- Perforated tear line ----
    $tearY = $infoY - 8;
    $pdf->centeredText($pageW / 2, $tearY + 10, 'KEEP THIS STUB FOR ENTRY', 8, true, SimplePdf::INK_SOFT);
    $pdf->dashedLine($left + 6, $tearY, $right - 6, $tearY, 1.4, SimplePdf::INK_SOFT, 5, 4);
    $pdf->circle($left - 6, $tearY, 9, SimplePdf::PAPER, 'fill');
    $pdf->circle($right + 6, $tearY, 9, SimplePdf::PAPER, 'fill');

    // ---- Admission stub ----
    $stubTop = $tearY - 16;
    $stubH = 116;
    $stubBottom = $stubTop - $stubH;
    $pdf->filledRect($left, $stubBottom, $contentW, $stubH, SimplePdf::INDIGO);
    $pdf->filledRect($left, $stubBottom, 6, $stubH, SimplePdf::CORAL);

    $stubNameMaxW = ($right - 24) - ($left + 24) - 150;
    $pdf->text($left + 24, $stubTop - 24, 'ADMIT - TICKETS TO THRILL', 13, true, SimplePdf::WHITE);
    $pdf->text($left + 24, $stubTop - 41, SimplePdf::truncateToWidth($user['name'], $stubNameMaxW, 11), 11, false, [0.851, 0.824, 0.941]);
    $stubVisitDateText = !empty($order['visit_date']) ? date('D, d M Y', strtotime($order['visit_date'])) : 'not specified';
    $pdf->text($left + 24, $stubTop - 56, 'Valid: ' . $stubVisitDateText . '   ·   Order #' . $order['id'], 10, true, SimplePdf::YELLOW);

    $barX = $left + 24;
    $barY = $stubBottom + 20;
    $barW = 220;
    $pdf->codeBars($barX, $barY, $barW, 26, (int) $order['id'] * 97 + 13, SimplePdf::WHITE);
    $pdf->text($barX, $barY - 12, 'ORDER CODE ' . str_pad((string) $order['id'], 6, '0', STR_PAD_LEFT), 8, true, [0.700, 0.663, 0.867]);

    $pdf->rightText($right - 24, $stubTop - 41, 'Gate staff: verify by', 9, false, [0.700, 0.663, 0.867]);
    $pdf->rightText($right - 24, $stubTop - 55, 'order number in the', 9, false, [0.700, 0.663, 0.867]);
    $pdf->rightText($right - 24, $stubTop - 69, 'admin panel.', 9, false, [0.700, 0.663, 0.867]);

    // ---- Footer note ----
    $footY = $stubBottom - 28;
    $pdf->dashedLine($left, $footY + 16, $right, $footY + 16, 0.75, SimplePdf::LINE, 3, 3);
    $pdf->text($left, $footY, 'Present this ticket (printed or on screen) at the gate.', 10, true, SimplePdf::INK);
    $pdf->text($left, $footY - 15, 'This is a demo ticket - no real payment was processed.', 9, false, SimplePdf::INK_SOFT);
    $pdf->rightText($right, $footY, 'ticketstothrill.example', 9, false, SimplePdf::INK_SOFT);
    $pdf->rightText($right, $footY - 15, 'support@ticketstothrill.example', 9, false, SimplePdf::INK_SOFT);

    return $pdf->output();
}
