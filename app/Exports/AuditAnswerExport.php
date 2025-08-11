<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class AuditAnswerExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths, WithEvents, WithDrawings
{
    protected $formattedData;
    protected $auditAnswer;
    protected $signatures;
    protected $grade;
    protected $picName;
    protected $chargeFees;
    protected $rowHeights = [];
    protected $totalRows;

    public function __construct($formattedData, $auditAnswer, $grade, $signatures = null, $chargeFees = null, $picName = null)
    {
        $this->formattedData = $formattedData;
        $this->auditAnswer = $auditAnswer;
        $this->grade = $grade;
        $this->signatures = $signatures;
        $this->chargeFees = $chargeFees;
        $this->picName = $picName;
    }

    public function collection()
    {
        $data = [];

        // Catatan
        $data[] = [
            'Kategori' => 'CATATAN:',
            'Tema' => 'Foto standar dan foto temuan ditampilkan langsung di dalam dokumen Excel.',
            'Standar' => '',
            'Foto Standar' => '',
            'Variabel' => '',
            'Score' => '',
            'Temuan' => '',
            'Foto Temuan' => ''
        ];

        // Baris kosong
        $data[] = [
            'Kategori' => '',
            'Tema' => '',
            'Standar' => '',
            'Foto Standar' => '',
            'Variabel' => '',
            'Score' => '',
            'Temuan' => '',
            'Foto Temuan' => ''
        ];

        $currentRow = 4; // Mulai dari baris 4

        foreach ($this->formattedData as $detail) {
            $temuan = '';
            foreach ($detail['auditees'] as $auditee) {
                $nameKey = isset($auditee['auditee']) ? 'auditee' : 'name';
                $temuan .= $auditee[$nameKey] . ': ' . $auditee['temuan'] . "\n";
            }

            $row = [
                'Kategori' => $detail['kategori'],
                'Tema' => $detail['tema'],
                'Standar' => $detail['standar_variabel'],
                'Foto Standar' => $detail['standar_foto'] ? '(Lihat gambar)' : 'Tidak ada foto',
                'Variabel' => $detail['variabel'],
                'Score' => $detail['score'],
                'Temuan' => $temuan ?: 'Tidak ada temuan',
                'Foto Temuan' => count($detail['images']) > 0 ? '(Lihat gambar)' : 'Tidak ada foto'
            ];

            $data[] = $row;

            // Hitung tinggi baris berdasarkan jumlah gambar
            $baseHeight = 80;
            $imageHeight = 120;
            $imageSpacing = 5;

            // Hitung tinggi untuk foto standar
            $standarImageCount = !empty($detail['list_standar_foto']) ? count($detail['list_standar_foto']) : 0;
            $standarRows = $standarImageCount > 0 ? ceil($standarImageCount / 3) : 0;
            $standarHeight = $standarRows > 0 ? ($imageHeight + $imageSpacing) * $standarRows : 0;

            // Hitung tinggi untuk foto temuan
            $temuanImageCount = count($detail['images']);
            $temuanRows = $temuanImageCount > 0 ? ceil($temuanImageCount / 3) : 0;
            $temuanHeight = $temuanRows > 0 ? ($imageHeight + $imageSpacing) * $temuanRows : 0;

            // Ambil tinggi maksimal antara standar dan temuan
            $maxImageHeight = max($standarHeight, $temuanHeight);
            $this->rowHeights[$currentRow] = max($baseHeight, $maxImageHeight + 20); // +20 untuk padding

            $currentRow++;
        }

        // Total Score
        $data[] = [
            'Kategori' => '',
            'Tema' => '',
            'Standar' => '',
            'Foto Standar' => '',
            'Variabel' => 'Total Score',
            'Score' => $this->auditAnswer->total_score,
            'Temuan' => '',
            'Foto Temuan' => ''
        ];

        // Grade
        $data[] = [
            'Kategori' => '',
            'Tema' => '',
            'Standar' => '',
            'Foto Standar' => '',
            'Variabel' => 'Grade',
            'Score' => $this->grade,
            'Temuan' => '',
            'Foto Temuan' => ''
        ];

        // Charge Fees (jika grade != Diamond)
        if ($this->grade != 'Diamond' && $this->chargeFees) {
            $data[] = [
                'Kategori' => '',
                'Tema' => '',
                'Standar' => '',
                'Foto Standar' => '',
                'Variabel' => '',
                'Score' => '',
                'Temuan' => '',
                'Foto Temuan' => ''
            ];

            $data[] = [
                'Kategori' => 'CHARGE FEES',
                'Tema' => '',
                'Standar' => '',
                'Foto Standar' => '',
                'Variabel' => '',
                'Score' => '',
                'Temuan' => '',
                'Foto Temuan' => ''
            ];

            $data[] = [
                'Kategori' => 'Tarif Denda per Temuan',
                'Tema' => 'Rp ' . number_format($this->chargeFees['feeRate'], 0, ',', '.'),
                'Standar' => '',
                'Foto Standar' => '',
                'Variabel' => '',
                'Score' => '',
                'Temuan' => '',
                'Foto Temuan' => ''
            ];

            $data[] = [
                'Kategori' => 'Total Temuan',
                'Tema' => $this->chargeFees['totalFindings'],
                'Standar' => 'Total Denda',
                'Foto Standar' => 'Rp ' . number_format($this->chargeFees['totalFee'], 0, ',', '.'),
                'Variabel' => '',
                'Score' => '',
                'Temuan' => '',
                'Foto Temuan' => ''
            ];

            $data[] = [
                'Kategori' => 'Denda Tertuduh',
                'Tema' => '',
                'Standar' => '',
                'Foto Standar' => '',
                'Variabel' => '',
                'Score' => '',
                'Temuan' => '',
                'Foto Temuan' => ''
            ];

            foreach ($this->chargeFees['tertuduhDetails'] as $name => $detail) {
                $data[] = [
                    'Kategori' => $name,
                    'Tema' => $detail['dept'] ?? '-',
                    'Standar' => 'Jumlah Temuan: ' . $detail['findings'],
                    'Foto Standar' => 'Denda: Rp ' . number_format($detail['fee'], 0, ',', '.'),
                    'Variabel' => '',
                    'Score' => '',
                    'Temuan' => '',
                    'Foto Temuan' => ''
                ];
            }

            $data[] = [
                'Kategori' => 'Denda PIC Area (50%)',
                'Tema' => $this->picName ?? 'Tidak Ada',
                'Standar' => 'Total Temuan: ' . $this->chargeFees['totalFindings'],
                'Foto Standar' => 'Denda: Rp ' . number_format($this->chargeFees['picAreaFee'], 0, ',', '.'),
                'Variabel' => '',
                'Score' => '',
                'Temuan' => '',
                'Foto Temuan' => ''
            ];

            if (!empty($this->chargeFees['managerDetails'])) {
                $data[] = [
                    'Kategori' => 'Denda Manager (Rp 1.000/temuan)',
                    'Tema' => '',
                    'Standar' => '',
                    'Foto Standar' => '',
                    'Variabel' => '',
                    'Score' => '',
                    'Temuan' => '',
                    'Foto Temuan' => ''
                ];

                foreach ($this->chargeFees['managerDetails'] as $name => $detail) {
                    $data[] = [
                        'Kategori' => $name,
                        'Tema' => $detail['dept'],
                        'Standar' => 'Jumlah Temuan: ' . $detail['findings'],
                        'Foto Standar' => 'Denda: Rp ' . number_format($detail['fee'], 0, ',', '.'),
                        'Variabel' => '',
                        'Score' => '',
                        'Temuan' => '',
                        'Foto Temuan' => ''
                    ];
                }
            }

            if (!empty($this->chargeFees['gmDetails'])) {
                $data[] = [
                    'Kategori' => 'Denda General Manager (Rp 2.000/temuan)',
                    'Tema' => '',
                    'Standar' => '',
                    'Foto Standar' => '',
                    'Variabel' => '',
                    'Score' => '',
                    'Temuan' => '',
                    'Foto Temuan' => ''
                ];

                foreach ($this->chargeFees['gmDetails'] as $name => $detail) {
                    $data[] = [
                        'Kategori' => $name,
                        'Tema' => $detail['dept'],
                        'Standar' => 'Jumlah Temuan: ' . $detail['findings'],
                        'Foto Standar' => 'Denda: Rp ' . number_format($detail['fee'], 0, ',', '.'),
                        'Variabel' => '',
                        'Score' => '',
                        'Temuan' => '',
                        'Foto Temuan' => ''
                    ];
                }
            }
        }

        // Baris kosong
        $data[] = [
            'Kategori' => '',
            'Tema' => '',
            'Standar' => '',
            'Foto Standar' => '',
            'Variabel' => '',
            'Score' => '',
            'Temuan' => '',
            'Foto Temuan' => ''
        ];

        // Header Tanda Tangan
        $data[] = [
            'Kategori' => 'TANDA TANGAN',
            'Tema' => '',
            'Standar' => '',
            'Foto Standar' => '',
            'Variabel' => '',
            'Score' => '',
            'Temuan' => '',
            'Foto Temuan' => ''
        ];

        // Row untuk tanda tangan (empty row)
        $data[] = [
            'Kategori' => '',
            'Tema' => '',
            'Standar' => '',
            'Foto Standar' => '',
            'Variabel' => '',
            'Score' => '',
            'Temuan' => '',
            'Foto Temuan' => ''
        ];

        // Row kosong untuk ruang tanda tangan (labels: Auditor, Fasilitator, Auditee)
        $data[] = [
            'Kategori' => 'Auditor:',
            'Tema' => '',
            'Standar' => 'Fasilitator:',
            'Foto Standar' => '',
            'Variabel' => 'Auditee:',
            'Score' => '',
            'Temuan' => '',
            'Foto Temuan' => ''
        ];

        // Nama penanda tangan
        $data[] = [
            'Kategori' => $this->auditAnswer->auditor->name ?? 'N/A',
            'Tema' => '',
            'Standar' => $this->signatures->manager_name ?? 'N/A',
            'Foto Standar' => '',
            'Variabel' => $this->signatures->auditee_name ?? 'N/A',
            'Score' => '',
            'Temuan' => '',
            'Foto Temuan' => ''
        ];

        // Tanggal tanda tangan
        $data[] = [
            'Kategori' => 'Tanggal: ' . ($this->auditAnswer->created_at ? $this->auditAnswer->created_at->format('d-m-Y') : 'N/A'),
            'Tema' => '',
            'Standar' => 'Tanggal: ' . ($this->auditAnswer->created_at ? $this->auditAnswer->created_at->format('d-m-Y') : 'N/A'),
            'Foto Standar' => '',
            'Variabel' => 'Tanggal: ' . ($this->auditAnswer->created_at ? $this->auditAnswer->created_at->format('d-m-Y') : 'N/A'),
            'Score' => '',
            'Temuan' => '',
            'Foto Temuan' => ''
        ];

        $this->totalRows = count($data);
        return collect($data);
    }

    public function headings(): array
    {
        return [
            'Kategori',
            'Tema',
            'Standar',
            'Foto Standar',
            'Variabel',
            'Score',
            'Temuan',
            'Foto Temuan'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = $sheet->getHighestRow();
        $lastColumn = $sheet->getHighestColumn();

        // Set wrap text untuk semua sel
        $sheet->getStyle('A1:' . $lastColumn . $lastRow)->getAlignment()->setWrapText(true);

        // Set vertical alignment ke top untuk semua sel
        $sheet->getStyle('A1:' . $lastColumn . $lastRow)->getAlignment()->setVertical(Alignment::VERTICAL_TOP);

        // Set border untuk semua sel
        $sheet->getStyle('A1:' . $lastColumn . $lastRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        // Style untuk header
        $sheet->getStyle('A1:' . $lastColumn . '1')->getFont()->setBold(true);
        $sheet->getStyle('A1:' . $lastColumn . '1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFCCCCCC');
        $sheet->getStyle('A1:' . $lastColumn . '1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Style untuk baris catatan
        $sheet->getStyle('A2:H2')->getFont()->setBold(true);
        $sheet->getStyle('A2:H3')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFEEEEEE');

        // Merge cells untuk catatan
        $sheet->mergeCells('B2:H2');

        // Style untuk baris data
        $dataStartRow = 4;
        $dataEndRow = $lastRow - 9; // Adjusted for additional signature rows
        $sheet->getStyle('A' . $dataStartRow . ':' . $lastColumn . $dataEndRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        // Style untuk total score dan grade
        $totalScoreRow = $lastRow - 8;
        $gradeRow = $lastRow - 7;
        $sheet->getStyle('A' . $totalScoreRow . ':' . $lastColumn . $gradeRow)->getFont()->setBold(true);
        $sheet->getStyle('A' . $totalScoreRow . ':' . $lastColumn . $gradeRow)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFDDDDDD');

        // Style untuk header tanda tangan
        $signatureHeaderRow = $lastRow - 5;
        $sheet->getStyle('A' . $signatureHeaderRow)->getFont()->setBold(true);
        $sheet->getStyle('A' . $signatureHeaderRow . ':' . $lastColumn . $signatureHeaderRow)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFDDDDDD');
        $sheet->mergeCells('A' . $signatureHeaderRow . ':H' . $signatureHeaderRow);

        // Style untuk baris tanda tangan
        $signatureRow = $lastRow - 4;
        $sheet->getStyle('A' . $signatureRow)->getFont()->setBold(true);
        $sheet->getStyle('C' . $signatureRow)->getFont()->setBold(true);
        $sheet->getStyle('E' . $signatureRow)->getFont()->setBold(true);

        // Merge cells untuk setiap bagian tanda tangan
        $sheet->mergeCells('A' . $signatureRow . ':B' . $signatureRow);
        $sheet->mergeCells('C' . $signatureRow . ':D' . $signatureRow);
        $sheet->mergeCells('E' . $signatureRow . ':F' . $signatureRow);

        // Merge cells untuk ruang tanda tangan
        $signatureImageRow = $lastRow - 3;
        $sheet->mergeCells('A' . $signatureImageRow . ':B' . $signatureImageRow);
        $sheet->mergeCells('C' . $signatureImageRow . ':D' . $signatureImageRow);
        $sheet->mergeCells('E' . $signatureImageRow . ':F' . $signatureImageRow);
        $sheet->getRowDimension($signatureImageRow)->setRowHeight(100);

        // Merge cells untuk nama tanda tangan
        $nameSignatureRow = $lastRow - 2;
        $sheet->mergeCells('A' . $nameSignatureRow . ':B' . $nameSignatureRow);
        $sheet->mergeCells('C' . $nameSignatureRow . ':D' . $nameSignatureRow);
        $sheet->mergeCells('E' . $nameSignatureRow . ':F' . $nameSignatureRow);

        // Merge cells untuk tanggal tanda tangan
        $dateSignatureRow = $lastRow - 1;
        $sheet->mergeCells('A' . $dateSignatureRow . ':B' . $dateSignatureRow);
        $sheet->mergeCells('C' . $dateSignatureRow . ':D' . $dateSignatureRow);
        $sheet->mergeCells('E' . $dateSignatureRow . ':F' . $dateSignatureRow);

        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 20,  // Kategori
            'B' => 20,  // Tema
            'C' => 30,  // Standar
            'D' => 25,  // Foto Standar
            'E' => 25,  // Variabel
            'F' => 10,  // Score
            'G' => 40,  // Temuan
            'H' => 60,  // Foto Temuan
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $event->sheet->getRowDimension(1)->setRowHeight(25);
                $event->sheet->getRowDimension(2)->setRowHeight(30);
                $event->sheet->getRowDimension(3)->setRowHeight(20);

                // Set row height untuk baris data dari kalkulasi
                foreach ($this->rowHeights as $row => $height) {
                    $event->sheet->getRowDimension($row)->setRowHeight($height);
                }

                // Set default height untuk baris lain
                $lastRow = $event->sheet->getHighestRow();
                for ($i = 4; $i <= $lastRow - 9; $i++) {
                    if (!isset($this->rowHeights[$i])) {
                        $event->sheet->getRowDimension($i)->setRowHeight(50);
                    }
                }

                // Set larger height for signature row
                $signatureImageRow = $lastRow - 3;
                $event->sheet->getRowDimension($signatureImageRow)->setRowHeight(150);

                // Auto-filter untuk header
                $event->sheet->setAutoFilter('A1:H1');

                // Freeze panes
                $event->sheet->freezePane('A4');
            },
        ];
    }

    public function drawings()
    {
        $drawings = [];
        $currentRow = 4;

        foreach ($this->formattedData as $detail) {
            // Tambahkan gambar standar jika ada - dengan grid layout
            if (!empty($detail['list_standar_foto']) && count($detail['list_standar_foto']) > 0) {
                $this->addImagesInGrid($drawings, $detail['list_standar_foto'], 'D', $currentRow, 'Foto Standar');
            }

            // Tambahkan gambar temuan jika ada - dengan grid layout
            if (count($detail['images']) > 0) {
                $this->addImagesInGrid($drawings, $detail['images'], 'H', $currentRow, 'Foto Temuan');
            }

            $currentRow++;
        }

        // Add signature images
        $signatureImageRow = $this->totalRows - 2;

        // Auditor signature
        if ($this->signatures && $this->signatures->auditor_signature) {
            $auditorSignPath = storage_path('app/public/' . $this->signatures->auditor_signature);
            if (file_exists($auditorSignPath)) {
                $drawing = new Drawing();
                $drawing->setName('Tanda Tangan Auditor');
                $drawing->setDescription('Tanda Tangan Auditor');
                $drawing->setPath($auditorSignPath);
                $drawing->setHeight(80);
                $drawing->setWidth(150);
                $drawing->setResizeProportional(true);
                $drawing->setCoordinates('A' . $signatureImageRow);
                $drawing->setOffsetX(10);
                $drawing->setOffsetY(35);
                $drawings[] = $drawing;
            }
        }

        // Manager signature
        if ($this->signatures && $this->signatures->facilitator_signature) {
            $managerSignPath = storage_path('app/public/' . $this->signatures->facilitator_signature);
            if (file_exists($managerSignPath)) {
                $drawing = new Drawing();
                $drawing->setName('Tanda Tangan Fasilitator');
                $drawing->setDescription('Tanda Tangan Fasilitator');
                $drawing->setPath($managerSignPath);
                $drawing->setHeight(80);
                $drawing->setWidth(150);
                $drawing->setResizeProportional(true);
                $drawing->setCoordinates('C' . $signatureImageRow);
                $drawing->setOffsetX(10);
                $drawing->setOffsetY(35);
                $drawings[] = $drawing;
            }
        }

        // Auditee signature
        if ($this->signatures && $this->signatures->auditee_signature) {
            $auditeeSignPath = storage_path('app/public/' . $this->signatures->auditee_signature);
            if (file_exists($auditeeSignPath)) {
                $drawing = new Drawing();
                $drawing->setName('Tanda Tangan Auditee');
                $drawing->setDescription('Tanda Tangan Auditee');
                $drawing->setPath($auditeeSignPath);
                $drawing->setHeight(80);
                $drawing->setWidth(150);
                $drawing->setResizeProportional(true);
                $drawing->setCoordinates('E' . $signatureImageRow);
                $drawing->setOffsetX(10);
                $drawing->setOffsetY(35);
                $drawings[] = $drawing;
            }
        }

        return $drawings;
    }

    /**
     * Menambahkan gambar dalam layout grid (maksimal 3 kolom)
     * 
     * @param array $drawings Array untuk menampung drawing objects
     * @param array $images Array gambar yang akan ditambahkan
     * @param string $startColumn Kolom awal (D atau H)
     * @param int $row Baris tempat gambar akan ditempatkan
     * @param string $imageName Prefix nama gambar
     */
    private function addImagesInGrid(&$drawings, $images, $startColumn, $row, $imageName)
    {
        $imageWidth = 120;
        $imageHeight = 120;
        $columnSpacing = 10; // Jarak antar kolom
        $rowSpacing = 5;     // Jarak antar baris

        // Kolom yang akan digunakan untuk grid (maksimal 3)
        $columns = [$startColumn];

        // Jika ada ruang untuk kolom tambahan, tambahkan kolom berikutnya
        if ($startColumn == 'D') {
            // Untuk foto standar, bisa menggunakan kolom D saja atau expand ke kanan jika diperlukan
            // Kita akan tetap di kolom D tapi dengan offset X yang berbeda
        } elseif ($startColumn == 'H') {
            // Untuk foto temuan, bisa menggunakan kolom H saja dengan offset X yang berbeda
        }

        foreach ($images as $index => $image) {
            $imagePath = storage_path('app/public/' . $image['image_path']);

            if (file_exists($imagePath)) {
                // Hitung posisi dalam grid (maksimal 3 kolom)
                $gridCol = $index % 3;          // 0, 1, 2
                $gridRow = floor($index / 3);   // 0, 0, 0, 1, 1, 1, dst

                // Hitung offset X berdasarkan posisi kolom dalam grid
                $offsetX = 5 + ($gridCol * ($imageWidth + $columnSpacing));

                // Hitung offset Y berdasarkan posisi baris dalam grid
                $offsetY = 5 + ($gridRow * ($imageHeight + $rowSpacing));

                $drawing = new Drawing();
                $drawing->setName($imageName . ' ' . ($index + 1));
                $drawing->setDescription($imageName . ' ' . ($index + 1));
                $drawing->setPath($imagePath);
                $drawing->setHeight($imageHeight);
                $drawing->setWidth($imageWidth);
                $drawing->setResizeProportional(true);
                $drawing->setCoordinates($startColumn . $row);
                $drawing->setOffsetX($offsetX);
                $drawing->setOffsetY($offsetY);

                $drawings[] = $drawing;
            }
        }
    }
}
