<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Pages\Tests;

use Atrium\Pages\Bitrix\StaticPageFile;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase as PlainTestCase;

/**
 * How a Bitrix page file is read: where its text starts and ends, its
 * title and properties, the components inside it and its encoding.
 */
final class StaticPageFileTest extends PlainTestCase
{
    private const HEAD = "<?\nrequire(\$_SERVER[\"DOCUMENT_ROOT\"].\"/bitrix/header.php\");\n\$APPLICATION->SetTitle(\"Title\");\n?>";

    private const FOOT = '<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>';

    /**
     * @return array<string,array{0:string,1:string}>
     */
    public static function texts(): array
    {
        return [
            'the text right after the header' => [self::HEAD.'<p>A</p>'.self::FOOT, '<p>A</p>'],
            'nothing between header and footer' => [self::HEAD.self::FOOT, ''],
            'what follows the footer is not the text' => [self::HEAD."\n<p>A</p>\n".self::FOOT."\n<p>After</p>", '<p>A</p>'],
            'a footer block over several lines' => [self::HEAD."\n<p>A</p>\n<?\n// the end\nrequire(\$_SERVER[\"DOCUMENT_ROOT\"].\"/bitrix/footer.php\");\n?>\n<p>After</p>", '<p>A</p>'],
            'no footer: to the end of the file' => [self::HEAD."\n<p>A</p>\n<p>B</p>", "<p>A</p>\n<p>B</p>"],
            'a header block never closed' => ["<?\nrequire(\$_SERVER[\"DOCUMENT_ROOT\"].\"/bitrix/header.php\");\n\$APPLICATION->SetTitle(\"T\");", ''],
            'an unclosed PHP block at the end' => [self::HEAD."<p>A</p>\n<?php echo 1;", '<p>A</p>'],
            'Unicode text' => [self::HEAD.'<p>Привет, 世界 👋</p>'.self::FOOT, '<p>Привет, 世界 👋</p>'],
        ];
    }

    #[DataProvider('texts')]
    public function test_the_text_lies_between_the_header_and_the_footer(string $source, string $content): void
    {
        $file = StaticPageFile::parse($source);

        $this->assertTrue($file->isPage);
        $this->assertSame($content, $file->content);
    }

    public function test_a_file_without_the_header_is_not_a_page(): void
    {
        $file = StaticPageFile::parse("<?\n\$APPLICATION->SetTitle(\"Script\");\n?><p>A</p>");

        $this->assertFalse($file->isPage);
        $this->assertSame('', $file->title);
        $this->assertSame([], $file->properties);
        $this->assertSame('', $file->content);
        $this->assertSame([], $file->components);
    }

    public function test_the_components_are_named_once_each(): void
    {
        $component = fn (string $name): string => "<?\$APPLICATION->IncludeComponent(\n  \"{$name}\", \"\", []);?>";
        $file = StaticPageFile::parse(self::HEAD.'<p>A</p>'.$component('bitrix:news.list').$component('bitrix:menu').$component('bitrix:news.list').self::FOOT);

        $this->assertSame(['bitrix:news.list', 'bitrix:menu'], $file->components);
        $this->assertSame('<p>A</p>', $file->content);
    }

    public function test_the_property_names_are_lower_case_in_any_alphabet(): void
    {
        $file = StaticPageFile::parse("<?\nrequire(\"/bitrix/header.php\");\n"
            ."\$APPLICATION->SetPageProperty(\"DESCRIPTION\", \"Who we are\");\n"
            ."\$APPLICATION->SetDirProperty('ОПИСАНИЕ', 'Кто мы');\n"
            .'?>Text');

        $this->assertSame(['description' => 'Who we are', 'описание' => 'Кто мы'], $file->properties);
    }

