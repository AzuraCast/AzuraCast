<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @package    Zend_Pdf
 * @subpackage Fonts
 * @copyright  Copyright (c) 2005-2007 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/** Zend_Pdf_Resource_Font */
require_once 'Zend/Pdf/Resource/Font.php';


/**
 * OpenType fonts implementation
 *
 * OpenType fonts can contain either TrueType or PostScript Type 1 outlines. The
 * code in this class is common to both types. However, you will only deal
 * directly with subclasses.
 *
 * Font objects should be normally be obtained from the factory methods
 * {@link Zend_Pdf_Font::fontWithName} and {@link Zend_Pdf_Font::fontWithPath}.
 *
 * @package    Zend_Pdf
 * @subpackage Fonts
 * @copyright  Copyright (c) 2005-2007 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
abstract class Zend_Pdf_Resource_Font_OpenType extends Zend_Pdf_Resource_Font
{
  /**** Public Interface ****/


  /* Object Lifecycle */

    /**
     * Object constructor
     *
     * The $embeddingOptions parameter allows you to set certain flags related
     * to font embedding. You may combine options by OR-ing them together. See
     * the EMBED_ constants defined in {@link Zend_Pdf_Font} for the list of
     * available options and their descriptions.
     *
     * Note that it is not requried that fonts be embedded within the PDF file
     * to use them. If the recipient of the PDF has the font installed on their
     * computer, they will see the correct fonts in the document. If they don't,
     * the PDF viewer will substitute or synthesize a replacement.
     *
     * @param Zend_Pdf_FileParser_Font_OpenType $fontParser Font parser object
     *   containing OpenType file.
     * @param integer $embeddingOptions Options for font embedding.
     * @throws Zend_Pdf_Exception
     */
    public function __construct(Zend_Pdf_FileParser_Font_OpenType $fontParser, $embeddingOptions)
    {
        $fontParser->parse();

        parent::__construct($embeddingOptions);


        /* Object properties */

        $this->_fontNames = $fontParser->names;

        $this->_isBold = $fontParser->isBold;
        $this->_isItalic = $fontParser->isItalic;
        $this->_isMonospaced = $fontParser->isMonospaced;

        $this->_underlinePosition = $fontParser->underlinePosition;
        $this->_underlineThickness = $fontParser->underlineThickness;
        $this->_strikePosition = $fontParser->strikePosition;
        $this->_strikeThickness = $fontParser->strikeThickness;

        $this->_unitsPerEm = $fontParser->unitsPerEm;

        $this->_ascent  = $fontParser->ascent;
        $this->_descent = $fontParser->descent;
        $this->_lineGap = $fontParser->lineGap;

        $this->_glyphWidths = $fontParser->glyphWidths;
        $this->_glyphMaxIndex = count($this->_glyphWidths) - 1;

        $this->cmap = $fontParser->cmap;


        /* Resource dictionary */

        $baseFont = $this->getFontName(Zend_Pdf_Font::NAME_POSTSCRIPT, 'en', 'UTF-8');
        $this->_resource->BaseFont = new Zend_Pdf_Element_Name($baseFont);

        $this->_resource->FirstChar = new Zend_Pdf_Element_Numeric(0);
        $this->_resource->LastChar  = new Zend_Pdf_Element_Numeric(255);

        /* Build up the widths array and add it as an indirect object. The
         * character codes contained in this array are the Unicode characters
         * representing the WinAnsi (CP1252) character set. This corresponds to
         * to encoding method specified below.
         */
        $characterCodes = array(
              0x00,   0x01,   0x02,   0x03,   0x04,   0x05,   0x06,   0x07,
              0x08,   0x09,   0x0a,   0x0b,   0x0c,   0x0d,   0x0e,   0x0f,
              0x10,   0x11,   0x12,   0x13,   0x14,   0x15,   0x16,   0x17,
              0x18,   0x19,   0x1a,   0x1b,   0x1c,   0x1d,   0x1e,   0x1f,
              0x20,   0x21,   0x22,   0x23,   0x24,   0x25,   0x26,   0x27,
              0x28,   0x29,   0x2a,   0x2b,   0x2c,   0x2d,   0x2e,   0x2f,
              0x30,   0x31,   0x32,   0x33,   0x34,   0x35,   0x36,   0x37,
              0x38,   0x39,   0x3a,   0x3b,   0x3c,   0x3d,   0x3e,   0x3f,
              0x40,   0x41,   0x42,   0x43,   0x44,   0x45,   0x46,   0x47,
              0x48,   0x49,   0x4a,   0x4b,   0x4c,   0x4d,   0x4e,   0x4f,
              0x50,   0x51,   0x52,   0x53,   0x54,   0x55,   0x56,   0x57,
              0x58,   0x59,   0x5a,   0x5b,   0x5c,   0x5d,   0x5e,   0x5f,
              0x60,   0x61,   0x62,   0x63,   0x64,   0x65,   0x66,   0x67,
              0x68,   0x69,   0x6a,   0x6b,   0x6c,   0x6d,   0x6e,   0x6f,
              0x70,   0x71,   0x72,   0x73,   0x74,   0x75,   0x76,   0x77,
              0x78,   0x79,   0x7a,   0x7b,   0x7c,   0x7d,   0x7e,   0x7f,
            0x20ac,   0x00, 0x201a, 0x0192, 0x201e, 0x2026, 0x2020, 0x2021,
            0x02c6, 0x2030, 0x0160, 0x2039, 0x0152,   0x00, 0x017d,   0x00,
              0x00, 0x2018, 0x2019, 0x201c, 0x201d, 0x2022, 0x2013, 0x2014,
            0x02dc, 0x2122, 0x0161, 0x203a, 0x0153,   0x00, 0x017e, 0x0178,
              0xa0,   0xa1,   0xa2,   0xa3,   0xa4,   0xa5,   0xa6,   0xa7,
              0xa8,   0xa9,   0xaa,   0xab,   0xac,   0xad,   0xae,   0xaf,
              0xb0,   0xb1,   0xb2,   0xb3,   0xb4,   0xb5,   0xb6,   0xb7,
              0xb8,   0xb9,   0xba,   0xbb,   0xbc,   0xbd,   0xbe,   0xbf,
              0xc0,   0xc1,   0xc2,   0xc3,   0xc4,   0xc5,   0xc6,   0xc7,
              0xc8,   0xc9,   0xca,   0xcb,   0xcc,   0xcd,   0xce,   0xcf,
              0xd0,   0xd1,   0xd2,   0xd3,   0xd4,   0xd5,   0xd6,   0xd7,
              0xd8,   0xd9,   0xda,   0xdb,   0xdc,   0xdd,   0xde,   0xdf,
              0xe0,   0xe1,   0xe2,   0xe3,   0xe4,   0xe5,   0xe6,   0xe7,
              0xe8,   0xe9,   0xea,   0xeb,   0xec,   0xed,   0xee,   0xef,
              0xf0,   0xf1,   0xf2,   0xf3,   0xf4,   0xf5,   0xf6,   0xf7,
              0xf8,   0xf9,   0xfa,   0xfb,   0xfc,   0xfd,   0xfe,   0xff);

        /* Convert characters to glyphs and then get the widths.
         */
        $glyphNumbers = $this->cmap->glyphNumbersForCharacters($characterCodes);
        $glyphWidths  = $this->widthsForGlyphs($glyphNumbers);

        /* Now convert the scalar glyph widths to Zend_Pdf_Element_Numeric objects.
         */
        $pdfWidths = array();
        foreach ($glyphWidths as $width) {
            $pdfWidths[] = new Zend_Pdf_Element_Numeric($this->_toEmSpace($width));
        }

        /* Create the Zend_Pdf_Element_Array object and add it to the font's
         * object factory and resource dictionary.
         */
        $widthsArrayElement = new Zend_Pdf_Element_Array($pdfWidths);
        $widthsObject = $this->_objectFactory->newObject($widthsArrayElement);
        $this->_resource->Widths = $widthsObject;

        $this->_resource->Encoding = new Zend_Pdf_Element_Name('WinAnsiEncoding');

    }

}
