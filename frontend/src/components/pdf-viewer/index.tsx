'use client';

import { useCallback, useEffect, useMemo, useState } from 'react';
import { X, ExternalLink, Download, FileText, ChevronLeft, ChevronRight } from 'lucide-react';
import { createPortal } from 'react-dom';
import type { GeneratedFile } from '@/lib/api';
import { api } from '@/lib/api';
import { usePDFViewer } from './usePDFViewer';
import { PDFViewer } from './PDFViewer';
import { PDFToolbar } from './PDFToolbar';
import { PDFPageNavigation } from './PDFPageNavigation';
import './styles.css';

interface PDFPreviewPanelProps {
  file: GeneratedFile | null;
  files?: GeneratedFile[];
  onClose: () => void;
  onFileChange?: (file: GeneratedFile) => void;
}

export function PDFPreviewPanel({ file, files = [], onClose, onFileChange }: PDFPreviewPanelProps) {
  const { state, actions, constants } = usePDFViewer();
  const [showControls, setShowControls] = useState(true);
  const [isVisible, setIsVisible] = useState(false);
  const [isMounted, setIsMounted] = useState(false);

  // File navigation
  const currentIndex = useMemo(() => {
    if (!file || files.length === 0) return -1;
    return files.findIndex(f => f.path === file.path);
  }, [file, files]);

  const hasPrevFile = currentIndex > 0;
  const hasNextFile = currentIndex >= 0 && currentIndex < files.length - 1;

  const goToPrevFile = useCallback(() => {
    if (hasPrevFile && onFileChange) {
      actions.resetState();
      onFileChange(files[currentIndex - 1]);
    }
  }, [hasPrevFile, onFileChange, files, currentIndex, actions]);

  const goToNextFile = useCallback(() => {
    if (hasNextFile && onFileChange) {
      actions.resetState();
      onFileChange(files[currentIndex + 1]);
    }
  }, [hasNextFile, onFileChange, files, currentIndex, actions]);

  // 隣接ファイルをプリフェッチ（ファイル切り替え高速化）
  useEffect(() => {
    if (files.length <= 1 || currentIndex < 0) return;
    const links: HTMLLinkElement[] = [];
    const indices = [currentIndex - 1, currentIndex + 1];
    for (const i of indices) {
      if (i >= 0 && i < files.length) {
        const link = document.createElement('link');
        link.rel = 'prefetch';
        link.href = api.getPreviewUrl(files[i].path);
        document.head.appendChild(link);
        links.push(link);
      }
    }
    return () => {
      links.forEach(link => document.head.removeChild(link));
    };
  }, [currentIndex, files]);

  // Mount check for portal
  useEffect(() => {
    setIsMounted(true);
  }, []);

  // Animate in when file changes
  useEffect(() => {
    if (file) {
      // Small delay for animation
      requestAnimationFrame(() => setIsVisible(true));
    } else {
      setIsVisible(false);
    }
  }, [file]);

  // Hide controls after inactivity
  useEffect(() => {
    if (!showControls) return;
    const timer = setTimeout(() => setShowControls(false), 3000);
    return () => clearTimeout(timer);
  }, [showControls]);

  // Keyboard shortcuts
  useEffect(() => {
    if (!file) return;

    const handleKeyDown = (e: KeyboardEvent) => {
      // Ignore if typing in an input
      if (e.target instanceof HTMLInputElement) return;

      switch (e.key) {
        case 'ArrowLeft':
        case 'ArrowUp':
          e.preventDefault();
          actions.prevPage();
          break;
        case 'ArrowRight':
        case 'ArrowDown':
        case ' ':
          e.preventDefault();
          actions.nextPage();
          break;
        case '+':
        case '=':
          e.preventDefault();
          actions.zoomIn();
          break;
        case '-':
          e.preventDefault();
          actions.zoomOut();
          break;
        case 'r':
          e.preventDefault();
          actions.rotate('cw');
          break;
        case 'R':
          e.preventDefault();
          actions.rotate('ccw');
          break;
        case '0':
          e.preventDefault();
          actions.setScale(1);
          break;
        case '[':
          e.preventDefault();
          goToPrevFile();
          break;
        case ']':
          e.preventDefault();
          goToNextFile();
          break;
        case 'Escape':
          e.preventDefault();
          onClose();
          break;
      }
    };

    window.addEventListener('keydown', handleKeyDown);
    return () => window.removeEventListener('keydown', handleKeyDown);
  }, [file, actions, onClose, goToPrevFile, goToNextFile]);

  // Prevent body scroll when modal is open
  useEffect(() => {
    if (file) {
      document.body.style.overflow = 'hidden';
    } else {
      document.body.style.overflow = '';
    }
    return () => {
      document.body.style.overflow = '';
    };
  }, [file]);

  const handleMouseMove = useCallback(() => {
    setShowControls(true);
  }, []);

  const handleDownload = useCallback(() => {
    if (!file) return;
    const link = document.createElement('a');
    link.href = api.getDownloadUrl(file.path);
    link.download = file.name;
    link.click();
  }, [file]);

  const handlePrint = useCallback(() => {
    if (!file) return;
    const previewUrl = api.getPreviewUrl(file.path);
    const printWindow = window.open(previewUrl, '_blank');
    if (printWindow) {
      printWindow.onload = () => printWindow.print();
    }
  }, [file]);

  const handleOpenNewTab = useCallback(() => {
    if (!file) return;
    window.open(api.getPreviewUrl(file.path), '_blank');
  }, [file]);

  const handleOverlayClick = useCallback((e: React.MouseEvent) => {
    // Only close if clicking the overlay itself, not the modal content
    if (e.target === e.currentTarget) {
      onClose();
    }
  }, [onClose]);

  if (!file || !isMounted) return null;

  const previewUrl = api.getPreviewUrl(file.path);
  const showFileNav = files.length > 1 && currentIndex >= 0;

  const modalContent = (
    <div
      className={`pdf-modal-overlay ${isVisible ? 'visible' : ''}`}
      onClick={handleOverlayClick}
    >
      <div
        className={`pdf-modal-container ${isVisible ? 'visible' : ''}`}
        onMouseMove={handleMouseMove}
      >
        {/* Ambient Background */}
        <div className="pdf-ambient-bg" />

        {/* Header */}
        <header className={`pdf-header ${showControls ? 'visible' : ''}`}>
          <div className="pdf-header-info">
            <div className="pdf-file-icon">
              <FileText className="w-4 h-4" />
            </div>

            {showFileNav ? (
              <div className="pdf-file-nav">
                <button
                  onClick={goToPrevFile}
                  disabled={!hasPrevFile}
                  className="pdf-file-nav-button"
                  title="前のファイル ([)"
                >
                  <ChevronLeft className="w-4 h-4" />
                </button>
                <div className="pdf-file-meta">
                  <h3 className="pdf-file-name">{file.name}</h3>
                  <span className="pdf-file-type">
                    {file.type === 'draft' ? '印刷用' : '個別'} • {currentIndex + 1} / {files.length}
                  </span>
                </div>
                <button
                  onClick={goToNextFile}
                  disabled={!hasNextFile}
                  className="pdf-file-nav-button"
                  title="次のファイル (])"
                >
                  <ChevronRight className="w-4 h-4" />
                </button>
              </div>
            ) : (
              <div className="pdf-file-meta">
                <h3 className="pdf-file-name">{file.name}</h3>
                <span className="pdf-file-type">
                  {file.type === 'draft' ? '印刷用' : '個別'} • {state.numPages > 0 ? `${state.numPages} pages` : 'Loading...'}
                </span>
              </div>
            )}
          </div>

          <button
            onClick={onClose}
            className="pdf-close-button"
            title="Close (Esc)"
          >
            <X className="w-5 h-5" />
          </button>
        </header>

        {/* Main Viewport */}
        <div className="pdf-viewport">
          <div className="pdf-canvas">
            <PDFViewer
              url={previewUrl}
              pageNumber={state.pageNumber}
              scale={state.scale}
              rotation={state.rotation}
              onLoadSuccess={actions.onDocumentLoadSuccess}
              onLoadError={actions.onDocumentLoadError}
              onPageClick={() => setShowControls(true)}
            />
          </div>
        </div>

        {/* Floating Controls */}
        <div className={`pdf-controls ${showControls ? 'visible' : ''}`}>
          {/* Center: Toolbar */}
          <div className="pdf-controls-center">
            <PDFToolbar
              scale={state.scale}
              rotation={state.rotation}
              onZoomIn={actions.zoomIn}
              onZoomOut={actions.zoomOut}
              onRotate={actions.rotate}
              onFitWidth={actions.fitWidth}
              onFitPage={actions.fitPage}
              onSetScale={actions.setScale}
              onDownload={handleDownload}
              onPrint={handlePrint}
              minScale={constants.MIN_SCALE}
              maxScale={constants.MAX_SCALE}
              zoomPresets={constants.ZOOM_PRESETS}
            />
          </div>

          {/* Bottom: Page Navigation */}
          {state.numPages > 1 && (
            <div className="pdf-controls-bottom">
              <PDFPageNavigation
                pageNumber={state.pageNumber}
                numPages={state.numPages}
                onPageChange={actions.goToPage}
                onPrevPage={actions.prevPage}
                onNextPage={actions.nextPage}
              />
            </div>
          )}
        </div>

        {/* Footer Actions */}
        <footer className={`pdf-footer ${showControls ? 'visible' : ''}`}>
          <button onClick={handleDownload} className="pdf-action-button primary">
            <Download className="w-4 h-4" />
            <span>Download</span>
          </button>
          <button onClick={handleOpenNewTab} className="pdf-action-button">
            <ExternalLink className="w-4 h-4" />
            <span>新しいタブ</span>
          </button>
        </footer>

        {/* Keyboard Hints */}
        <div className={`pdf-hints ${showControls ? 'visible' : ''}`}>
          <span>←→ pages</span>
          <span>+− zoom</span>
          {showFileNav && <span>[] files</span>}
          <span>R rotate</span>
          <span>Esc close</span>
        </div>
      </div>
    </div>
  );

  // Render as portal to body for fullscreen overlay
  return createPortal(modalContent, document.body);
}

export { usePDFViewer } from './usePDFViewer';
export { PDFViewer } from './PDFViewer';
export { PDFToolbar } from './PDFToolbar';
export { PDFPageNavigation } from './PDFPageNavigation';
