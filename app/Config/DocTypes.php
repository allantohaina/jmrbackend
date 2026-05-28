<?php

namespace Config;

class DocTypes
{
    /**
     * List of valid document types.
     *
     * @var array<string, string>
     */
    public array $list = [];

    /**
     * Whether to remove the solidus (`/`) character for void HTML elements (e.g. `<input>`)
     * for HTML5 compatibility.
     *
     * Set to:
     *    `true` - to be HTML5 compatible
     *    `false` - to be XHTML compatible
     */
    public bool $html5 = true;

    public function __construct()
    {
        $this->list = [
            'xhtml11' => $this->buildPublicDoctype(
                'html',
                '-//W3C//DTD XHTML 1.1//EN',
                'https://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd'
            ),
            'xhtml1-strict' => $this->buildPublicDoctype(
                'html',
                '-//W3C//DTD XHTML 1.0 Strict//EN',
                'https://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd'
            ),
            'xhtml1-trans' => $this->buildPublicDoctype(
                'html',
                '-//W3C//DTD XHTML 1.0 Transitional//EN',
                'https://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd'
            ),
            'xhtml1-frame' => $this->buildPublicDoctype(
                'html',
                '-//W3C//DTD XHTML 1.0 Frameset//EN',
                'https://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd'
            ),
            'xhtml-basic11' => $this->buildPublicDoctype(
                'html',
                '-//W3C//DTD XHTML Basic 1.1//EN',
                'https://www.w3.org/TR/xhtml-basic/xhtml-basic11.dtd'
            ),
            'html5' => '<!DOCTYPE html>',
            'html4-strict' => $this->buildPublicDoctype(
                'html',
                '-//W3C//DTD HTML 4.01//EN',
                'https://www.w3.org/TR/html4/strict.dtd'
            ),
            'html4-trans' => $this->buildPublicDoctype(
                'html',
                '-//W3C//DTD HTML 4.01 Transitional//EN',
                'https://www.w3.org/TR/html4/loose.dtd'
            ),
            'html4-frame' => $this->buildPublicDoctype(
                'html',
                '-//W3C//DTD HTML 4.01 Frameset//EN',
                'https://www.w3.org/TR/html4/frameset.dtd'
            ),
            'mathml1' => $this->buildSystemDoctype(
                'math',
                'https://www.w3.org/Math/DTD/mathml1/mathml.dtd'
            ),
            'mathml2' => $this->buildPublicDoctype(
                'math',
                '-//W3C//DTD MathML 2.0//EN',
                'https://www.w3.org/Math/DTD/mathml2/mathml2.dtd'
            ),
            'svg10' => $this->buildPublicDoctype(
                'svg',
                '-//W3C//DTD SVG 1.0//EN',
                'https://www.w3.org/TR/2001/REC-SVG-20010904/DTD/svg10.dtd'
            ),
            'svg11' => $this->buildPublicDoctype(
                'svg',
                '-//W3C//DTD SVG 1.1//EN',
                'https://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd'
            ),
            'svg11-basic' => $this->buildPublicDoctype(
                'svg',
                '-//W3C//DTD SVG 1.1 Basic//EN',
                'https://www.w3.org/Graphics/SVG/1.1/DTD/svg11-basic.dtd'
            ),
            'svg11-tiny' => $this->buildPublicDoctype(
                'svg',
                '-//W3C//DTD SVG 1.1 Tiny//EN',
                'https://www.w3.org/Graphics/SVG/1.1/DTD/svg11-tiny.dtd'
            ),
            'xhtml-math-svg-xh' => $this->buildPublicDoctype(
                'html',
                '-//W3C//DTD XHTML 1.1 plus MathML 2.0 plus SVG 1.1//EN',
                'https://www.w3.org/2002/04/xhtml-math-svg/xhtml-math-svg.dtd'
            ),
            'xhtml-math-svg-sh' => $this->buildPublicDoctype(
                'svg:svg',
                '-//W3C//DTD XHTML 1.1 plus MathML 2.0 plus SVG 1.1//EN',
                'https://www.w3.org/2002/04/xhtml-math-svg/xhtml-math-svg.dtd'
            ),
            'xhtml-rdfa-1' => $this->buildPublicDoctype(
                'html',
                '-//W3C//DTD XHTML+RDFa 1.0//EN',
                'https://www.w3.org/MarkUp/DTD/xhtml-rdfa-1.dtd'
            ),
            'xhtml-rdfa-2' => $this->buildPublicDoctype(
                'html',
                '-//W3C//DTD XHTML+RDFa 1.1//EN',
                'https://www.w3.org/MarkUp/DTD/xhtml-rdfa-2.dtd'
            ),
        ];
    }

    private function buildPublicDoctype(string $root, string $publicId, string $systemId): string
    {
        return '<!DOCTYPE ' . $root . ' PUBLIC "' . $publicId . '" "' . $systemId . '">';
    }

    private function buildSystemDoctype(string $root, string $systemId): string
    {
        return '<!DOCTYPE ' . $root . ' SYSTEM "' . $systemId . '">';
    }
}
