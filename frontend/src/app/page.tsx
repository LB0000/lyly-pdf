'use client';

import { useState, useCallback } from 'react';
import { DropZone } from '@/components/DropZone';
import { ProcessTypeSelector, type ProcessType } from '@/components/ProcessTypeSelector';
import { LogViewer } from '@/components/LogViewer';
import { FileList } from '@/components/FileList';
import { PDFPreviewPanel } from '@/components/PDFPreviewPanel';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { HistoryList } from '@/components/HistoryList';
import { ValidationPanel } from '@/components/ValidationPanel';
import { api, type LogEntry, type GeneratedFile, type GenerationMode, type SSEEvent, type OrderValidation } from '@/lib/api';

export default function Home() {
  const [selectedFile, setSelectedFile] = useState<File | null>(null);
  const [processType, setProcessType] = useState<ProcessType>('all');
  const [showGuideline, setShowGuideline] = useState(false);
  const [isGenerating, setIsGenerating] = useState(false);
  const [logs, setLogs] = useState<LogEntry[]>([]);
  const [generatedFiles, setGeneratedFiles] = useState<GeneratedFile[]>([]);
  const [zipUrl, setZipUrl] = useState<string | null>(null);
  const [previewFile, setPreviewFile] = useState<GeneratedFile | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [progress, setProgress] = useState<number>(0);
  const [validations, setValidations] = useState<OrderValidation[]>([]);

  const handleFileSelect = useCallback((file: File) => {
    setSelectedFile(file);
    setError(null);
    setValidations([]);
  }, []);

  const handleGenerate = useCallback(async () => {
    if (!selectedFile) return;

    setIsGenerating(true);
    setLogs([]);
    setGeneratedFiles([]);
    setZipUrl(null);
    setError(null);
    setProgress(0);
    setValidations([]);

    try {
      setLogs([{ message: `CSVファイルをアップロード中: ${selectedFile.name}`, type: 'info' }]);

      const mode: GenerationMode = showGuideline ? 'normal' : 'download';

      await api.generateStream(selectedFile, processType, mode, (event: SSEEvent) => {
        if (event.type === 'done') {
          // 完了イベント: ファイル一覧とZIPを設定
          if (event.files) setGeneratedFiles(event.files);
          if (event.zipFilePath) setZipUrl(api.getDownloadUrl(event.zipFilePath));
          setProgress(100);
        } else if (event.type === 'validation') {
          // テキスト検証イベント: 注文単位で収集
          if (event.order_name && event.warnings) {
            setValidations((prev) => {
              // 重複order_nameがあれば警告をマージ
              const existing = prev.find((v) => v.order_name === event.order_name);
              if (existing) {
                return prev.map((v) =>
                  v.order_name === event.order_name
                    ? { ...v, warnings: [...v.warnings, ...event.warnings!] }
                    : v
                );
              }
              return [...prev, {
                order_name: event.order_name!,
                warnings: event.warnings!,
              }];
            });
          }
        } else {
          // ログイベント: リアルタイムで追加
          setLogs((prev) => [...prev, { message: event.message, type: event.type as LogEntry['type'] }]);
          if (event.progress !== undefined) setProgress(event.progress);
        }
      });
    } catch (err) {
      const message = err instanceof Error ? err.message : '不明なエラー';
      setError(message);
      setLogs((prev) => [...prev, { message, type: 'error' }]);
    } finally {
      setIsGenerating(false);
    }
  }, [selectedFile, processType, showGuideline]);

  const handleReset = useCallback(() => {
    setSelectedFile(null);
    setLogs([]);
    setGeneratedFiles([]);
    setZipUrl(null);
    setError(null);
    setValidations([]);
  }, []);

  return (
    <>
      <div className="grid grid-cols-1 lg:grid-cols-[400px_1fr] gap-6">
        {/* Left Column - Input */}
        <div className="space-y-4">
          <DropZone
            onFileSelect={handleFileSelect}
            disabled={isGenerating}
          />

          <ProcessTypeSelector
            value={processType}
            onChange={setProcessType}
            disabled={isGenerating}
          />

          {/* Guideline Toggle */}
          <Card>
            <CardContent className="pt-4">
              <button
                onClick={() => setShowGuideline(!showGuideline)}
                disabled={isGenerating}
                className={`
                  w-full flex items-center justify-between p-3 rounded-lg border
                  transition-all duration-200
                  ${showGuideline
                    ? 'border-amber-500 bg-amber-500/10'
                    : 'border-border hover:border-amber-500/50'
                  }
                  ${isGenerating ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer'}
                `}
              >
                <div className="text-left">
                  <span className="font-medium">ガイドライン表示</span>
                  <span className="block text-xs text-muted-foreground">
                    {showGuideline ? 'ON（確認用・生成が遅くなります）' : 'OFF（高速モード）'}
                  </span>
                </div>
                <div
                  className={`
                    w-12 h-6 rounded-full transition-colors relative
                    ${showGuideline ? 'bg-amber-500' : 'bg-muted'}
                  `}
                >
                  <div
                    className={`
                      absolute top-1 w-4 h-4 rounded-full bg-white shadow transition-transform
                      ${showGuideline ? 'translate-x-7' : 'translate-x-1'}
                    `}
                  />
                </div>
              </button>
            </CardContent>
          </Card>

          <Card>
            <CardHeader className="pb-3">
              <CardTitle className="text-sm font-medium uppercase tracking-wider text-muted-foreground">
                Actions
              </CardTitle>
            </CardHeader>
            <CardContent className="space-y-3">
              <Button
                className="w-full bg-gradient-to-r from-emerald-500 to-sky-500 hover:from-emerald-600 hover:to-sky-600"
                size="lg"
                onClick={handleGenerate}
                disabled={!selectedFile || isGenerating}
              >
                {isGenerating ? (
                  <>
                    <span className="animate-spin mr-2">⏳</span>
                    生成中...{progress > 0 && ` ${progress}%`}
                  </>
                ) : (
                  <>
                    🚀 PDF生成開始
                  </>
                )}
              </Button>
              <Button
                variant="outline"
                className="w-full"
                onClick={handleReset}
                disabled={isGenerating}
              >
                🔄 リセット
              </Button>
            </CardContent>
          </Card>

          {error && (
            <Card className="border-red-500/50 bg-red-500/10">
              <CardContent className="pt-4">
                <p className="text-sm text-red-500">{error}</p>
              </CardContent>
            </Card>
          )}

          <LogViewer logs={logs} isGenerating={isGenerating} />
          <ValidationPanel validations={validations} isGenerating={isGenerating} />
        </div>

        {/* Right Column - File List */}
        <div className="space-y-4">
          <FileList
            files={generatedFiles}
            onPreview={setPreviewFile}
            zipUrl={zipUrl}
          />
          <HistoryList onPreview={setPreviewFile} />
        </div>
      </div>

      {/* PDF Preview Modal - renders as portal */}
      <PDFPreviewPanel
        file={previewFile}
        files={generatedFiles}
        onClose={() => setPreviewFile(null)}
        onFileChange={setPreviewFile}
      />
    </>
  );
}
