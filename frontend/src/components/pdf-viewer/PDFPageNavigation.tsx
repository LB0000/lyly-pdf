'use client';

import { useState, useCallback, KeyboardEvent } from 'react';
import { ChevronLeft, ChevronRight } from 'lucide-react';

interface PDFPageNavigationProps {
  pageNumber: number;
  numPages: number;
  onPageChange: (page: number) => void;
  onPrevPage: () => void;
  onNextPage: () => void;
  className?: string;
}

export function PDFPageNavigation({
  pageNumber,
  numPages,
  onPageChange,
  onPrevPage,
  onNextPage,
  className = '',
}: PDFPageNavigationProps) {
  const [inputValue, setInputValue] = useState(String(pageNumber));
  const [isFocused, setIsFocused] = useState(false);

  const handleInputChange = useCallback((e: React.ChangeEvent<HTMLInputElement>) => {
    const value = e.target.value;
    if (value === '' || /^\d+$/.test(value)) {
      setInputValue(value);
    }
  }, []);

  const handleInputBlur = useCallback(() => {
    setIsFocused(false);
    const page = parseInt(inputValue, 10);
    if (!isNaN(page) && page >= 1 && page <= numPages) {
      onPageChange(page);
    } else {
      setInputValue(String(pageNumber));
    }
  }, [inputValue, numPages, pageNumber, onPageChange]);

  const handleKeyDown = useCallback((e: KeyboardEvent<HTMLInputElement>) => {
    if (e.key === 'Enter') {
      e.currentTarget.blur();
    } else if (e.key === 'Escape') {
      setInputValue(String(pageNumber));
      e.currentTarget.blur();
    }
  }, [pageNumber]);

  const handleFocus = useCallback(() => {
    setIsFocused(true);
    setInputValue(String(pageNumber));
  }, [pageNumber]);

  // Sync input with external page changes
  if (!isFocused && inputValue !== String(pageNumber)) {
    setInputValue(String(pageNumber));
  }

  return (
    <div className={`page-navigation ${className}`}>
      {/* Previous Button */}
      <button
        onClick={onPrevPage}
        disabled={pageNumber <= 1}
        className="page-nav-button"
        title="Previous Page"
      >
        <ChevronLeft className="w-4 h-4" />
      </button>

      {/* Page Input */}
      <div className="page-indicator">
        <span className="page-label">Page</span>
        <input
          type="text"
          value={inputValue}
          onChange={handleInputChange}
          onBlur={handleInputBlur}
          onFocus={handleFocus}
          onKeyDown={handleKeyDown}
          className="page-input"
          aria-label="Current page"
        />
        <span className="page-separator">/</span>
        <span className="page-total">{numPages}</span>
      </div>

      {/* Next Button */}
      <button
        onClick={onNextPage}
        disabled={pageNumber >= numPages}
        className="page-nav-button"
        title="Next Page"
      >
        <ChevronRight className="w-4 h-4" />
      </button>
    </div>
  );
}
