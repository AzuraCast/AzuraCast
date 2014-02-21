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

/** Zend_Pdf_Resource_Font_OpenType */
require_once 'Zend/Pdf/Resource/Font/OpenType.php';


/**
 * TrueType fonts implementation
 *
 * Font objects should be normally be obtained from the factory methods
 * {@link Zend_Pdf_Font::fontWithName} and {@link Zend_Pdf_Font::fontWithPath}.
 *
 * @package    Zend_Pdf
 * @subpackage Fonts
 * @copyright  Copyright (c) 2005-2007 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Pdf_Resource_Font_OpenType_TrueType extends Zend_Pdf_Resource_Font_OpenType
{
  /**** Public Interface ****/


  /* Object Lifecycle */

    /**
     * Object constructor
     *
     * @param Zend_Pdf_FileParser_Font_OpenType_TrueType $fontParser Font parser
     *   object containing parsed TrueType file.
     * @param integer $embeddingOptions Options for font embedding.
     * @throws Zend_Pdf_Exception
     */
    public function __construct(Zend_Pdf_FileParser_Font_OpenType_TrueType $fontParser, $embeddingOptions)
    {
        parent::__construct($fontParser, $embeddingOptions);


        /* Object properties */

        $this->_fontType = Zend_Pdf_Font::TYPE_TRUETYPE;


        /* Resource dictionary */

        $this->_resource->Subtype  = new Zend_Pdf_Element_Name('TrueType');

        /* The font descriptor object contains the rest of the font metrics and
         * the information about the embedded font program (if applicible).
         */
        $fontDescriptor = new Zend_Pdf_Element_Dictionary();

        $fontDescriptor->Type = new Zend_Pdf_Element_Name('FontDescriptor');
        $fontDescriptor->FontName = $this->_resource->BaseFont;

        /* The font flags value is a bitfield that describes the stylistic
         * attributes of the font. We will set as many of the bits as can be
         * determined from the font parser.
         */
        $flags = 0;
        if ($fontParser->isMonospaced) {    // bit 1: FixedPitch
            $flags |= 1 << 0;
        }
        if ($fontParser->isSerifFont) {    // bit 2: Serif
            $flags |= 1 << 1;
        }
        if (! $fontParser->isAdobeLatinSubset) {    // bit 3: Symbolic
            $flags |= 1 << 2;
        }
        if ($fontParser->isScriptFont) {    // bit 4: Script
            $flags |= 1 << 3;
        }
        if ($fontParser->isAdobeLatinSubset) {    // bit 6: Nonsymbolic
            $flags |= 1 << 5;
        }
        if ($fontParser->isItalic) {    // bit 7: Italic
            $flags |= 1 << 6;
        }
        // bits 17-19: AllCap, SmallCap, ForceBold; not available
        $fontDescriptor->Flags = new Zend_Pdf_Element_Numeric($flags);

        $fontBBox = array(new Zend_Pdf_Element_Numeric($this->_toEmSpace($fontParser->xMin)),
                          new Zend_Pdf_Element_Numeric($this->_toEmSpace($fontParser->yMin)),
                          new Zend_Pdf_Element_Numeric($this->_toEmSpace($fontParser->xMax)),
                          new Zend_Pdf_Element_Numeric($this->_toEmSpace($fontParser->yMax)));
        $fontDescriptor->FontBBox = new Zend_Pdf_Element_Array($fontBBox);

        $fontDescriptor->ItalicAngle = new Zend_Pdf_Element_Numeric($fontParser->italicAngle);

        $fontDescriptor->Ascent = new Zend_Pdf_Element_Numeric($this->_toEmSpace($fontParser->ascent));
        $fontDescriptor->Descent = new Zend_Pdf_Element_Numeric($this->_toEmSpace($fontParser->descent));

        $fontDescriptor->CapHeight = new Zend_Pdf_Element_Numeric($fontParser->capitalHeight);
        /**
         * The vertical stem width is not yet extracted from the OpenType font
         * file. For now, record zero which is interpreted as 'unknown'.
         * @todo Calculate value for StemV.
         */
        $fontDescriptor->StemV = new Zend_Pdf_Element_Numeric(0);

        /* Set up font embedding. This is where the actual font program itself
         * is embedded within the PDF document.
         *
         * Note that it is not requried that fonts be embedded within the PDF
         * document to use them. If the recipient of the PDF has the font
         * installed on their computer, they will see the correct fonts in the
         * document. If they don't, the PDF viewer will substitute or synthesize
         * a replacement.
         *
         * There are several guidelines for font embedding:
         *
         * First, the developer might specifically request not to embed the font.
         */
        if (! $this->_isEmbeddingOptionSet(Zend_Pdf_Font::EMBED_DONT_EMBED)) {

            /* Second, the font author may have set copyright bits that prohibit
             * the font program from being embedded. Yes this is controversial,
             * but it's the rules:
             *   http://partners.adobe.com/public/developer/en/acrobat/sdk/FontPolicies.pdf
             *
             * To keep the developer in the loop, and to prevent surprising bug
             * reports of "your PDF doesn't have the right fonts," throw an
             * exception if the font cannot be embedded.
             */
            if (! $fontParser->isEmbeddable) {
                /* This exception may be suppressed if the developer decides that
                 * it's not a big deal that the font program can't be embedded.
                 */
                if (! $this->_isEmbeddingOptionSet(Zend_Pdf_Font::EMBED_SUPPRESS_EMBED_EXCEPTION)) {
                    $message = 'This font cannot be embedded in the PDF document. If you would like to use '
                             . 'it anyway, you must pass Zend_Pdf_Font::EMBED_SUPPRESS_EMBED_EXCEPTION '
                             . 'in the $options parameter of the font constructor.';
                    throw new Zend_Pdf_Exception($message, Zend_Pdf_Exception::FONT_CANT_BE_EMBEDDED);
                }

            } else {
                /* Otherwise, the default behavior is to embed all custom fonts.
                 */
                /* This section will change soon to a stream object data
                 * provider model so that we don't have to keep a copy of the
                 * entire font in memory.
                 *
                 * We also cannot build font subsetting until the data provider
                 * model is in place.
                 */
                $fontFile = $fontParser->getDataSource()->readAllBytes();
                $fontFileObject = $this->_objectFactory->newStreamObject($fontFile);
                $fontFileObject->dictionary->Length1 = new Zend_Pdf_Element_Numeric(strlen($fontFile));
                if (! $this->_isEmbeddingOptionSet(Zend_Pdf_Font::EMBED_DONT_COMPRESS)) {
                    /* Compress the font file using Flate. This generally cuts file
                     * sizes by about half!
                     */
                    $filter = new Zend_Pdf_Element_Array(array(new Zend_Pdf_Element_Name('FlateDecode')));
                    $fontFileObject->dictionary->Filter = $filter;
                }
                $fontDescriptor->FontFile2 = $fontFileObject;
            }
        }

        $fontDescriptorObject = $this->_objectFactory->newObject($fontDescriptor);
        $this->_resource->FontDescriptor = $fontDescriptorObject;

        $this->_resource->Encoding = new Zend_Pdf_Element_Name('WinAnsiEncoding');
    }

}
