# PHP pandocfilters

This is a PHP port of the python module for writing pandoc filters found at
[jgm/pandocfilters](https://github.com/jgm/pandocfilters)

The purpose is simply to make it easier to write filters in PHP.

```php
#!/usr/bin/env php
<?php

require_once __DIR__ . '/../pandocfilters.php';

Pandoc_Filter::toJSONFilter(function ($type, $value, $format, $meta) {
    if ('Str' == $type) {
        // use mb_convert_case instead of ucwords so filter works with unicode
        return mb_convert_case($value, MB_CASE_TITLE, "UTF-8");
    }
});

```

This is a fork of
[Vinai/pandocfilters-php](https://github.com/Vinai/pandocfilters-php) packaged
as a composer package

Thanks to John MacFarlane for pandoc.
