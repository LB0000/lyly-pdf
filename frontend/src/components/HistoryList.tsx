'use client';

import { useState, useEffect, useCallback } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
} from '@/components/ui/dialog';
import { api, type HistoryEntry, type GeneratedFile } from '@/lib/api';

interface HistoryListProps {
  onPreview: (file: GeneratedFile) => void;
}

const formatSize = (bytes: number) => {
  if (bytes < 1024) return `${bytes} B`;
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
};

export function HistoryList({ onPreview }: HistoryListProps) {
  const [histories, setHistories] = useState<HistoryEntry[]>([]);
  const [total, setTotal] = useState(0);
  const [loading, setLoading] = useState(true);
  const [selectedEntry, setSelectedEntry] = useState<HistoryEntry | null>(null);
  const [detailFiles, setDetailFiles] = useState<GeneratedFile[]>([]);
  const [detailLoading, setDetailLoading] = useState(false);

  useEffect(() => {
    api.getHistory()
      .then((res) => {
        if (res.success) {
          setHistories(res.histories);
          setTotal(res.total);
        }
      })
      .catch(() => {})
      .finally(() => setLoading(false));
  }, []);

  const handleEntryClick = useCallback(async (entry: HistoryEntry) => {
    setSelectedEntry(entry);
    setDetailLoading(true);
    setDetailFiles([]);
    try {
      const res = await api.getHistoryDetail(entry.timestamp);
      if (res.success) setDetailFiles(res.files);
    } catch {
      // ignore
    } finally {
      setDetailLoading(false);
    }
  }, []);

  const getZipDownloadUrl = (entry: HistoryEntry) => {
    if (!entry.zipFile) return null;
    return api.getDownloadUrl(`./output/${entry.zipFile}`);
  };

  const draftFiles = detailFiles.filter((f) => f.type === 'draft');
  const tempFiles = detailFiles.filter((f) => f.type === 'temp');

  const FileItem = ({ file }: { file: GeneratedFile }) => (
    <div className="flex items-center justify-between p-3 rounded-lg border bg-card hover:bg-muted/50 transition-colors">
      <div className="flex items-center gap-3 min-w-0 flex-1">
        <span className="text-2xl flex-shrink-0">
          {file.type === 'draft' ? '📑' : '📄'}
        </span>
        <div className="min-w-0 flex-1">
          <p className="font-mono text-sm truncate">{file.name}</p>
          <p className="text-xs text-muted-foreground">{formatSize(file.size)}</p>
        </div>
      </div>
      <div className="flex gap-2 flex-shrink-0">
        <Button variant="outline" size="sm" onClick={() => onPreview(file)}>
          Preview
        </Button>
        <Button variant="outline" size="sm" asChild>
          <a href={api.getDownloadUrl(file.path)} download={file.name}>DL</a>
        </Button>
      </div>
    </div>
  );

  return (
    <>
      <Card>
        <CardHeader className="pb-3">
          <CardTitle className="text-sm font-medium uppercase tracking-wider text-muted-foreground flex items-center gap-2">
            <span className="w-2 h-2 rounded-full bg-amber-500" />
            History
            <span className="ml-2 text-xs font-normal">
              ({total})
            </span>
          </CardTitle>
        </CardHeader>
        <CardContent>
          {loading ? (
            <div className="flex items-center justify-center py-8 text-muted-foreground">
              <span className="animate-spin mr-2">⏳</span> 読み込み中...
            </div>
          ) : histories.length === 0 ? (
            <div className="flex flex-col items-center justify-center py-8 text-muted-foreground">
              <span className="text-4xl mb-4">📂</span>
              <p>生成履歴がありません</p>
            </div>
          ) : (
            <ScrollArea className="h-[400px]">
              <div className="space-y-2 pr-4">
                {histories.map((entry) => (
                  <button
                    key={entry.timestamp}
                    onClick={() => handleEntryClick(entry)}
                    className="w-full flex items-center justify-between p-3 rounded-lg border bg-card hover:bg-muted/50 transition-colors text-left cursor-pointer"
                  >
                    <div className="min-w-0 flex-1">
                      <p className="text-sm font-medium">{entry.datetimeFormatted}</p>
                      <p className="text-xs text-muted-foreground">
                        {entry.csvFilename && (
                          <span className="font-mono mr-2">{entry.csvFilename}</span>
                        )}
                        印刷用 {entry.draftCount} / 個別 {entry.tempCount}
                        {entry.orderCount !== undefined && entry.orderCount > 0 && (
                          <span className="ml-1">
                            (注文{entry.orderCount}件
                            {entry.failedCount ? ` / 失敗${entry.failedCount}件` : ''})
                          </span>
                        )}
                      </p>
                    </div>
                    <div className="flex items-center gap-2 flex-shrink-0">
                      <span className="text-xs text-muted-foreground">
                        {entry.totalFiles} files
                      </span>
                      {entry.zipFile && (
                        <Button
                          variant="outline"
                          size="sm"
                          asChild
                          onClick={(e) => e.stopPropagation()}
                        >
                          <a href={getZipDownloadUrl(entry)!} download>
                            ZIP
                          </a>
                        </Button>
                      )}
                    </div>
                  </button>
                ))}
              </div>
            </ScrollArea>
          )}
        </CardContent>
      </Card>

      <Dialog open={!!selectedEntry} onOpenChange={(open) => !open && setSelectedEntry(null)}>
        <DialogContent className="sm:max-w-2xl max-h-[80vh] flex flex-col">
          <DialogHeader>
            <DialogTitle>{selectedEntry?.datetimeFormatted}</DialogTitle>
            <DialogDescription>
              {selectedEntry?.csvFilename && (
                <span className="font-mono mr-2">{selectedEntry.csvFilename}</span>
              )}
              印刷用 {selectedEntry?.draftCount} / 個別 {selectedEntry?.tempCount} ファイル
            </DialogDescription>
          </DialogHeader>
          {detailLoading ? (
            <div className="flex items-center justify-center py-12 text-muted-foreground">
              <span className="animate-spin mr-2">⏳</span> 読み込み中...
            </div>
          ) : detailFiles.length === 0 ? (
            <div className="flex flex-col items-center justify-center py-12 text-muted-foreground">
              <p>ファイルがありません</p>
            </div>
          ) : (
            <Tabs defaultValue="draft" className="flex-1 min-h-0">
              <TabsList className="grid w-full grid-cols-2 mb-4">
                <TabsTrigger value="draft">印刷用 ({draftFiles.length})</TabsTrigger>
                <TabsTrigger value="temp">個別 ({tempFiles.length})</TabsTrigger>
              </TabsList>
              <TabsContent value="draft">
                <ScrollArea className="h-[400px]">
                  <div className="space-y-2 pr-4">
                    {draftFiles.map((file) => (
                      <FileItem key={file.path} file={file} />
                    ))}
                    {draftFiles.length === 0 && (
                      <p className="text-center text-muted-foreground py-8">印刷用PDFがありません</p>
                    )}
                  </div>
                </ScrollArea>
              </TabsContent>
              <TabsContent value="temp">
                <ScrollArea className="h-[400px]">
                  <div className="space-y-2 pr-4">
                    {tempFiles.map((file) => (
                      <FileItem key={file.path} file={file} />
                    ))}
                    {tempFiles.length === 0 && (
                      <p className="text-center text-muted-foreground py-8">個別PDFがありません</p>
                    )}
                  </div>
                </ScrollArea>
              </TabsContent>
            </Tabs>
          )}
        </DialogContent>
      </Dialog>
    </>
  );
}
