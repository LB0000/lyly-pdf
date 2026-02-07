'use client';

import { useState, useCallback, useRef } from 'react';

export interface PDFViewerState {
  numPages: number;
  pageNumber: number;
  scale: number;
  rotation: number;
  isLoading: boolean;
  error: string | null;
}

const ZOOM_STEP = 0.25;
const MIN_SCALE = 0.25;
const MAX_SCALE = 3;
const ZOOM_PRESETS = [0.5, 0.75, 1, 1.25, 1.5, 2] as const;

export function usePDFViewer() {
  const [state, setState] = useState<PDFViewerState>({
    numPages: 0,
    pageNumber: 1,
    scale: 0.5,
    rotation: 0,
    isLoading: true,
    error: null,
  });

  const containerRef = useRef<HTMLDivElement>(null);

  const goToPage = useCallback((page: number) => {
    setState((prev) => ({
      ...prev,
      pageNumber: Math.max(1, Math.min(page, prev.numPages || 1)),
    }));
  }, []);

  const nextPage = useCallback(() => {
    setState((prev) => ({
      ...prev,
      pageNumber: Math.min(prev.pageNumber + 1, prev.numPages || 1),
    }));
  }, []);

  const prevPage = useCallback(() => {
    setState((prev) => ({
      ...prev,
      pageNumber: Math.max(prev.pageNumber - 1, 1),
    }));
  }, []);

  const setScale = useCallback((scale: number) => {
    setState((prev) => ({
      ...prev,
      scale: Math.max(MIN_SCALE, Math.min(scale, MAX_SCALE)),
    }));
  }, []);

  const zoomIn = useCallback(() => {
    setState((prev) => ({
      ...prev,
      scale: Math.min(prev.scale + ZOOM_STEP, MAX_SCALE),
    }));
  }, []);

  const zoomOut = useCallback(() => {
    setState((prev) => ({
      ...prev,
      scale: Math.max(prev.scale - ZOOM_STEP, MIN_SCALE),
    }));
  }, []);

  const rotate = useCallback((direction: 'cw' | 'ccw' = 'cw') => {
    setState((prev) => ({
      ...prev,
      rotation: (prev.rotation + (direction === 'cw' ? 90 : -90) + 360) % 360,
    }));
  }, []);

  const fitWidth = useCallback(() => {
    // This will be calculated based on container width
    // For now, use a reasonable default
    setScale(1.2);
  }, [setScale]);

  const fitPage = useCallback(() => {
    setScale(1);
  }, [setScale]);

  const onDocumentLoadSuccess = useCallback(({ numPages }: { numPages: number }) => {
    setState((prev) => ({
      ...prev,
      numPages,
      isLoading: false,
      error: null,
    }));
  }, []);

  const onDocumentLoadError = useCallback((error: Error) => {
    setState((prev) => ({
      ...prev,
      isLoading: false,
      error: error.message,
    }));
  }, []);

  return {
    state,
    containerRef,
    actions: {
      goToPage,
      nextPage,
      prevPage,
      setScale,
      zoomIn,
      zoomOut,
      rotate,
      fitWidth,
      fitPage,
      onDocumentLoadSuccess,
      onDocumentLoadError,
    },
    constants: {
      ZOOM_PRESETS,
      MIN_SCALE,
      MAX_SCALE,
    },
  };
}

export type PDFViewerActions = ReturnType<typeof usePDFViewer>['actions'];
