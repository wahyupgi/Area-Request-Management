<?php

namespace App\Features\Dashboard\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Memo;
use App\Models\MemoTemplate;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReportController extends Controller
{
    /**
     * Show the GA Report page (AM only).
     */
    public function gaReport(Request $request)
    {
        $user = auth()->user();

        if (! $user->isAM()) {
            abort(403);
        }

        // GA templates for filter dropdown
        $gaTemplates = MemoTemplate::where('category', 'GA')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        // Branches visible to this AM (that have GA memos)
        $branches = Branch::whereHas('memos', function ($q) use ($user) {
            $q->where('area_manager_id', $user->id)
              ->whereHas('template', fn ($t) => $t->where('category', 'GA'));
        })->orderBy('name')->get(['id', 'name']);

        // Base query: GA memos for this AM
        $query = Memo::with([
            'template:id,name,category',
            'branch:id,name',
            'creator:id,name',
        ])
            ->select([
                'id', 'code', 'title', 'template_id', 'branch_id',
                'created_by', 'area_manager_id', 'status',
                'submitted_at', 'updated_at', 'field_values',
            ])
            ->where('area_manager_id', $user->id)
            ->where('status', 'approved')
            ->whereHas('template', fn ($t) => $t->where('category', 'GA'));

        // Filters

        if ($request->filled('template_id') && is_numeric($request->template_id)) {
            $query->where('template_id', $request->template_id);
        }
        if ($request->filled('branch_id') && is_numeric($request->branch_id)) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('submitted_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('submitted_at', '<=', $request->date_to);
        }

        $memos = $query->orderByDesc('submitted_at')->paginate(25)->withQueryString();

        return Inertia::render('Dashboard/AM', [
            'reportMode'  => true,
            'gaReport'    => $memos,
            'gaTemplates' => $gaTemplates,
            'branches'    => $branches,
            'filters'     => $request->only(['status', 'template_id', 'branch_id', 'date_from', 'date_to']),
        ]);
    }

    /**
     * Export GA memos as a downloadable CSV.
     */
    public function exportGaReport(Request $request)
    {
        $user = auth()->user();

        if (! $user->isAM()) {
            abort(403);
        }

        $query = Memo::with([
            'template:id,name,category',
            'branch:id,name',
            'creator:id,name',
        ])
            ->select([
                'id', 'code', 'title', 'template_id', 'branch_id',
                'created_by', 'area_manager_id', 'status',
                'submitted_at', 'updated_at', 'field_values',
            ])
            ->where('area_manager_id', $user->id)
            ->where('status', 'approved')
            ->whereHas('template', fn ($t) => $t->where('category', 'GA'));


        if ($request->filled('template_id') && is_numeric($request->template_id)) {
            $query->where('template_id', $request->template_id);
        }
        if ($request->filled('branch_id') && is_numeric($request->branch_id)) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('submitted_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('submitted_at', '<=', $request->date_to);
        }

        $memos = $query->orderByDesc('submitted_at')->get();
        $fileName = 'Laporan_GA_' . now()->format('Ymd_His') . '.xls';

        $headers = [
            'Content-Type'        => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ];

        $html = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
        $html .= '<head><meta charset="UTF-8"></head><body>';
        $html .= '<table border="0" style="border-collapse: collapse;">';
        
        // ── Empty Space at Top ──
        $html .= '<tr><td></td><td colspan="12" style="height: 20px;"></td></tr>';
        
        // ── TANDA TERIMA GA BLOCK ──
        $html .= '<tr>';
        $html .= '<td style="width: 20px;"></td>'; // Left margin column
        $html .= '<td rowspan="3" colspan="4" style="border: 1px solid #000000; text-align: center; vertical-align: middle; font-weight: bold; color: #344f82; font-size: 16px;">TANDA TERIMA GA</td>';
        $html .= '<td colspan="4" style="border: 1px solid #000000; text-align: center;">Diketahui oleh,</td>';
        $html .= '<td colspan="4" style="border: 1px solid #000000; text-align: center;">Diketahui oleh,</td>';
        $html .= '</tr>';
        $html .= '<tr>';
        $html .= '<td></td>';
        $html .= '<td colspan="4" style="border: 1px solid #000000; height: 65px;"></td>';
        $html .= '<td colspan="4" style="border: 1px solid #000000; height: 65px;"></td>';
        $html .= '</tr>';
        $html .= '<tr>';
        $html .= '<td></td>';
        $html .= '<td colspan="4" style="border: 1px solid #000000; text-align: center;">Manager GA</td>';
        $html .= '<td colspan="4" style="border: 1px solid #000000; text-align: center;">Area Manager</td>';
        $html .= '</tr>';
        
        $html .= '<tr><td></td><td colspan="12" style="height: 20px;"></td></tr>'; // Empty row
        
        // Table Headers
        $html .= '<tr style="font-weight:bold;">';
        $html .= '<td></td>'; // Left margin column
        $columns = ['No', 'PIC', 'AM', 'Tanggal Pengajuan', 'KC (Pembuat)', 'Cabang', 'Kategori', 'Rincian Permasalahan', 'QTY', 'Keterangan', 'Status', 'Tanggal Penyerahan Memo Ke GA'];
        foreach ($columns as $col) {
            $html .= '<td style="border: 1px solid #000000; padding: 4px; background-color: #344f82; color: #ffffff; text-align: center; vertical-align: middle;">' . $col . '</td>';
        }
        $html .= '</tr>';

        // Data Rows
        foreach ($memos as $index => $memo) {
            $fv = is_array($memo->field_values) ? $memo->field_values : [];
            $items = (isset($fv['items']) && is_array($fv['items'])) ? $fv['items'] : [$fv];

            $extractValue = function ($item, $keys, $isQty = false) {
                foreach ($keys as $k) {
                    if (isset($item[$k]) && $item[$k] !== '') {
                        $val = $item[$k];
                        if (is_array($val)) {
                            if ($isQty) {
                                $sum = 0;
                                $hasVal = false;
                                foreach ($val as $row) {
                                    if (is_array($row)) {
                                        foreach (array_values($row) as $v) {
                                            $num = (float) filter_var($v, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                                            if ($num > 0 || is_numeric(str_replace(',', '.', $v))) {
                                                $sum += $num;
                                                $hasVal = true;
                                            }
                                        }
                                    } else {
                                        $num = (float) filter_var($row, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                                        if ($num > 0 || is_numeric(str_replace(',', '.', $row))) {
                                            $sum += $num;
                                            $hasVal = true;
                                        }
                                    }
                                }
                                return $hasVal ? (string)$sum : '-';
                            }
                            return collect($val)->map(fn($row) => is_array($row) ? implode(', ', array_values($row)) : $row)->implode(' | ');
                        }
                        if ($isQty) {
                            $num = (float) filter_var($val, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                            return (string)$num;
                        }
                        return $val;
                    }
                }
                foreach ($item as $val) {
                    if (is_array($val)) {
                        $tableVals = [];
                        foreach ($val as $row) {
                            if (is_array($row)) {
                                foreach ($keys as $k) {
                                    if (isset($row[$k]) && $row[$k] !== '') {
                                        $tableVals[] = $row[$k];
                                        break;
                                    }
                                }
                            }
                        }
                        if (!empty($tableVals)) {
                            if ($isQty) {
                                $sum = 0;
                                foreach ($tableVals as $v) {
                                    $num = (float) filter_var($v, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                                    $sum += $num;
                                }
                                return (string)$sum;
                            }
                            return implode(', ', $tableVals);
                        }
                    }
                }
                return '-';
            };

            $namaBarangArr = [];
            $qtyArr = [];
            $keteranganArr = [];

            foreach ($items as $item) {
                if (!is_array($item)) continue;

                $nb = $extractValue($item, ['nama_barang', 'permintaan', 'rincian']);
                if ($nb !== '-') $namaBarangArr[] = $nb;

                $q = $extractValue($item, ['jumlah', 'qty', 'jumlah_qty'], true);
                if ($q !== '-') $qtyArr[] = $q;

                $ket = $extractValue($item, ['keterangan', 'catatan', 'alasan']);
                if ($ket !== '-') $keteranganArr[] = $ket;
            }

            $namaBarang = empty($namaBarangArr) ? '-' : implode(' • ', $namaBarangArr);
            $qty = empty($qtyArr) ? '-' : implode(' • ', $qtyArr);
            $keterangan = empty($keteranganArr) ? '-' : implode(' • ', $keteranganArr);

            $statusLabel = match ($memo->status) {
                'approved'  => 'Disetujui',
                'rejected'  => 'Ditolak',
                'submitted' => 'Menunggu',
                default     => 'Draft',
            };

            $submittedAt = $memo->submitted_at ? $memo->submitted_at->locale('id')->isoFormat('D MMM YYYY') : '-';
            $penyerahanAt = $memo->status === 'approved' ? ($memo->updated_at ? $memo->updated_at->locale('id')->isoFormat('D MMM YYYY') : '-') : '-';

            $baseStyle = 'border: 1px solid #000000; padding: 4px; vertical-align: top;';
            $tdCenter  = 'style="' . $baseStyle . ' text-align: center;"';
            $tdLeft    = 'style="' . $baseStyle . ' text-align: left;"';

            $html .= '<tr>';
            $html .= '<td></td>'; // Left margin column
            $html .= '<td ' . $tdCenter . '>' . ($index + 1) . '</td>';
            $html .= '<td ' . $tdCenter . '>GA</td>';
            $html .= '<td ' . $tdLeft . '>' . htmlspecialchars($user->name) . '</td>';
            $html .= '<td ' . $tdCenter . '>' . $submittedAt . '</td>';
            $html .= '<td ' . $tdLeft . '>' . htmlspecialchars(optional($memo->creator)->name ?? '-') . '</td>';
            $html .= '<td ' . $tdLeft . '>' . htmlspecialchars(optional($memo->branch)->name ?? '-') . '</td>';
            $html .= '<td ' . $tdLeft . '>' . htmlspecialchars(optional($memo->template)->name ?? '-') . '</td>';
            $html .= '<td ' . $tdLeft . '>' . htmlspecialchars($namaBarang) . '</td>';
            $html .= '<td ' . $tdCenter . '>' . htmlspecialchars($qty) . '</td>';
            $html .= '<td ' . $tdLeft . '>' . htmlspecialchars($keterangan) . '</td>';
            $html .= '<td ' . $tdCenter . '>' . $statusLabel . '</td>';
            $html .= '<td ' . $tdCenter . '>' . $penyerahanAt . '</td>';
            $html .= '</tr>';
        }

        $html .= '</table></body></html>';

        return response($html, 200, $headers);
    }
}
