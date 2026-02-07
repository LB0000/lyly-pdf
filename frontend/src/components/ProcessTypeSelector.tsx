'use client';

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Label } from '@/components/ui/label';

export type ProcessType = 'all' | 'temp' | 'draft';

interface ProcessTypeSelectorProps {
  value: ProcessType;
  onChange: (value: ProcessType) => void;
  disabled?: boolean;
}

const options: { value: ProcessType; label: string; description: string }[] = [
  {
    value: 'all',
    label: '全て生成',
    description: '個別PDF + 印刷用PDF',
  },
  {
    value: 'temp',
    label: '個別のみ',
    description: '個別PDFのみ生成',
  },
  {
    value: 'draft',
    label: '印刷用のみ',
    description: '印刷用PDFのみ生成',
  },
];

export function ProcessTypeSelector({
  value,
  onChange,
  disabled,
}: ProcessTypeSelectorProps) {
  return (
    <Card>
      <CardHeader className="pb-3">
        <CardTitle className="text-sm font-medium uppercase tracking-wider text-muted-foreground">
          Process Type
        </CardTitle>
      </CardHeader>
      <CardContent>
        <RadioGroup
          value={value}
          onValueChange={(v) => onChange(v as ProcessType)}
          disabled={disabled}
          className="space-y-2"
        >
          {options.map((option) => (
            <div
              key={option.value}
              className={`
                flex items-center space-x-3 p-3 rounded-lg border
                transition-all duration-200
                ${value === option.value
                  ? 'border-emerald-500 bg-emerald-500/10'
                  : 'border-border hover:border-emerald-500/50'
                }
                ${disabled ? 'opacity-50' : ''}
              `}
            >
              <RadioGroupItem
                value={option.value}
                id={option.value}
                className="border-emerald-500 text-emerald-500"
              />
              <Label
                htmlFor={option.value}
                className="flex-1 cursor-pointer"
              >
                <span className="font-medium">{option.label}</span>
                <span className="block text-xs text-muted-foreground">
                  {option.description}
                </span>
              </Label>
            </div>
          ))}
        </RadioGroup>
      </CardContent>
    </Card>
  );
}
