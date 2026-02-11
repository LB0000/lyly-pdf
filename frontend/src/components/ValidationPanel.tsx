'use client';

import { useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ScrollArea } from '@/components/ui/scroll-area';
import type { OrderValidation, ValidationWarning } from '@/lib/api';

interface ValidationPanelProps {
  validations: OrderValidation[];
  isGenerating?: boolean;
}

const warningTypeConfig: Record<string, { label: string; color: string; bgColor: string }> = {
  encoding: { label: '文字化け', color: 'text-red-500', bgColor: 'bg-red-500/10' },
  typo: { label: 'タイポ', color: 'text-amber-500', bgColor: 'bg-amber-500/10' },
  date: { label: '日付', color: 'text-blue-500', bgColor: 'bg-blue-500/10' },
  time: { label: '時間', color: 'text-blue-500', bgColor: 'bg-blue-500/10' },
};

const defaultWarningConfig = { label: '警告', color: 'text-gray-500', bgColor: 'bg-gray-500/10' };

export function ValidationPanel({ validations, isGenerating }: ValidationPanelProps) {
  const [expandedOrders, setExpandedOrders] = useState<Set<string>>(new Set());

  const totalWarnings = validations.reduce((sum, v) => sum + v.warnings.length, 0);

  const toggleOrder = (orderName: string) => {
    setExpandedOrders((prev) => {
      const next = new Set(prev);
      if (next.has(orderName)) {
        next.delete(orderName);
      } else {
        next.add(orderName);
      }
      return next;
    });
  };

  // 生成中 or 検証結果なしの場合は非表示
  if (isGenerating || validations.length === 0) {
    return null;
  }

  return (
    <Card className={totalWarnings > 0 ? 'border-amber-500/50' : 'border-emerald-500/50'}>
      <CardHeader className="pb-3">
        <CardTitle className="text-sm font-medium uppercase tracking-wider text-muted-foreground flex items-center gap-2">
          <span
            className={`w-2 h-2 rounded-full ${
              totalWarnings > 0 ? 'bg-amber-500' : 'bg-emerald-500'
            }`}
          />
          {totalWarnings > 0
            ? `Text Validation - ${validations.length}件の注文に${totalWarnings}件の警告`
            : 'Text Validation - 問題なし'}
        </CardTitle>
      </CardHeader>
      {totalWarnings > 0 && (
        <CardContent>
          <ScrollArea className="max-h-[300px] rounded-md border bg-card p-3">
            <div className="space-y-2">
              {validations.map((validation) => (
                <div key={validation.order_name} className="border rounded-md overflow-hidden">
                  <button
                    onClick={() => toggleOrder(validation.order_name)}
                    aria-expanded={expandedOrders.has(validation.order_name)}
                    className="w-full flex items-center justify-between px-3 py-2 text-xs font-mono hover:bg-muted/50 transition-colors"
                  >
                    <span className="font-medium">{validation.order_name}</span>
                    <span className="flex items-center gap-2">
                      <span className="text-amber-500">{validation.warnings.length}件</span>
                      <span className="text-muted-foreground">
                        {expandedOrders.has(validation.order_name) ? '\u25B2' : '\u25BC'}
                      </span>
                    </span>
                  </button>
                  {expandedOrders.has(validation.order_name) && (
                    <div className="px-3 pb-2 space-y-1">
                      {validation.warnings.map((w, i) => {
                        const config = warningTypeConfig[w.type] ?? defaultWarningConfig;
                        return (
                          <div key={i} className="flex items-start gap-2 text-xs font-mono">
                            <span
                              className={`inline-block px-1.5 py-0.5 rounded text-[10px] font-bold shrink-0 ${config.color} ${config.bgColor}`}
                            >
                              {config.label}
                            </span>
                            <span className="text-muted-foreground shrink-0">{w.field}:</span>
                            <span className={config.color}>{w.message}</span>
                          </div>
                        );
                      })}
                    </div>
                  )}
                </div>
              ))}
            </div>
          </ScrollArea>
        </CardContent>
      )}
    </Card>
  );
}
