'use client';

import { createContext, useContext, useState, useEffect, useCallback, type ReactNode } from 'react';
import { api } from '@/lib/api';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';

interface AuthContextValue {
  isAuthenticated: boolean;
  authRequired: boolean;
  loading: boolean;
  connectionError: boolean;
  logout: () => void;
  retry: () => void;
}

const AuthContext = createContext<AuthContextValue>({
  isAuthenticated: false,
  authRequired: false,
  loading: true,
  connectionError: false,
  logout: () => {},
  retry: () => {},
});

export function useAuth() {
  return useContext(AuthContext);
}

export function AuthProvider({ children }: { children: ReactNode }) {
  const [authRequired, setAuthRequired] = useState(false);
  const [isAuthenticated, setIsAuthenticated] = useState(false);
  const [loading, setLoading] = useState(true);
  const [connectionError, setConnectionError] = useState(false);

  const checkAuthStatus = useCallback(() => {
    setLoading(true);
    setConnectionError(false);
    api.checkAuth()
      .then((res) => {
        setAuthRequired(res.authRequired);
        setIsAuthenticated(res.authenticated || !res.authRequired);
      })
      .catch(() => {
        setConnectionError(true);
        setIsAuthenticated(false);
      })
      .finally(() => setLoading(false));
  }, []);

  useEffect(() => {
    checkAuthStatus();
  }, [checkAuthStatus]);

  const logout = useCallback(() => {
    api.logout();
    setIsAuthenticated(false);
  }, []);

  const handleLoginSuccess = useCallback(() => {
    setIsAuthenticated(true);
  }, []);

  if (loading) {
    return (
      <div className="flex items-center justify-center min-h-[60vh] text-muted-foreground">
        <span className="animate-pulse font-mono">Loading...</span>
      </div>
    );
  }

  if (connectionError) {
    return (
      <div className="flex items-center justify-center min-h-[60vh]">
        <Card className="w-full max-w-sm">
          <CardContent className="pt-6 text-center space-y-4">
            <p className="text-muted-foreground">サーバーに接続できません</p>
            <Button variant="outline" onClick={checkAuthStatus}>再試行</Button>
          </CardContent>
        </Card>
      </div>
    );
  }

  if (authRequired && !isAuthenticated) {
    return <LoginForm onSuccess={handleLoginSuccess} />;
  }

  return (
    <AuthContext.Provider value={{ isAuthenticated, authRequired, loading, connectionError, logout, retry: checkAuthStatus }}>
      {children}
    </AuthContext.Provider>
  );
}

function LoginForm({ onSuccess }: { onSuccess: () => void }) {
  const [password, setPassword] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError(null);
    setSubmitting(true);

    try {
      const res = await api.login(password);
      if (res.success) {
        onSuccess();
      } else {
        setError(res.error || 'ログインに失敗しました');
      }
    } catch {
      setError('サーバーに接続できません');
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <div className="flex items-center justify-center min-h-[60vh]">
      <Card className="w-full max-w-sm">
        <CardHeader>
          <CardTitle className="text-center">
            <div className="w-12 h-12 rounded-lg bg-gradient-to-br from-emerald-500 to-sky-500 flex items-center justify-center font-mono font-bold text-background mx-auto mb-3">
              LY
            </div>
            LYLY PDF Generator
          </CardTitle>
        </CardHeader>
        <CardContent>
          <form onSubmit={handleSubmit} className="space-y-4">
            <div>
              <input
                type="password"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                placeholder="パスワード"
                autoFocus
                className="w-full px-3 py-2 rounded-lg border bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-emerald-500"
              />
            </div>
            {error && (
              <p className="text-sm text-red-500">{error}</p>
            )}
            <Button
              type="submit"
              className="w-full bg-gradient-to-r from-emerald-500 to-sky-500 hover:from-emerald-600 hover:to-sky-600"
              disabled={submitting || !password}
            >
              {submitting ? 'ログイン中...' : 'ログイン'}
            </Button>
          </form>
        </CardContent>
      </Card>
    </div>
  );
}
