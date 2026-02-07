'use client';

import { useState, useCallback, useEffect } from 'react';
import type { DocumentProps } from 'react-pdf';

interface PDFViewerProps {
  url: string;
  pageNumber: number;
  scale: number;
  rotation: number;
  onLoadSuccess: (data: { numPages: number }) => void;
  onLoadError: (error: Error) => void;
  onPageClick?: () => void;
}

export function PDFViewer({
  url,
  pageNumber,
  scale,
  rotation,
  onLoadSuccess,
  onLoadError,
  onPageClick,
}: PDFViewerProps) {
  const [pageLoading, setPageLoading] = useState(true);
  const [ReactPDF, setReactPDF] = useState<{
    Document: React.ComponentType<DocumentProps>;
    Page: React.ComponentType<any>;
  } | null>(null);

  // Dynamically import react-pdf on client side only
  useEffect(() => {
    Promise.all([
      import('react-pdf'),
      // @ts-expect-error - CSS imports
      import('react-pdf/dist/Page/AnnotationLayer.css'),
      // @ts-expect-error - CSS imports
      import('react-pdf/dist/Page/TextLayer.css'),
    ]).then(([module]) => {
      // Configure worker
      module.pdfjs.GlobalWorkerOptions.workerSrc = '/pdf.worker.min.mjs';
      setReactPDF({
        Document: module.Document,
        Page: module.Page,
      });
    });
  }, []);

  const handlePageLoadSuccess = useCallback(() => {
    setPageLoading(false);
  }, []);

  if (!ReactPDF) {
    return <LoadingState />;
  }

  const { Document, Page } = ReactPDF;

  return (
    <div
      className="pdf-document-container"
      onClick={onPageClick}
    >
      <Document
        file={url}
        onLoadSuccess={onLoadSuccess}
        onLoadError={onLoadError}
        loading={<LoadingState />}
        error={<ErrorState />}
        className="pdf-document"
      >
        <div
          className="pdf-page-wrapper"
          style={{
            opacity: pageLoading ? 0.5 : 1,
            transition: 'opacity 0.3s ease-out',
          }}
        >
          <Page
            pageNumber={pageNumber}
            scale={scale}
            rotate={rotation}
            renderTextLayer={true}
            renderAnnotationLayer={true}
            onLoadSuccess={handlePageLoadSuccess}
            onRenderSuccess={() => setPageLoading(false)}
            loading={null}
            className="pdf-page"
          />
        </div>
      </Document>
    </div>
  );
}

function LoadingState() {
  return (
    <div className="flex items-center justify-center h-64">
      <div className="relative">
        {/* Animated loading rings */}
        <div className="absolute inset-0 flex items-center justify-center">
          <div className="w-16 h-16 border-2 border-amber-500/20 rounded-full animate-ping" />
        </div>
        <div className="relative flex items-center justify-center w-16 h-16">
          <div className="w-12 h-12 border-2 border-amber-500/30 border-t-amber-500 rounded-full animate-spin" />
        </div>
        <p className="mt-6 text-xs font-mono text-zinc-500 tracking-widest uppercase">
          Loading PDF
        </p>
      </div>
    </div>
  );
}

function ErrorState() {
  return (
    <div className="flex flex-col items-center justify-center h-64 text-center px-8">
      <div className="w-16 h-16 mb-4 rounded-xl bg-red-500/10 flex items-center justify-center">
        <svg
          className="w-8 h-8 text-red-400"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
        >
          <path
            strokeLinecap="round"
            strokeLinejoin="round"
            strokeWidth={1.5}
            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
          />
        </svg>
      </div>
      <p className="text-sm font-mono text-zinc-400">
        Failed to load PDF
      </p>
      <p className="mt-2 text-xs text-zinc-600">
        Please try again or download the file
      </p>
    </div>
  );
}
