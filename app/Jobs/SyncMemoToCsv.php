<?php

namespace App\Jobs;

use App\Models\Memo;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class SyncMemoToCsv implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Get all memos with branch relation
        $memos = Memo::with('branch')->orderBy('id', 'desc')->get();
        
        $csvData = [];
        
        // CSV Headers
        $csvData[] = ['No Memo', 'Cabang', 'Perihal', 'Tanggal', 'Status'];
        
        $sanitizeCell = function ($value): string {
            if ($value === null || $value === '' || $value === '-') {
                return '-';
            }
            $str = (string) $value;
            if (strlen($str) > 1 && in_array($str[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
                return "'" . $str;
            }
            return $str;
        };

        // CSV Rows
        foreach ($memos as $memo) {
            $csvData[] = [
                $sanitizeCell($memo->code),
                $sanitizeCell($memo->branch ? $memo->branch->name : null),
                $sanitizeCell($memo->title),
                $memo->created_at ? $memo->created_at->format('Y-m-d H:i:s') : '-',
                $sanitizeCell($memo->status)
            ];
        }

        // Write to temporary memory stream
        $output = fopen('php://temp', 'r+');
        foreach ($csvData as $row) {
            fputcsv($output, $row);
        }
        rewind($output);
        $content = stream_get_contents($output);
        fclose($output);

        // Save to storage/app/exports/rekap_memo.csv
        Storage::put('exports/rekap_memo.csv', $content);
    }
}
