<?php

namespace Drupal\custom_stream_wrapper\StreamWrapper;

use Drupal\Core\StreamWrapper\LocalReadOnlyStream;
use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;

/**
 * Abstract stream wrapper class for symlinked files. 
 */
abstract class SymlinkStream extends PublicStream
{
    /**
     * {@inheritdoc}
     */
    protected function getLocalPath($uri = NULL)
    {
        if (!isset($uri)) {
            $uri = $this->uri;
        }

        $target = $this->getTarget($uri);

        // Reject path traversal attempts at the URI level, before any filesystem
        // resolution. Normalise separators first to catch both Unix and Windows
        // variants (e.g. "..\"). realpath() cannot be used for this because it
        // resolves symlinks to a path outside $directory by design.
        if (str_contains(str_replace('\\', '/', $target), '..')) {
            return FALSE;
        }

        $path = $this->getDirectoryPath() . '/' . $target;


        // In PHPUnit tests, the base path for local streams may be a virtual
        // filesystem stream wrapper URI, in which case this local stream acts like
        // a proxy. realpath() is not supported by vfsStream, because a virtual
        // file system does not have a real filepath.
        if (str_starts_with($path, 'vfs://')) {
            return $path;
        }

        $realpath = realpath($path);
        if (!$realpath) {
            // This file does not yet exist.
            $realpath = realpath(dirname($path)) . '/' . \Drupal::service('file_system')->basename($path);
        }

        $directory = realpath($this->getDirectoryPath());
        if (
            !$realpath ||
            !$directory
            // Overriden to not compare the $realpath and $directory because they will always be different for symlinked files. Perhaps there is a better way to do this 
            // !str_starts_with($realpath, $directory) 
        ) {
            return FALSE;
        }

        return $realpath;
    }
}
