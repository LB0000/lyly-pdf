// API Client for LYLY PDF Generator

export interface LogEntry {
  message: string;
  type: 'success' | 'error' | 'info' | 'warning' | 'skipped';
}

export interface GeneratedFile {
  name: string;
  path: string;
  type: 'temp' | 'draft';
  size: number;
  mtime: number;
}

export interface GenerationResponse {
  success: boolean;
  logs: LogEntry[];
  files: GeneratedFile[];
  zipFile: string | null;
  zipFilePath: string | null;
  outputFolder: string;
  error?: string;
}

export interface HistoryEntry {
  timestamp: string;
  datetime: number;
  datetimeFormatted: string;
  csvFilename?: string;
  processType?: string;
  orderCount?: number;
  successCount?: number;
  failedCount?: number;
  draftCount: number;
  tempCount: number;
  totalFiles: number;
  zipFile: string | null;
  zipSize: number;
  folderPath: string;
}

export interface HistoryResponse {
  success: boolean;
  histories: HistoryEntry[];
  total: number;
  limit: number;
  offset: number;
}

export interface HistoryDetailResponse {
  success: boolean;
  timestamp: string;
  files: GeneratedFile[];
  folderPath: string;
}

export interface ListResponse {
  success: boolean;
  files: GeneratedFile[];
}

export interface AuthCheckResponse {
  success: boolean;
  authRequired: boolean;
  authenticated: boolean;
}

export interface LoginResponse {
  success: boolean;
  token: string | null;
  authRequired: boolean;
  error?: string;
}

const API_BASE = '/api';
// Direct PHP server URL for long-running requests (bypasses Next.js proxy timeout)
const PHP_DIRECT = process.env.NEXT_PUBLIC_PHP_DIRECT_URL || 'http://localhost:8080';

export type GenerationMode = 'normal' | 'download';

// テキスト検証の警告
export interface ValidationWarning {
  type: 'encoding' | 'typo' | 'date' | 'time';
  field: string;
  message: string;
  value: string;
}

// 注文単位の検証結果
export interface OrderValidation {
  order_name: string;
  warnings: ValidationWarning[];
}

// SSEイベントのデータ型
export interface SSEEvent {
  type: 'success' | 'error' | 'info' | 'warning' | 'skipped' | 'validation' | 'done';
  message: string;
  progress?: number;
  files?: GeneratedFile[];
  zipFile?: string | null;
  zipFilePath?: string | null;
  outputFolder?: string;
  order_name?: string;
  warnings?: ValidationWarning[];
}

// 認証トークン管理
const AUTH_TOKEN_KEY = 'lyly_auth_token';

function getStoredToken(): string | null {
  if (typeof window === 'undefined') return null;
  return localStorage.getItem(AUTH_TOKEN_KEY);
}

function setStoredToken(token: string | null) {
  if (typeof window === 'undefined') return;
  if (token) {
    localStorage.setItem(AUTH_TOKEN_KEY, token);
  } else {
    localStorage.removeItem(AUTH_TOKEN_KEY);
  }
}

function authHeaders(): Record<string, string> {
  const token = getStoredToken();
  if (!token) return {};
  return { 'Authorization': `Bearer ${token}` };
}

// 認証付きfetch（401時に自動でトークンクリア）
async function fetchWithAuth(url: string, options?: RequestInit): Promise<Response> {
  const response = await fetch(url, {
    ...options,
    headers: { ...authHeaders(), ...options?.headers },
  });
  if (response.status === 401) {
    setStoredToken(null);
    throw new Error('認証が必要です');
  }
  return response;
}

