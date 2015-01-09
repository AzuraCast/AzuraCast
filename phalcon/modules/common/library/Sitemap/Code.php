<?php

namespace Baseapp\Library\Sitemap;

class Code implements \Baseapp\Library\Sitemap\SitemapInterface
{

    private $_attributes = array(
        'filetype' => null,
        'license' => null,
        'filename' => null,
        'packageurl' => null,
        'packagemap' => null
    );
    protected $_licenses = array(
        'aladdin', 'artistic', 'apache', 'apple', 'bsd', 'cpl', 'gpl', 'lgpl', 'disclaimer',
        'ibm', 'lucent', 'mit', 'mozilla', 'nasa', 'python', 'qpl', 'sleepycat', 'zope'
    );
    protected $_archives = array(
        '.tar', '.tar.z', '.tar.gz', '.tgz', '.tar.bz2', '.tbz', '.tbz2', '.zip'
    );

    /**
     * @param string $type Case-insensitive. The value "archive" indicates that
     * the file is an archive file. For source code files, the value defines the
     * the source code language. Examples include "C", "Python", "C#", "Java", "Vim".
     * For source code language, the Short Name, as specified in the list of supported
     * languages, must be used. The value must be printable ASCII characters, and
     * no white space is allowed.
     *
     * @see http://www.google.com/support/webmasters/bin/answer.py?answer=75252
     */
    public function set_file_type($type)
    {
        $type = (string) $type;

        if (!preg_match('/^[a-z][a-z0-9+#]*$/i', $type)) {
            throw new \InvalidArgumentException('Type must only contain a-z, 0-9, + and #');
        }

        $this->_attributes['filetype'] = $type;

        return $this;
    }

    /**
     * @param string $license Case-insensitive. The name of the software license.
     * For archive files, this indicates the default license for files in the archive.
     * Examples include "GPL", "BSD", "Python", "disclaimer". You must use the Short
     * Name, as specified in the list of supported licenses.
     *
     * @see http://www.google.com/support/webmasters/bin/answer.py?answer=75256
     */
    public function set_license($license)
    {
        $license = (string) $license;

        if (!in_array($license, $this->_licenses)) {
            throw new \InvalidArgumentException('Invalid license type. See http://www.google.com/support/webmasters/bin/answer.py?answer=75256 for details');
        }

        $this->_attributes['license'] = $license;

        return $this;
    }

    /**
     * @param string $file_name The name of the actual file. This is useful if the
     * URL ends in something like download.php?id=1234 instead of the actual filename.
     * The name can contain any character except "/". If the file is an archive file,
     * it will be indexed only if it has one of the supported archive suffixes.
     *
     * @see http://www.google.com/support/webmasters/bin/answer.py?answer=75259
     */
    public function set_file_name($file_name)
    {
        $file_name = (string) $file_name;

        if ($this->_attributes['filetype'] === 'archive') {
            if (!in_array(pathinfo($file_name, PATHINFO_EXTENSION), $this->_archives)) {
                throw new \InvalidArgumentException('Not a valid archive type');
            }
        }

        $this->_attributes['filename'] = basename($file_name);

        return $this;
    }

    /**
     * @param <type> $package_type For use only when the value of codesearch:filetype
     * is not "archive". The URL truncated at the top-level directory for the package.
     * For example, the file http://path/Foo/1.23/bar/file.c could have the package URL
     * http://path/Foo/1.23. All files in a package should have the same packageurl.
     * This tells us which files belong together.
     */
    public function set_package_url($package_type)
    {
        $this->_attributes['packageurl'] = $package_type;
    }

    /**
     * @param string $package_map Case-sensitive. For use only when codesearch:filetype
     * is "archive". The name of the packagemap file inside the archive. Just like a
     * Sitemap is a list of files on a web site, a packagemap is a list of files in
     * a package.
     *
     * @see http://www.google.com/help/codesearch_packagemap.html
     */
    public function set_package_map($package_map)
    {
        $this->_attributes['packagemap'] = $package_map;
    }

    public function create()
    {
        // Here we need to create a new DOMDocument. This is so we can re-import the
        // DOMElement at the other end.
        $document = new \DOMDocument;

        // Mobile element
        $code = $document->createElement('codesearch:codesearch');

        // Append attributes
        foreach ($this->_attributes as $name => $value) {
            if (null !== $value) {
                $code->appendChild($document->createElement('codesearch:' . $name, $value));
            }
        }

        return $code;
    }

    public function root(\DOMElement & $root)
    {
        $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:codesearch', 'http://www.google.com/codesearch/schemas/sitemap/1.0');
    }

}
