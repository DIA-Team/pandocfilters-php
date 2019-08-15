#!/usr/bin/env php
<?php

/**
 * Performs manipulations of Markdown text to help make a manuscript, such as
 * described at http://www.shunn.net/format/story.html
 *
 * Requirements
 *
 * The file pandocfilters.php must be in the current or the parent directory.
 *
 * The code in this file is considered public domain, thanks to the author Dave Jarvis.
 */

require_once __DIR__ . '/../PandocFilter.php';


PandocFilter::toJSONFilter(function ($key, $value, $format, $meta)
use ($Str, $Header) {

    if ($key === 'Image') {
        // Images are not allowed inside manuscripts.
        return $Str('');
    } elseif ($key === 'Link') {
        // Extract the hyperlink text, discard the URL.
        return $Str(PandocFilter::stringify($value));
    } elseif ($key === 'Header' && $value[0] == 2) {
        // Make the header level 2 titlecase.
        $s = $Str(ucwords(PandocFilter::stringify($value[2])));

        // Replace the old header with the new header.
        return $Header($value[0], $value[1], [$s]);
    }
});
