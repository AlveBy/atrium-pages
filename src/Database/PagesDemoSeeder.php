<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Pages\Database;

use Atrium\Admin\Modules\DemoSeeder;
use Atrium\Admin\Support\Sites;
use Atrium\Pages\Models\Page;

/**
 * The static pages of the demo shop on the default site, in English, German
 * and Russian: "About us" with two subpages listed as cards, delivery and
 * contacts. The German and Russian texts show once those languages are on
 * (the News demo adds them).
 */
final class PagesDemoSeeder extends DemoSeeder
{
    public function run(): bool
    {
        if (Page::query()->withoutGlobalScopes()->exists()) {
            return false;
        }

        $site = app(Sites::class)->default();

        $about = $this->page($site->id, null, 'about', 100, 'children', [
            'en' => ['About us', '<p>We have sold phones, laptops and headphones since 2009, in two stores and online.</p>'],
            'de' => ['Über uns', '<p>Seit 2009 verkaufen wir Smartphones, Laptops und Kopfhörer, in zwei Filialen und online.</p>'],
            'ru' => ['О компании', '<p>С 2009 года продаём телефоны, ноутбуки и наушники — в двух магазинах и на сайте.</p>'],
        ]);

        $this->page($site->id, $about->id, 'team', 100, 'default', [
            'en' => ['Our team', '<p>Twelve people: sales staff in the stores, two repair technicians and the team that packs your orders.</p>'],
            'de' => ['Unser Team', '<p>Zwölf Leute: das Verkaufsteam in den Filialen, zwei Techniker für Reparaturen und das Team, das Ihre Bestellungen packt.</p>'],
            'ru' => ['Команда', '<p>Двенадцать человек: продавцы в магазинах, два мастера по ремонту и те, кто собирает ваши заказы.</p>'],
        ]);

        $this->page($site->id, $about->id, 'history', 200, 'default', [
            'en' => ['Our history', '<p>The first store opened on Market Street in 2009; the second, by the river, in 2015; the online shop followed a year later.</p>'],
            'de' => ['Unsere Geschichte', '<p>Die erste Filiale eröffnete 2009 in der Market Street, die zweite 2015 am Fluss, ein Jahr später kam der Onlineshop dazu.</p>'],
            'ru' => ['История', '<p>Первый магазин открылся на Market Street в 2009 году, второй — у реки в 2015-м, через год заработал интернет-магазин.</p>'],
        ]);

        $this->page($site->id, null, 'delivery', 200, 'default', [
            'en' => ['Delivery and payment', '<p>Delivery in the city takes two to three working days, elsewhere up to a week. Pay by card online, or by card or cash when you pick up the order.</p>'],
            'de' => ['Lieferung und Bezahlung', '<p>Die Lieferung dauert in der Stadt zwei bis drei Werktage, im übrigen Land bis zu einer Woche. Bezahlen Sie online mit Karte oder bei der Abholung mit Karte oder bar.</p>'],
            'ru' => ['Доставка и оплата', '<p>По городу доставляем за два-три рабочих дня, по стране — до недели. Оплатить можно картой на сайте или картой и наличными при получении.</p>'],
        ]);

        $this->page($site->id, null, 'contacts', 300, 'full-width', [
            'en' => ['Contacts', '<p>12 Market Street, Old Town<br>+1 555 0100<br>centre@example.com</p><p>Open daily from 10:00 to 20:00.</p>'],
            'de' => ['Kontakt', '<p>12 Market Street, Altstadt<br>+1 555 0100<br>centre@example.com</p><p>Täglich von 10:00 bis 20:00 Uhr geöffnet.</p>'],
            'ru' => ['Контакты', '<p>12 Market Street, Старый город<br>+1 555 0100<br>centre@example.com</p><p>Работаем ежедневно с 10:00 до 20:00.</p>'],
        ]);

        return true;
    }

    /**
     * @param  array<string,array{0:string,1:string}>  $texts  language => [name, content]
     */
    private function page(int $siteId, ?int $parentId, string $code, int $sort, string $template, array $texts): Page
    {
        $page = new Page(['site_id' => $siteId, 'parent_id' => $parentId, 'code' => $code, 'sort' => $sort, 'template' => $template]);

        foreach ($texts as $language => [$name, $content]) {
            $page->setTranslation('name', $language, $name)->setTranslation('content', $language, $content);
        }

        $page->save();

        return $page;
    }
}
