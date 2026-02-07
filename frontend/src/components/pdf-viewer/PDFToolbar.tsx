'use client';

import {
  ZoomIn,
  ZoomOut,
  RotateCw,
  RotateCcw,
  Maximize2,
  Download,
  Printer,
  Columns2
} from 'lucide-react';

interface PDFToolbarProps {
  scale: number;
  rotation: number;
  onZoomIn: () => void;
  onZoomOut: () => void;
  onRotate: (direction: 'cw' | 'ccw') => void;
  onFitWidth: () => void;
  onFitPage: () => void;
  onSetScale: (scale: number) => void;
  onDownload?: () => void;
  onPrint?: () => void;
  minScale: number;
  maxScale: number;
  zoomPresets: readonly number[];
  className?: string;
}

export function PDFToolbar({
  scale,
  onZoomIn,
  onZoomOut,
  onRotate,
  onFitWidth,
  onFitPage,
  onSetScale,
  onDownload,
  onPrint,
  minScale,
  maxScale,
  zoomPresets,
  className = '',
}: PDFToolbarProps) {
  const zoomPercent = Math.round(scale * 100);

  return (
    <div className={`pdf-toolbar ${className}`}>
      {/* Zoom Controls Group */}
      <div className="toolbar-group">
        <ToolbarButton
          onClick={onZoomOut}
          disabled={scale <= minScale}
          title="Zoom Out"
        >
          <ZoomOut className="w-4 h-4" />
        </ToolbarButton>

        {/* Zoom Slider */}
        <div className="zoom-slider-container">
          <input
            type="range"
            min={minScale * 100}
            max={maxScale * 100}
            value={zoomPercent}
            onChange={(e) => onSetScale(Number(e.target.value) / 100)}
            className="zoom-slider"
          />
          <div
            className="zoom-slider-fill"
            style={{
              width: `${((scale - minScale) / (maxScale - minScale)) * 100}%`
            }}
          />
        </div>

        <ToolbarButton
          onClick={onZoomIn}
          disabled={scale >= maxScale}
          title="Zoom In"
        >
          <ZoomIn className="w-4 h-4" />
        </ToolbarButton>

        {/* Zoom Preset Dropdown */}
        <div className="relative group">
          <button className="zoom-display">
            <span className="font-mono text-xs">{zoomPercent}%</span>
            <svg className="w-3 h-3 ml-1 opacity-50" viewBox="0 0 12 12" fill="currentColor">
              <path d="M3 5l3 3 3-3" />
            </svg>
          </button>
          <div className="zoom-dropdown">
            {zoomPresets.map((preset) => (
              <button
                key={preset}
                onClick={() => onSetScale(preset)}
                className={`zoom-preset ${scale === preset ? 'active' : ''}`}
              >
                {Math.round(preset * 100)}%
              </button>
            ))}
            <div className="zoom-dropdown-divider" />
            <button onClick={onFitWidth} className="zoom-preset">
              <Columns2 className="w-3 h-3 mr-2" />
              Fit Width
            </button>
            <button onClick={onFitPage} className="zoom-preset">
              <Maximize2 className="w-3 h-3 mr-2" />
              Fit Page
            </button>
          </div>
        </div>
      </div>

      {/* Separator */}
      <div className="toolbar-separator" />

      {/* Rotation Group */}
      <div className="toolbar-group">
        <ToolbarButton
          onClick={() => onRotate('ccw')}
          title="Rotate Counter-Clockwise"
        >
          <RotateCcw className="w-4 h-4" />
        </ToolbarButton>
        <ToolbarButton
          onClick={() => onRotate('cw')}
          title="Rotate Clockwise"
        >
          <RotateCw className="w-4 h-4" />
        </ToolbarButton>
      </div>

      {/* Separator */}
      <div className="toolbar-separator" />

      {/* Actions Group */}
      <div className="toolbar-group">
        {onPrint && (
          <ToolbarButton onClick={onPrint} title="Print">
            <Printer className="w-4 h-4" />
          </ToolbarButton>
        )}
        {onDownload && (
          <ToolbarButton onClick={onDownload} title="Download">
            <Download className="w-4 h-4" />
          </ToolbarButton>
        )}
      </div>
    </div>
  );
}

interface ToolbarButtonProps {
  onClick: () => void;
  disabled?: boolean;
  title?: string;
  children: React.ReactNode;
}

function ToolbarButton({ onClick, disabled, title, children }: ToolbarButtonProps) {
  return (
    <button
      onClick={onClick}
      disabled={disabled}
      title={title}
      className="toolbar-button"
    >
      {children}
    </button>
  );
}
