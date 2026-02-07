'use client';

import { useAuth } from '@/components/AuthProvider';
import { Button } from '@/components/ui/button';

export function HeaderAuth() {
  const { authRequired, isAuthenticated, logout } = useAuth();

  if (!authRequired || !isAuthenticated) return null;

  return (
    <Button
      variant="ghost"
      size="sm"
      onClick={() => { logout(); window.location.reload(); }}
      className="text-xs text-muted-foreground hover:text-foreground"
    >
      Logout
    </Button>
  );
}