export const api = {
  // 認証状態確認
  checkAuth: async (): Promise<AuthCheckResponse> => {
    const response = await fetch(`${PHP_DIRECT}/api.php?action=check_auth`, {
      headers: authHeaders(),
    });
    return response.json();
  },

  // ログイン
  login: async (password: string): Promise<LoginResponse> => {
    const response = await fetch(`${PHP_DIRECT}/api.php?action=login`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ password }),
    });
    const data = await response.json();
    if (data.success && data.token) {
      setStoredToken(data.token);
    }
    return data;
  },

  // ログアウト
  logout: () => {
    setStoredToken(null);
  },

  // トークン取得（外部から参照用）
  getToken: getStoredToken,

  // POST: Upload CSV and generate PDFs (従来の一括レスポンス版)
  generate: async (
    file: File,
    processType: 'all' | 'temp' | 'draft' = 'all',
    mode: GenerationMode = 'download'
  ): Promise<GenerationResponse> => {
    const formData = new FormData();
    formData.append('csv', file);
    formData.append('process', processType);
    formData.append('mode', mode);

    const response = await fetchWithAuth(`${PHP_DIRECT}/api.php?action=generate`, {
      method: 'POST',
      body: formData,
    });

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    return response.json();
  },

  // POST: Upload CSV and generate PDFs (SSEストリーミング版)
  generateStream: async (
    file: File,
    processType: 'all' | 'temp' | 'draft' = 'all',
    mode: GenerationMode = 'download',
    onEvent: (event: SSEEvent) => void,
  ): Promise<void> => {
    const formData = new FormData();
    formData.append('csv', file);
    formData.append('process', processType);
    formData.append('mode', mode);

    const response = await fetchWithAuth(`${PHP_DIRECT}/api.php?action=generate_stream`, {
      method: 'POST',
      body: formData,
    });

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    const reader = response.body?.getReader();
    if (!reader) throw new Error('ReadableStream not supported');

    const decoder = new TextDecoder();
    let buffer = '';

    while (true) {
      const { done, value } = await reader.read();
      if (done) break;

      buffer += decoder.decode(value, { stream: true });

      // SSEフォーマット: "data: {...}\n\n" をパース
      const lines = buffer.split('\n\n');
      buffer = lines.pop() || ''; // 未完了の最後の部分を保持

      for (const line of lines) {
        const dataLine = line.trim();
        if (dataLine.startsWith('data: ')) {
          try {
            const event: SSEEvent = JSON.parse(dataLine.slice(6));
            onEvent(event);
          } catch {
            // パースエラーは無視
          }
        }
      }
    }
  },

  // GET: List generated files
  listFiles: async (
    type: 'all' | 'temp' | 'draft' = 'all'
  ): Promise<ListResponse> => {
    const response = await fetchWithAuth(`${API_BASE}/list?type=${type}`);
    return response.json();
  },

  // GET: Download URL for a file (direct PHP for large files)
  getDownloadUrl: (filePath: string): string => {
    const token = getStoredToken();
    const tokenParam = token ? `&_token=${encodeURIComponent(token)}` : '';
    return `${PHP_DIRECT}/api.php?action=download&file=${encodeURIComponent(filePath)}${tokenParam}`;
  },

  // GET: Preview URL for a file (direct PHP for large files)
  getPreviewUrl: (filePath: string): string => {
    const token = getStoredToken();
    const tokenParam = token ? `&_token=${encodeURIComponent(token)}` : '';
    return `${PHP_DIRECT}/api.php?action=preview&file=${encodeURIComponent(filePath)}${tokenParam}`;
  },

  // GET: History list (ページネーション対応)
  getHistory: async (limit = 50, offset = 0): Promise<HistoryResponse> => {
    const response = await fetchWithAuth(`${PHP_DIRECT}/api.php?action=history&limit=${limit}&offset=${offset}`);
    return response.json();
  },

  // GET: History detail
  getHistoryDetail: async (
    timestamp: string
  ): Promise<HistoryDetailResponse> => {
    const response = await fetchWithAuth(
      `${PHP_DIRECT}/api.php?action=history_detail&timestamp=${encodeURIComponent(timestamp)}`
    );
    return response.json();
  },

  // GET: Download all as ZIP (direct PHP for large files)
  getZipUrl: (type: 'all' | 'temp' | 'draft' = 'all'): string => {
    const token = getStoredToken();
    const tokenParam = token ? `&_token=${encodeURIComponent(token)}` : '';
    return `${PHP_DIRECT}/api.php?action=zip&type=${type}${tokenParam}`;
  },
};
