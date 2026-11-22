<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Pages\Actions;

use Atrium\Admin\Actions\ActionResult;
use Atrium\Admin\Actions\BulkAction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Shows or hides the selected pages on the site. Each page is saved on its
 * own, so the menus and the cache learn about it as from the form.
 */
final class ChangeVisibility extends BulkAction
{
    private bool $visible = true;

    public function to(bool $visible): static
    {
        $this->visible = $visible;

        return $this;
    }

    public function handle(Collection $records): ActionResult
    {
        $records->each(fn (Model $page): bool => $page->update(['active' => $this->visible]));

        return ActionResult::success(trans_choice(
            $this->visible ? 'atrium-pages::pages.actions.shown' : 'atrium-pages::pages.actions.hidden',
            $records->count(),
        ));
    }
}
