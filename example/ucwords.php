#!/usr/bin/env php
<?php

require_once __DIR__ . '/../PandocFilter.php';

PandocFilter::toJSONFilter(function ($type, $value, $format, $meta) {
    if ('Str' == $type) {
        // use mb_convert_case instead of ucwords so filter works with unicode
        return mb_convert_case($value, MB_CASE_TITLE, "UTF-8");
    }
});
