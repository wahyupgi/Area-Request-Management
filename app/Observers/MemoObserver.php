<?php

namespace App\Observers;

use App\Models\Memo;
use App\Jobs\SyncMemoToCsv;

class MemoObserver
{
    /**
     * Handle the Memo "saved" event.
     * This covers both created and updated events.
     */
    public function saved(Memo $memo): void
    {
        SyncMemoToCsv::dispatch();
    }
}
