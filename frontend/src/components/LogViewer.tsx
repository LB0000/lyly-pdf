'use client';

import { useEffect, useRef } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ScrollArea } from '@/components/ui/scroll-area';
import type { LogEntry } from '@/lib/api';

interface LogViewerProps {
  logs: LogEntry[];
  isGenerating?: boolean;
}

export function LogViewer({ logs, isGenerating }: LogViewerProps) {
  const scrollRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    if (scrollRef.current) {
      scrollRef.current.scrollTop = scrollRef.current.scrollHeight;
    }
  }, [logs]);

  const getLogColor = (type: LogEntry['type']) => {
    switch (type) {
      case 'success':
        return 'text-emerald-500';
      case 'error':
        return 'text-red-500';
      case 'warning':
        return 'text-amber-500';
      case 'skipped':
        return 'text-muted-foreground';
      default:
        return 'text-foreground';
    }
  };

  const getLogPrefix = (type: LogEntry['type']) => {
    switch (type) {
      case 'success':
        return '[OK]';
      case 'error':
        return '[NG]';
      case 'warning':
        return '[!!]';
      case 'skipped':
        return '[--]';
      default:
        return '[>>]';
    }
  };

  return (
    <Card>
      <CardHeader className="pb-3">
        <CardTitle className="text-sm font-medium uppercase tracking-wider text-muted-foreground flex items-center gap-2">
          <span
            className={`w-2 h-2 rounded-full ${
              isGenerating ? 'bg-amber-500 animate-pulse' : 'bg-muted-foreground'
            }`}
          />
          Generation Logs
        </CardTitle>
      </CardHeader>
      <CardContent>
        <ScrollArea className="h-[200px] rounded-md border bg-card p-3">
          <div ref={scrollRef} className="font-mono text-xs space-y-1">
            {logs.length === 0 ? (
              <p className="text-muted-foreground">
                ログはここに表示されます...
              </p>
            ) : (
              logs.map((log, index) => (
                <div key={index} className={getLogColor(log.type)}>
                  <span className="opacity-60">{getLogPrefix(log.type)}</span>{' '}
                  {log.message}
                </div>
              ))
            )}
            {isGenerating && (
              <div className="text-amber-500 animate-pulse">
                <span className="opacity-60">[..]</span> 処理中...
              </div>
            )}
          </div>
        </ScrollArea>
      </CardContent>
    </Card>
  );
}
