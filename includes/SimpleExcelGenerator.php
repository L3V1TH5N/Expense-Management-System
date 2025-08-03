<?php
class SimpleExcelGenerator {
    private $data = [];
    private $headers = [];
    private $title = '';
    private $filename = '';
    private $metadata = [];
    private $totals = [];
    private $columnWidths = [];

    public function __construct($title = 'Report', $filename = null) {
        $this->title = $title;
        $this->filename = $filename ?: ($title . '_' . date('Y-m-d_H-i-s') . '.xls');
    }
    
    public function setHeaders($headers) {
        $this->headers = $headers;
        return $this;
    }
    
    public function setData($data) {
        $this->data = $data;
        return $this;
    }
    
    public function setTitle($title) {
        $this->title = $title;
        return $this;
    }
    
    public function setFilename($filename) {
        $this->filename = $filename;
        return $this;
    }
    
    public function setMetadata($metadata) {
        $this->metadata = $metadata;
        return $this;
    }
    
    public function setTotals($totals) {
        $this->totals = $totals;
        return $this;
    }
    
    public function setColumnWidths($widths) {
        $this->columnWidths = $widths;
        return $this;
    }
    
    public function download() {
        // Clean output buffer
        while (ob_get_level()) ob_end_clean();
        
        // Headers for Excel download
        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $this->filename . '"');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Expires: 0');
        
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"' . "\n";
        echo ' xmlns:o="urn:schemas-microsoft-com:office:office"' . "\n";
        echo ' xmlns:x="urn:schemas-microsoft-com:office:excel"' . "\n";
        echo ' xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"' . "\n";
        echo ' xmlns:html="http://www.w3.org/TR/REC-html40">' . "\n";
        
        // Styles
        echo '<Styles>' . "\n";
        echo '<Style ss:ID="Default" ss:Name="Normal">' . "\n";
        echo '<Alignment ss:Vertical="Center"/>' . "\n";
        echo '</Style>' . "\n";
        
        echo '<Style ss:ID="Title">' . "\n";
        echo '<Font ss:Bold="1" ss:Size="11"/>' . "\n";
        echo '<Alignment ss:Horizontal="Center" ss:Vertical="Center"/>' . "\n";
        echo '</Style>' . "\n";
        
        echo '<Style ss:ID="Header">' . "\n";
        echo '<Font ss:Bold="1" ss:Color="#000000"/>' . "\n";
        echo '<Alignment ss:Horizontal="Center" ss:Vertical="Center"/>' . "\n";
        echo '</Style>' . "\n";
        
        echo '<Style ss:ID="Currency">' . "\n";
        echo '<NumberFormat ss:Format="&quot;₱&quot;#,##0.00"/>' . "\n";
        echo '<Alignment ss:Horizontal="Right" ss:Vertical="Center"/>' . "\n";
        echo '</Style>' . "\n";
        
        echo '<Style ss:ID="Date">' . "\n";
        echo '<NumberFormat ss:Format="mm/dd/yyyy;@"/>' . "\n";
        echo '<Alignment ss:Horizontal="Center" ss:Vertical="Center"/>' . "\n";
        echo '</Style>' . "\n";
        
        echo '<Style ss:ID="Total">' . "\n";
        echo '<Font ss:Bold="1"/>' . "\n";
        echo '<Alignment ss:Horizontal="Right" ss:Vertical="Center"/>' . "\n";
        echo '</Style>' . "\n";
        
        echo '<Style ss:ID="Center">' . "\n";
        echo '<Alignment ss:Horizontal="Center" ss:Vertical="Center"/>' . "\n";
        echo '</Style>' . "\n";
        
        echo '<Style ss:ID="Left">' . "\n";
        echo '<Alignment ss:Horizontal="Left" ss:Vertical="Center"/>' . "\n";
        echo '</Style>' . "\n";
        
        echo '<Style ss:ID="Right">' . "\n";
        echo '<Alignment ss:Horizontal="Right" ss:Vertical="Center"/>' . "\n";
        echo '</Style>' . "\n";
        echo '</Styles>' . "\n";
        
        // Worksheet
        echo '<Worksheet ss:Name="Report">' . "\n";
        
        // Set column widths if specified
        if (!empty($this->columnWidths)) {
            echo '<Table>' . "\n";
            foreach ($this->columnWidths as $width) {
                echo '<Column ss:Width="' . $width . '"/>' . "\n";
            }
        } else {
            echo '<Table>' . "\n";
        }
        
        // Data rows
        foreach ($this->data as $row) {
            echo '<Row>' . "\n";
            
            // Determine if this is a title row that should be merged
            $is_title_row = false;
            $empty_cell_count = 0;
            
            foreach ($row as $cell) {
                if ($cell === '') {
                    $empty_cell_count++;
                }
            }
            
            $is_title_row = ($empty_cell_count > 0 && $empty_cell_count >= count($row) - 1);
            
            if ($is_title_row) {
                // This is a title row that should be merged
                $mergeAcross = count($row) - 1;
                $cell_value = $row[0];
                
                // Special case: fund type row should not be merged
                if (strpos($cell_value, 'Fund Type:') !== false || count($row) === 1) {
                    echo '<Cell ss:StyleID="Left">';
                    echo '<Data ss:Type="String">' . htmlspecialchars($cell_value) . '</Data>';
                    echo '</Cell>' . "\n";
                } else {
                    echo '<Cell ss:StyleID="Title" ss:MergeAcross="' . $mergeAcross . '">';
                    echo '<Data ss:Type="String">' . htmlspecialchars($cell_value) . '</Data>';
                    echo '</Cell>' . "\n";
                }
            } else {
                // Check if this is a header row
                $is_header_row = false;
                foreach ($row as $cell) {
                    if (in_array($cell, $this->headers)) {
                        $is_header_row = true;
                        break;
                    }
                }
                
                // Normal data row
                foreach ($row as $i => $cell) {
                    $style = '';
                    $type = 'String';
                    
                    if ($is_header_row) {
                        $style = 'ss:StyleID="Header"';
                    } elseif ($i === 0 && preg_match('/^\d{2}\/\d{2}\/\d{2}$/', $cell)) {
                        $style = 'ss:StyleID="Date"';
                        $type = 'DateTime';
                        $cell = date('Y-m-d', strtotime($cell)) . 'T00:00:00.000';
                    } elseif (($i === 3 || $i === 6) && is_numeric(str_replace(',', '', $cell))) {
                        $style = 'ss:StyleID="Currency"';
                        $type = 'Number';
                        $cell = str_replace(',', '', $cell);
                    } elseif ($i === count($row) - 1 && strpos($cell, 'GRAND TOTAL') !== false) {
                        $style = 'ss:StyleID="Total"';
                    } elseif ($i === 2 && !$is_header_row) { // Payee/Employee name column
                        $style = 'ss:StyleID="Left"';
                    } elseif (($i === 1 || $i === 4 || $i === 5 || $i === 6) && !$is_header_row) { // Check number, Liquidated, Returned, Remarks
                        $style = 'ss:StyleID="Center"';
                    } else {
                        $style = 'ss:StyleID="Default"';
                    }
                    
                    echo '<Cell ' . $style . '>';
                    echo '<Data ss:Type="' . $type . '">' . htmlspecialchars($cell) . '</Data>';
                    echo '</Cell>' . "\n";
                }
            }
            
            echo '</Row>' . "\n";
        }
        
        echo '</Table>' . "\n";
        echo '</Worksheet>' . "\n";
        echo '</Workbook>' . "\n";
        exit;
    }
}