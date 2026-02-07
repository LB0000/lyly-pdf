'use client';

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import type { GeneratedFile } from '@/lib/api';
import { api } from '@/lib/api';

interface FileListProps {
  files: GeneratedFile[];
  onPreview: (file: GeneratedFile) => void;
  zipUrl?: string | null;
}

export function FileList({ files, onPreview, zipUrl }: FileListProps) {
  const tempFiles = files.filter((f) => f.type === 'temp');
  const draftFiles = files.filter((f) => f.type === 'draft');

  const formatSize = (bytes: number) => {
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
  };

  const FileItem = ({ file }: { file: GeneratedFile }) => (
    <div className="flex items-center justify-between p-3 rounded-lg border bg-card hover:bg-muted/50 transition-colors">
      <div className="flex items-center gap-3 min-w-0 flex-1">
        <span className="text-2xl flex-shrink-0">
          {file.type === 'draft' ? '📑' : '📄'}
        </span>
        <div className="min-w-0 flex-1">
          <p className="font-mono text-sm truncate">{file.name}</p>
          <p className="text-xs text-muted-foreground">
            {formatSize(file.size)}
          </p>
        </div>
      </div>
      <div className="flex gap-2 flex-shrink-0">
        <Button
          variant="outline"
          size="sm"
          onClick={() => onPreview(file)}
        >
          Preview
        </Button>
        <Button
          variant="outline"
          size="sm"
          asChild
        >
          <a
            href={api.getDownloadUrl(file.path)}
            download={file.name}
          >
            DL
          </a>
        </Button>
      </div>
    </div>
  );

  return (
    <Card className="flex-1">
      <CardHeader className="pb-3">
        <div className="flex items-center justify-between">
          <CardTitle className="text-sm font-medium uppercase tracking-wider text-muted-foreground flex items-center gap-2">
            <span className="w-2 h-2 rounded-full bg-sky-500" />
            Generated Files
            <span className="ml-2 text-xs font-normal">
              ({files.length} files)
            </span>
          </CardTitle>
          {zipUrl && (
            <Button variant="default" size="sm" asChild>
              <a href={zipUrl} download>
                ZIP Download
              </a>
            </Button>
          )}
        </div>
      </CardHeader>
      <CardContent>
        {files.length === 0 ? (
          <div className="flex flex-col items-center justify-center py-12 text-muted-foreground">
            <span className="text-4xl mb-4">📁</span>
            <p>生成されたファイルがありません</p>
          </div>
        ) : (
          <Tabs defaultValue="draft" className="w-full">
            <TabsList className="grid w-full grid-cols-2 mb-4">
              <TabsTrigger value="draft">
                印刷用 ({draftFiles.length})
              </TabsTrigger>
              <TabsTrigger value="temp">
                個別 ({tempFiles.length})
              </TabsTrigger>
            </TabsList>
            <TabsContent value="draft">
              <ScrollArea className="h-[400px]">
                <div className="space-y-2 pr-4">
                  {draftFiles.map((file) => (
                    <FileItem key={file.path} file={file} />
                  ))}
                  {draftFiles.length === 0 && (
                    <p className="text-center text-muted-foreground py-8">
                      印刷用PDFがありません
                    </p>
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
                    <p className="text-center text-muted-foreground py-8">
                      個別PDFがありません
                    </p>
                  )}
                </div>
              </ScrollArea>
            </TabsContent>
          </Tabs>
        )}
      </CardContent>
    </Card>
  );
}
