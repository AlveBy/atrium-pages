<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Pages\Models;

use Atrium\Admin\Actions\CreateFromRecord;
use Atrium\Admin\Cache\CachesRecords;
use Atrium\Admin\Columns\BooleanColumn;
use Atrium\Admin\Columns\DateColumn;
use Atrium\Admin\Columns\TextColumn;
use Atrium\Admin\Contracts\Administrable;
use Atrium\Admin\Contracts\Cacheable;
use Atrium\Admin\Contracts\HasSeoFields;
use Atrium\Admin\Contracts\Previewable;
use Atrium\Admin\Contracts\Sitemapable;
use Atrium\Admin\Fields\CheckboxField;
use Atrium\Admin\Fields\ContentField;
use Atrium\Admin\Fields\LayoutField;
use Atrium\Admin\Fields\NumberField;
use Atrium\Admin\Fields\SelectField;
use Atrium\Admin\Fields\TextField;
use Atrium\Admin\Fields\TreeSelectField;
use Atrium\Admin\Filters\BooleanFilter;
use Atrium\Admin\Support\PublicUrl;
use Atrium\Admin\Templates\OwnsTemplateColumn;
use Atrium\Admin\Traits\BelongsToSite;
use Atrium\Admin\Traits\HasAdminSeo;
use Atrium\Admin\Traits\HasAdminTabs;
use Atrium\Admin\Traits\InteractsWithAdmin;
use Atrium\Admin\Traits\Translatable;
use Atrium\Pages\Actions\ChangeVisibility;
use Atrium\Pages\Fields\PageCodeField;
use Atrium\Pages\PageTree;
use Atrium\Pages\PageView;
use Atrium\Pages\Templates;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

/**
 * A static page of a site: about us, contacts, delivery terms. Pages make a
 * tree, and a page opens at the path of its codes from the root of the site:
 * /about/team. The name and the text are written per language, the code is
 * one for all of them (/about, /de/about).
 *
 * @property int $id
 * @property int|null $site_id
 * @property int|null $parent_id
 * @property string $code
 * @property string $name
 * @property string|null $content
 * @property array<array-key,mixed>|null $layout
 * @property string $template
 * @property bool $active
 * @property int $sort
 * @property string|null $source
 */
class Page extends Model implements Administrable, Cacheable, HasSeoFields, OwnsTemplateColumn, Previewable, Sitemapable
{
    use BelongsToSite, CachesRecords, HasAdminSeo, HasAdminTabs, InteractsWithAdmin, Translatable;

    protected $table = 'pages';

    /** @var array<int,string> */
    protected array $translatable = ['name', 'content'];

    protected $fillable = ['site_id', 'parent_id', 'code', 'name', 'content', 'layout', 'template', 'active', 'sort', 'source'];

    protected $attributes = [
        'template' => Templates::DEFAULT,
        'active' => true,
        'sort' => 500,
    ];

    protected function casts(): array
    {
        return ['active' => 'boolean', 'sort' => 'integer', 'parent_id' => 'integer', 'layout' => 'array'];
    }

    protected static function booted(): void
    {
        $forget = static function (): void {
            app(PageTree::class)->flush();
            PublicUrl::forgetMemo();
        };

        static::saved($forget);
        static::deleted($forget);

        // Children of a deleted page move up to its parent rather than
        // vanish from the site with no page above them.
        static::deleting(static function (Page $page): void {
            static::query()->where('parent_id', $page->id)->update(['parent_id' => $page->parent_id]);
        });
    }

    /**
     * @return BelongsTo<self,$this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<self,$this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public static function getAdminLabel(): string
    {
        return __('atrium-pages::pages.module');
    }

    public static function getAdminCreateLabel(): string
    {
        return __('atrium-pages::pages.create');
    }

    public static function getAdminRoutePrefix(): string
    {
        return 'pages';
    }

    public static function getDefaultSortColumn(): string
    {
        return 'sort';
    }

    public static function getDefaultSortDirection(): string
    {
        return 'asc';
    }

    /**
     * The template is a column of the page, not a row of the record
     * templates: the core leaves the field to the form below.
     */
    public static function templateColumn(): string
    {
        return 'template';
    }

    public static function getAdminTabs(): array
    {
        return [
            ['name' => 'params', 'label' => __('atrium::fields.tabs.main')],
            ['name' => 'seo', 'label' => __('atrium::fields.tabs.seo')],
        ];
    }

