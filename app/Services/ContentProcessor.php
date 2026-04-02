<?php

namespace App\Services;

use Exception;
use Illuminate\Http\UploadedFile;

class ContentProcessor
{
    protected PdfTextExtractor $pdfExtractor;

    public function __construct(PdfTextExtractor $pdfExtractor)
    {
        $this->pdfExtractor = $pdfExtractor;
    }

    /**
     * Determine how to process the given input (text or PDF)
     */
    public function processAndExtract(string $sourceType, ?string $inputText = null, ?UploadedFile $pdfFile = null): string
    {
        if ($sourceType === 'pdf') {
            if (!$pdfFile) {
                throw new Exception("PDF source selected but no file provided.");
            }
            if (!$this->pdfExtractor->validatePdf($pdfFile)) {
                throw new Exception("Invalid PDF file uploaded.");
            }
            $extracted = $this->cleanText($this->pdfExtractor->extract($pdfFile));
            if (mb_strlen($extracted) < 50) {
                throw new Exception('PDF extraction returned insufficient readable text. Please upload a text-based PDF.');
            }

            return $extracted;
        }

        // Text type
        if (empty($inputText)) {
            throw new Exception("Text source selected but no text provided.");
        }

        $cleaned = $this->cleanText($inputText);
        if (mb_strlen($cleaned) < 50) {
            throw new Exception('Study material must be at least 50 characters after cleaning.');
        }

        return $cleaned;
    }

    /**
     * Clean and normalize text
     */
    public function cleanText(string $text): string
    {
        // Basic sanitization
        $text = trim($text);
        // Replace non-breaking spaces
        $text = str_replace("\xC2\xA0", ' ', $text);
        // Normalize newlines
        $text = preg_replace("/\r\n|\r/", "\n", $text);
        
        return $text;
    }

    /**
     * Chunk text into smaller pieces if needed for AI processing window limits
     * Currently just a scaffold for future improvements
     */
    public function chunkText(string $text, int $maxChunkSize = 10000): array
    {
        if (strlen($text) <= $maxChunkSize) {
            return [$text];
        }

        // Basic character-based chunking (can be improved to sentence/paragraph boundary later)
        return str_split($text, $maxChunkSize);
    }
}