    public function test_a_property_call_with_one_argument_is_skipped_and_the_others_are_read(): void
    {
        $file = StaticPageFile::parse("<?\nrequire(\"/bitrix/header.php\");\n"
            ."\$APPLICATION->SetPageProperty(\"title\");\n"
            ."\$APPLICATION->SetDirProperty('keywords');\n"
            ."\$APPLICATION->SetPageProperty(\"description\", \"Who we are\");\n"
            .'?>Text');

        $this->assertTrue($file->isPage);
        $this->assertSame(['description' => 'Who we are'], $file->properties);
        $this->assertSame('Text', $file->content);
    }

    /**
     * @return array<string,array{0:string,1:string}>
     */
    public static function literals(): array
    {
        return [
            'double quotes take escapes' => ['"Line\\tTab"', "Line\tTab"],
            'single quotes keep a backslash-n' => ["'C:\\new'", 'C:\\new'],
            'single quotes: an escaped quote' => ["'It\\'s'", "It's"],
            'single quotes: an escaped backslash' => ["'a\\\\b'", 'a\\b'],
            'double quotes: an escaped quote' => ['"Say \\"hi\\""', 'Say "hi"'],
            'empty' => ["''", ''],
        ];
    }

    #[DataProvider('literals')]
    public function test_the_title_is_read_as_php_would(string $literal, string $title): void
    {
        $file = StaticPageFile::parse("<?\nrequire(\"/bitrix/header.php\");\n\$APPLICATION->SetTitle({$literal});\n?>Text");

        $this->assertSame($title, $file->title);
    }

    /**
     * @return array<string,array{0:string,1:?string}>
     */
    public static function encodings(): array
    {
        return [
            'UTF-8, found by itself' => ['UTF-8', null],
            'UTF-8, given' => ['UTF-8', 'UTF-8'],
            'UTF-8, given in lower case' => ['UTF-8', 'utf-8'],
            'windows-1251, found by itself' => ['Windows-1251', null],
            'windows-1251, given' => ['Windows-1251', 'Windows-1251'],
            'windows-1251, given by another name' => ['Windows-1251', 'CP1251'],
        ];
    }

    #[DataProvider('encodings')]
    public function test_the_file_is_read_in_its_encoding(string $stored, ?string $given): void
    {
        $source = "<?\nrequire(\"/bitrix/header.php\");\n\$APPLICATION->SetTitle(\"Контакты\");\n?><p>Минск</p>";

        $file = StaticPageFile::parse((string) mb_convert_encoding($source, $stored, 'UTF-8'), $given);

        $this->assertSame('Контакты', $file->title);
        $this->assertSame('<p>Минск</p>', $file->content);
    }

    /**
     * @return array<string,array{0:string,1:string}>
     */
    public static function sections(): array
    {
        return [
            'double quotes' => ['<? $sSectionName = "About the company"; ?>', 'About the company'],
            'single quotes over lines' => ["<?\n\$sSectionName\n  = 'Services';\n?>", 'Services'],
            'an escaped quote' => ['<? $sSectionName = "The \\"best\\""; ?>', 'The "best"'],
            'Cyrillic' => ['<? $sSectionName = "О компании"; ?>', 'О компании'],
            'no name' => ['<? $arDirProperties = []; ?>', ''],
            'a name that is not a string' => ['<? $sSectionName = $name; ?>', ''],
            'empty file' => ['', ''],
        ];
    }

    #[DataProvider('sections')]
    public function test_a_folder_is_named_in_its_section_file(string $source, string $name): void
    {
        $file = tempnam(sys_get_temp_dir(), 'atrium-section');
        file_put_contents($file, $source);

        try {
            $this->assertSame($name, StaticPageFile::sectionName($file));
        } finally {
            unlink($file);
        }
    }

    public function test_a_section_file_in_windows_1251(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'atrium-section');
        file_put_contents($file, (string) mb_convert_encoding('<? $sSectionName = "О компании"; ?>', 'Windows-1251', 'UTF-8'));

        try {
            $this->assertSame('О компании', StaticPageFile::sectionName($file));
            $this->assertSame('О компании', StaticPageFile::sectionName($file, 'windows-1251'));
        } finally {
            unlink($file);
        }
    }
}
