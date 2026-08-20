<?php

namespace App\Libraries;

/**
 * Générateur PDF minimal sans dépendance externe.
 * Produit des PDF 1.4 valides (texte + lignes) pour les reçus.
 */
class MiniPdf
{
    private const MM_TO_PT = 2.834645669;

    private array $objects = [];
    private string $content = '';
    private float $pageW = 419.5; // A5 largeur (pt)
    private float $pageH = 595.3; // A5 hauteur (pt)

    public function __construct(float $widthMm = 148, float $heightMm = 210)
    {
        $this->pageW = $widthMm * self::MM_TO_PT;
        $this->pageH = $heightMm * self::MM_TO_PT;
    }

    public function setFont(bool $bold, float $sizePt): void
    {
        $this->font = $bold ? 'F2' : 'F1';
        $this->fontSize = $sizePt;
    }

    public function text(float $xMm, float $yMm, string $str): void
    {
        $x = round($xMm * self::MM_TO_PT, 2);
        // PDF y est mesuré depuis le bas : convertir depuis le haut
        $y = round($this->pageH - ($yMm * self::MM_TO_PT), 2);
        $escaped = $this->escape($str);
        $this->content .= sprintf(
            "BT /%s %.1f Tf %s %.2f Td (%s) Tj ET\n",
            $this->font,
            $this->fontSize,
            $x,
            $y,
            $escaped
        );
    }

    public function line(float $x1Mm, float $y1Mm, float $x2Mm, float $y2Mm): void
    {
        $x1 = round($x1Mm * self::MM_TO_PT, 2);
        $y1 = round($this->pageH - ($y1Mm * self::MM_TO_PT), 2);
        $x2 = round($x2Mm * self::MM_TO_PT, 2);
        $y2 = round($this->pageH - ($y2Mm * self::MM_TO_PT), 2);
        $this->content .= sprintf("%s %s m %s %s l S\n", $x1, $y1, $x2, $y2);
    }

    public function output(): string
    {
        $this->objects = [];
        $this->objects[1] = "<< /Type /Catalog /Pages 2 0 R >>";
        $this->objects[2] = "<< /Type /Pages /Kids [3 0 R] /Count 1 >>";
        $this->objects[3] = sprintf(
            "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %s %s] /Resources << /Font << /F1 4 0 R /F2 5 0 R >> >> /Contents 6 0 R >>",
            $this->pageW,
            $this->pageH
        );
        $this->objects[4] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>";
        $this->objects[5] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>";

        $stream = "BT\n" . $this->content . "ET\n";
        $streamLen = strlen($stream);
        $this->objects[6] = sprintf("<< /Length %d >>\nstream\n%s\nendstream", $streamLen, $stream);

        $pdf = "%PDF-1.4\n";
        $offsets = [];
        foreach ($this->objects as $num => $body) {
            $offsets[$num] = strlen($pdf);
            $pdf .= sprintf("%d 0 obj\n%s\nendobj\n", $num, $body);
        }

        $xrefStart = strlen($pdf);
        $pdf .= "xref\n0 " . (count($this->objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }
        $pdf .= sprintf("trailer\n<< /Size %d /Root 1 0 R >>\nstartxref\n%d\n%%%%EOF", count($this->objects) + 1, $xrefStart);

        return $pdf;
    }

    private string $font = 'F1';
    private float $fontSize = 10;

    private function escape(string $str): string
    {
        $str = $this->transliterate($str);
        $out = '';
        $len = strlen($str);
        for ($i = 0; $i < $len; $i++) {
            $c = $str[$i];
            $o = ord($c);
            if ($c === '(' || $c === ')' || $c === '\\') {
                $out .= '\\' . $c;
            } elseif ($o < 128 || $o >= 160) {
                $out .= $c;
            } else {
                $out .= sprintf('\\%03o', $o);
            }
        }
        return $out;
    }

    private function transliterate(string $str): string
    {
        $map = [
            '€' => chr(128), '‘' => chr(145), '’' => chr(146), '“' => chr(147), '”' => chr(148),
            'œ' => chr(156), 'Œ' => chr(140), '•' => chr(149), '…' => chr(133), '–' => chr(150), '—' => chr(151),
        ];
        $str = strtr($str, $map);
        // Nettoyage final des caractères hors Latin-1
        return preg_replace('/[^\x00-\xFF]/u', '', $str) ?? $str;
    }
}