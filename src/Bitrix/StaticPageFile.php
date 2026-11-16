<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Pages\Bitrix;

/**
 * One public .php file of a Bitrix site, read without running it:
 *
 *     <?
 *     require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
 *     $APPLICATION->SetPageProperty("description", "Who we are");
 *     $APPLICATION->SetTitle("About us");
 *     ?>
 *     <p>The text of the page.</p>
 *     <?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>
 *
 * The title, the page properties and the HTML between the header and the
 * footer are taken; the PHP inside the text (components, include areas) is
 * dropped and its components are named, so the import can say what is lost.
 */
final class StaticPageFile
{
    /**
     * @param  array<string,string>  $properties  SetPageProperty / SetDirProperty values by lower-case name
     * @param  array<int,string>  $components  the components called inside the text
     */
    private function __construct(
        public readonly bool $isPage,
        public readonly string $title,
        public readonly array $properties,
        public readonly string $content,
        public readonly array $components,
    ) {}

    public static function read(string $file, ?string $encoding = null): self
    {
        return self::parse((string) file_get_contents($file), $encoding);
    }

    public static function parse(string $source, ?string $encoding = null): self
    {
        $source = self::toUtf8($source, $encoding);

        // A page draws the site's header; anything else is a script, an
        // include area or an AJAX handler.
        if (! preg_match('#["\']/bitrix/header\.php["\']#', $source, $header, PREG_OFFSET_CAPTURE)) {
            return new self(false, '', [], '', []);
        }

        $title = self::call($source, 'SetTitle')[0] ?? '';
        $properties = [];

        foreach (['SetPageProperty', 'SetDirProperty'] as $method) {
            foreach (self::calls($source, $method) as [$name, $value]) {
                $properties[mb_strtolower($name)] = $value;
            }
        }

        // The text starts after the PHP block with the header and ends
        // before the one with the footer.
        $start = strpos($source, '?>', $header[0][1]);
        $start = $start === false ? strlen($source) : $start + 2;
        $end = preg_match('#<\?(?:php)?(?:(?!\?>).)*?/bitrix/footer\.php#s', $source, $footer, PREG_OFFSET_CAPTURE, $start)
            ? $footer[0][1]
            : strlen($source);
        $body = substr($source, $start, max(0, $end - $start));

        preg_match_all('#IncludeComponent\(\s*["\']([^"\']+)["\']#', $body, $matches);
        $components = array_values(array_unique($matches[1]));

        // PHP blocks inside the text, and an unclosed one at its end.
        $content = preg_replace('#<\?(?:php|=)?.*?(?:\?>|$)#s', '', $body) ?? $body;

        return new self(true, $title, $properties, trim($content), $components);
    }

    /**
     * The name of a folder from its .section.php ($sSectionName = "About").
     */
    public static function sectionName(string $file, ?string $encoding = null): string
    {
        $source = self::toUtf8((string) file_get_contents($file), $encoding);

        if (preg_match('#\$sSectionName\s*=\s*("(?:[^"\\\\]|\\\\.)*"|\'(?:[^\'\\\\]|\\\\.)*\')#s', $source, $match)) {
            return self::unquote($match[1]);
        }

        return '';
    }

    /**
     * @return array<int,string> the string arguments of the first call
     */
    private static function call(string $source, string $method): array
    {
        return self::calls($source, $method)[0] ?? [];
    }

    /**
     * The string arguments of every $APPLICATION->{method}(…) call.
     *
     * @return array<int,array<int,string>>
     */
    private static function calls(string $source, string $method): array
    {
        $string = '"(?:[^"\\\\]|\\\\.)*"|\'(?:[^\'\\\\]|\\\\.)*\'';
        $pattern = '#->\s*'.$method.'\s*\(\s*('.$string.')\s*(?:,\s*('.$string.')\s*)?(?:,[^)]*)?\)#s';

        preg_match_all($pattern, $source, $matches, PREG_SET_ORDER);

        return array_map(
            static fn (array $match): array => array_values(array_map(self::unquote(...), array_filter(array_slice($match, 1), static fn (string $part): bool => $part !== ''))),
            $matches,
        );
    }

    /**
     * A PHP string literal as its value. Double quotes take the usual
     * escapes; single quotes only \' and \\.
     */
    private static function unquote(string $literal): string
    {
        $quote = $literal[0];
        $inner = substr($literal, 1, -1);

        if ($quote === "'") {
            return strtr($inner, ['\\\\' => '\\', "\\'" => "'"]);
        }

        return stripcslashes($inner);
    }

    /**
     * Old Bitrix sites keep their files in windows-1251.
     */
    private static function toUtf8(string $source, ?string $encoding): string
    {
        if ($encoding !== null && strcasecmp($encoding, 'UTF-8') !== 0) {
            return (string) mb_convert_encoding($source, 'UTF-8', $encoding);
        }

        if ($encoding === null && ! mb_check_encoding($source, 'UTF-8')) {
            return (string) mb_convert_encoding($source, 'UTF-8', 'Windows-1251');
        }

        return $source;
    }
}