    public static function getAdministrationFields(): array
    {
        return [
            CheckboxField::make('active')->label(__('atrium::fields.standard.active'))->default(true),
            TextField::make('name')->label(__('atrium-pages::pages.fields.name'))->required()->maxLength(255),
            PageCodeField::make('code')->label(__('atrium-pages::pages.fields.code'))->from('name')->required()
                ->hint(__('atrium-pages::pages.hints.code')),
            TreeSelectField::make('parent_id')->label(__('atrium-pages::pages.fields.parent'))->model(self::class)
                ->rootLabel(__('atrium-pages::pages.root')),
            SelectField::make('template')->label(__('atrium-pages::pages.fields.template'))
                ->options(Templates::options())->default(Templates::DEFAULT)->required(),
            NumberField::make('sort')->label(__('atrium::fields.standard.sort'))->default(500),
            ContentField::make('content')->label(__('atrium-pages::pages.fields.content'))->defaultMode('html')->ai(),
            LayoutField::make('layout')->label(__('atrium-pages::pages.fields.layout'))->hint(__('atrium-pages::pages.hints.layout')),
        ];
    }

    /**
     * "Subpage" on a page in the list and on its form: a new page with this
     * one as its parent.
     */
    public static function getAdministrationActions(): array
    {
        return [
            CreateFromRecord::make('subpage')->label(__('atrium-pages::pages.actions.subpage'))->fill(['parent_id' => 'id']),
        ];
    }

    /**
     * Over the pages checked in the list: show them on the site or hide them.
     *
     * @return array<int,ChangeVisibility>
     */
    public static function getAdministrationBulkActions(): array
    {
        return [
            ChangeVisibility::make('show')->label(__('atrium-pages::pages.actions.show'))->to(true),
            ChangeVisibility::make('hide')->label(__('atrium-pages::pages.actions.hide'))->to(false),
        ];
    }

    /**
     * Shown or hidden pages only: the filter above the list.
     */
    public static function getAdministrationFilters(): array
    {
        return [BooleanFilter::make('active')->label(__('atrium::fields.standard.active'))];
    }

    public static function getAdministrationListColumns(): array
    {
        return [
            TextColumn::make('id')->label(__('atrium::fields.standard.id'))->sortable(),
            TextColumn::make('name')->label(__('atrium-pages::pages.fields.name'))->sortable()->searchable(),
            TextColumn::make('code')->label(__('atrium-pages::pages.fields.code'))->sortable()->searchable(),
            TextColumn::make('sort')->label(__('atrium::fields.standard.sort'))->sortable(),
            BooleanColumn::make('active')->label(__('atrium::fields.standard.active')),
            DateColumn::make('updated_at')->label(__('atrium-pages::pages.fields.updated_at'))->sortable(),
        ];
    }

    /**
     * Two pages under one parent cannot share a code: they would have one
     * address. Checked here because the rule depends on the chosen parent.
     *
     * @param  array<string,mixed>  $data
     * @return array<string,mixed>
     */
    public function transformAdminData(array $data): array
    {
        $code = (string) ($data['code'] ?? $this->code);
        $parent = array_key_exists('parent_id', $data) ? $data['parent_id'] : $this->parent_id;

        $taken = static::query()
            ->where('code', $code)
            ->when($parent, fn (Builder $query) => $query->where('parent_id', $parent), fn (Builder $query) => $query->whereNull('parent_id'))
            ->when($this->exists, fn (Builder $query) => $query->whereKeyNot($this->getKey()))
            ->exists();

        if ($taken) {
            throw ValidationException::withMessages(['code' => __('atrium-pages::pages.errors.code_taken')]);
        }

        return $data;
    }

    public function adminPreview(string $language): Renderable
    {
        return app(PageView::class)->make($this, $language);
    }

    /**
     * @return Builder<static>
     */
    public static function sitemapQuery(): Builder
    {
        return static::query()->where('active', true);
    }

    public function sitemapUrl(string $language): ?string
    {
        return app(PageTree::class)->url($this, $language);
    }

    /**
     * The page has no preview text: the description comes from its own text.
     */
    protected function getDefaultSeoDescription(): string
    {
        $template = $this->getSeoDescriptionTemplate();

        if ($template) {
            return $this->processSeoTemplate($template);
        }

        return self::excerpt((string) $this->content);
    }
}
