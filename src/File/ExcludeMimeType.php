<?php

/**
 * @see       https://github.com/laminas/laminas-validator for the canonical source repository
 * @copyright https://github.com/laminas/laminas-validator/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-validator/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Validator\File;

use finfo;

/**
 * Validator for the mime type of a file
 *
 * @category  Laminas
 * @package   Laminas_Validator
 */
class ExcludeMimeType extends MimeType
{
    const FALSE_TYPE   = 'fileExcludeMimeTypeFalse';
    const NOT_DETECTED = 'fileExcludeMimeTypeNotDetected';
    const NOT_READABLE = 'fileExcludeMimeTypeNotReadable';

    /**
     * Returns true if the mimetype of the file does not matche the given ones. Also parts
     * of mimetypes can be checked. If you give for example "image" all image
     * mime types will not be accepted like "image/gif", "image/jpeg" and so on.
     *
     * @param  string $value Real file to check for mimetype
     * @param  array  $file  File data from \Laminas\File\Transfer\Transfer
     * @return boolean
     */
    public function isValid($value, $file = null)
    {
        if ($file === null) {
            $file = array(
                'type' => null,
                'name' => $value,
            );
        }

        // Is file readable ?
        if (false === stream_resolve_include_path($value)) {
            return $this->createError($file, self::NOT_READABLE);
        }

        $mimefile = $this->getMagicFile();
        if (class_exists('finfo', false)) {
            $const = defined('FILEINFO_MIME_TYPE') ? FILEINFO_MIME_TYPE : FILEINFO_MIME;
            if (!$this->isMagicFileDisabled() && (!empty($mimefile) && empty($this->finfo))) {
                $this->finfo = finfo_open($const, $mimefile);
            }

            if (empty($this->finfo)) {
                $this->finfo = finfo_open($const);
            }

            $this->type = null;
            if (!empty($this->finfo)) {
                $this->type = finfo_file($this->finfo, $value);
            }
        }

        if (empty($this->type) &&
            (function_exists('mime_content_type') && ini_get('mime_magic.magicfile'))
        ) {
            $this->type = mime_content_type($value);
        }

        if (empty($this->type) && $this->getHeaderCheck()) {
            $this->type = $file['type'];
        }

        if (empty($this->type)) {
            return $this->createError($file, self::NOT_DETECTED);
        }

        $mimetype = $this->getMimeType(true);
        if (in_array($this->type, $mimetype)) {
            return $this->createError($file, self::FALSE_TYPE);
        }

        $types = explode('/', $this->type);
        $types = array_merge($types, explode('-', $this->type));
        $types = array_merge($types, explode(';', $this->type));
        foreach ($mimetype as $mime) {
            if (in_array($mime, $types)) {
                return $this->createError($file, self::FALSE_TYPE);
            }
        }

        return true;
    }
}
