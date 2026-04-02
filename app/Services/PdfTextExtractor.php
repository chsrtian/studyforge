<?php

namespace App\Services;

use Exception;
use Illuminate\Http\UploadedFile;

class PdfTextExtractor
{
    protected ?object $parser = null;

    public function __construct()
    {
        // Lazily initialize parser so text-only flows do not crash if dependency is missing.
        $this->parser = null;
    }

    /**
     * Extract text from a given PDF file.
     */
    public function extract(UploadedFile $pdf): string
    {
        try {
            $parser = $this->getParser();
            $pdfDocument = $parser->parseFile($pdf->getRealPath());
            $text = $pdfDocument->getText();

            // Basic cleanup of extracted text (removing excessive whitespaces)
            return trim((string) preg_replace('/\s+/', ' ', $text));
        } catch (Exception $e) {
            throw new Exception("Failed to extract text from PDF: " . $e->getMessage());
        }
    }

    /**
     * Validate the uploaded PDF.
     */
    public function validatePdf(UploadedFile $pdf): bool
    {
        $extension = strtolower((string) $pdf->getClientOriginalExtension());
        $mimeType = strtolower((string) $pdf->getMimeType());

        if ($extension !== 'pdf') {
            return false;
        }

        if (! in_array($mimeType, ['application/pdf', 'application/x-pdf'], true)) {
            return false;
        }

        return $this->hasPdfSignature($pdf);
    }

    /**
     * Get basic metadata of the PDF.
     */
    public function getMetadata(UploadedFile $pdf): array
    {
        try {
            $parser = $this->getParser();
            $pdfDocument = $parser->parseFile($pdf->getRealPath());
            $details = $pdfDocument->getDetails();
            $pages = count($pdfDocument->getPages());

            return [
                'pages' => $pages,
                'author' => $details['Author'] ?? null,
                'title' => $details['Title'] ?? null,
            ];
        } catch (Exception $e) {
            return [
                'pages' => null,
                'error' => $e->getMessage()
            ];
        }
    }

    protected function getParser(): object
    {
        if ($this->parser !== null) {
            return $this->parser;
        }

        if (!class_exists('Smalot\\PdfParser\\Parser')) {
            throw new Exception('PDF parser library is missing. Install dependency: composer require smalot/pdfparser');
        }

        $parserClass = 'Smalot\\PdfParser\\Parser';
        $this->parser = new $parserClass();

        return $this->parser;
    }

    private function hasPdfSignature(UploadedFile $pdf): bool
    {
        $path = $pdf->getRealPath();
        if ($path === false || $path === '') {
            return false;
        }

        $handle = @fopen($path, 'rb');
        if ($handle === false) {
            return false;
        }

        try {
            $header = (string) fread($handle, 5);
        } finally {
            fclose($handle);
        }

        return str_starts_with($header, '%PDF-');
    }
}
